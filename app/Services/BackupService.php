<?php

namespace App\Services;

use App\Models\BackupSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupService
{
    protected string $backupPath;
    protected GoogleDriveBackupService $googleDriveService;

    public function __construct(GoogleDriveBackupService $googleDriveService)
    {
        $this->backupPath = storage_path('app/backups');
        $this->googleDriveService = $googleDriveService;

        // Ensure backup directory exists
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * Run full backup (database + files)
     */
    public function runBackup(): array
    {
        $results = [
            'success' => true,
            'database' => false,
            'google_drive' => false,
            'errors' => [],
            'backup_file' => null,
        ];

        try {
            // Create database backup
            $backupFile = $this->backupDatabase();
            $results['backup_file'] = $backupFile;
            $results['database'] = true;

            // Update last backup timestamp
            BackupSetting::set('last_backup_at', now()->toIso8601String());

            // Upload to Google Drive if enabled
            if (BackupSetting::get('google_drive_enabled', false)) {
                try {
                    $this->googleDriveService->uploadBackup($backupFile);
                    $results['google_drive'] = true;
                } catch (\Exception $e) {
                    $results['errors'][] = 'Google Drive upload failed: ' . $e->getMessage();
                    Log::error('Google Drive backup failed', ['error' => $e->getMessage()]);
                }
            }

            // Clean old backups
            $this->cleanOldBackups();

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Backup failed', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Create database backup
     */
    protected function backupDatabase(): string
    {
        $filename = sprintf(
            'teamvault_backup_%s.sql',
            now()->format('Y-m-d_His')
        );

        $filepath = $this->backupPath . '/' . $filename;

        $connection = config('database.default');
        $database = config('database.connections.' . $connection . '.database');
        $username = config('database.connections.' . $connection . '.username');
        $password = config('database.connections.' . $connection . '.password');
        $host = config('database.connections.' . $connection . '.host');
        $port = config('database.connections.' . $connection . '.port', 5432);

        // PostgreSQL backup command
        if ($connection === 'pgsql') {
            $command = sprintf(
                'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -d %s -F c -b -v -f %s 2>&1',
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
        }
        // MySQL backup command
        elseif ($connection === 'mysql') {
            $command = sprintf(
                'mysqldump -h %s -P %s -u %s -p%s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
        } else {
            throw new \Exception('Unsupported database connection: ' . $connection);
        }

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('Database backup command failed', [
                'command' => $command,
                'output' => $output,
                'return_code' => $returnCode
            ]);
            throw new \Exception('Database backup failed: ' . implode("\n", $output));
        }

        // Verify backup file exists and has content
        if (!file_exists($filepath) || filesize($filepath) === 0) {
            throw new \Exception('Backup file was not created or is empty');
        }

        Log::info('Database backup created successfully', ['file' => $filename]);

        return $filepath;
    }

    /**
     * Clean old backups based on retention policy
     */
    protected function cleanOldBackups(): void
    {
        $retentionDays = (int) BackupSetting::get('filesystem_retention_days', 30);
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $files = glob($this->backupPath . '/teamvault_backup_*.sql');

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffDate->timestamp) {
                unlink($file);
                Log::info('Deleted old backup', ['file' => basename($file)]);
            }
        }
    }

    /**
     * Get list of available backups
     */
    public function listBackups(): array
    {
        $files = glob($this->backupPath . '/teamvault_backup_*.sql');
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'size_human' => $this->formatBytes(filesize($file)),
                'created_at' => Carbon::createFromTimestamp(filemtime($file))->toIso8601String(),
                'path' => $file,
            ];
        }

        // Sort by creation time, newest first
        usort($backups, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $backups;
    }

    /**
     * Download a specific backup file
     */
    public function downloadBackup(string $filename): ?string
    {
        $filepath = $this->backupPath . '/' . basename($filename);

        if (!file_exists($filepath)) {
            return null;
        }

        return $filepath;
    }

    /**
     * Format bytes to human-readable size
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup(string $filename): bool
    {
        $filepath = $this->backupPath . '/' . basename($filename);

        if (!file_exists($filepath)) {
            throw new \Exception('Backup file not found');
        }

        $connection = config('database.default');
        $database = config('database.connections.' . $connection . '.database');
        $username = config('database.connections.' . $connection . '.username');
        $password = config('database.connections.' . $connection . '.password');
        $host = config('database.connections.' . $connection . '.host');
        $port = config('database.connections.' . $connection . '.port', 5432);

        // PostgreSQL restore command
        if ($connection === 'pgsql') {
            // Drop and recreate database
            $dropCommand = sprintf(
                'PGPASSWORD=%s psql -h %s -p %s -U %s -d postgres -c "DROP DATABASE IF EXISTS %s; CREATE DATABASE %s;" 2>&1',
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                $database,
                $database
            );

            exec($dropCommand, $dropOutput, $dropReturnCode);

            if ($dropReturnCode !== 0) {
                throw new \Exception('Failed to drop/create database: ' . implode("\n", $dropOutput));
            }

            // Restore backup
            $restoreCommand = sprintf(
                'PGPASSWORD=%s pg_restore -h %s -p %s -U %s -d %s -v %s 2>&1',
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
        }
        // MySQL restore command
        elseif ($connection === 'mysql') {
            $restoreCommand = sprintf(
                'mysql -h %s -P %s -u %s -p%s %s < %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
        } else {
            throw new \Exception('Unsupported database connection: ' . $connection);
        }

        exec($restoreCommand, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('Database restore failed', [
                'output' => $output,
                'return_code' => $returnCode
            ]);
            throw new \Exception('Database restore failed: ' . implode("\n", $output));
        }

        Log::info('Database restored successfully', ['file' => $filename]);

        return true;
    }
}
