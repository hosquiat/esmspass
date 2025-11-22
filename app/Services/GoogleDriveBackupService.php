<?php

namespace App\Services;

use App\Models\BackupSetting;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;

class GoogleDriveBackupService
{
    protected ?Client $client = null;
    protected ?Drive $driveService = null;

    /**
     * Initialize Google Drive client
     */
    protected function initializeClient(): void
    {
        if ($this->client !== null) {
            return;
        }

        $credentials = BackupSetting::get('google_drive_credentials');

        if (!$credentials) {
            throw new \Exception('Google Drive credentials not configured');
        }

        $this->client = new Client();
        $this->client->setAuthConfig(json_decode($credentials, true));
        $this->client->addScope(Drive::DRIVE_FILE);

        $this->driveService = new Drive($this->client);
    }

    /**
     * Upload backup file to Google Drive
     */
    public function uploadBackup(string $filepath): string
    {
        $this->initializeClient();

        $folderId = BackupSetting::get('google_drive_folder_id');
        $filename = basename($filepath);

        $fileMetadata = new DriveFile([
            'name' => $filename,
            'parents' => $folderId ? [$folderId] : [],
        ]);

        $content = file_get_contents($filepath);

        $file = $this->driveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => 'application/sql',
            'uploadType' => 'multipart',
            'fields' => 'id, name, webViewLink'
        ]);

        Log::info('Backup uploaded to Google Drive', [
            'file_id' => $file->id,
            'filename' => $filename
        ]);

        return $file->id;
    }

    /**
     * List backups in Google Drive
     */
    public function listBackups(): array
    {
        $this->initializeClient();

        $folderId = BackupSetting::get('google_drive_folder_id');

        $query = "name contains 'teamvault_backup_' and mimeType='application/sql'";
        if ($folderId) {
            $query .= " and '{$folderId}' in parents";
        }
        $query .= " and trashed=false";

        $response = $this->driveService->files->listFiles([
            'q' => $query,
            'orderBy' => 'createdTime desc',
            'fields' => 'files(id, name, size, createdTime, webViewLink)',
        ]);

        $backups = [];
        foreach ($response->files as $file) {
            $backups[] = [
                'id' => $file->id,
                'filename' => $file->name,
                'size' => $file->size,
                'size_human' => $this->formatBytes($file->size),
                'created_at' => $file->createdTime,
                'web_view_link' => $file->webViewLink,
            ];
        }

        return $backups;
    }

    /**
     * Download backup from Google Drive
     */
    public function downloadBackup(string $fileId, string $destinationPath): bool
    {
        $this->initializeClient();

        $response = $this->driveService->files->get($fileId, [
            'alt' => 'media'
        ]);

        $content = $response->getBody()->getContents();
        file_put_contents($destinationPath, $content);

        Log::info('Backup downloaded from Google Drive', [
            'file_id' => $fileId,
            'destination' => $destinationPath
        ]);

        return true;
    }

    /**
     * Delete backup from Google Drive
     */
    public function deleteBackup(string $fileId): bool
    {
        $this->initializeClient();

        $this->driveService->files->delete($fileId);

        Log::info('Backup deleted from Google Drive', ['file_id' => $fileId]);

        return true;
    }

    /**
     * Test Google Drive connection
     */
    public function testConnection(): array
    {
        try {
            $this->initializeClient();

            $about = $this->driveService->about->get(['fields' => 'user']);

            return [
                'success' => true,
                'user' => [
                    'email' => $about->user->emailAddress ?? 'Unknown',
                    'name' => $about->user->displayName ?? 'Unknown',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create or get TeamVault Backups folder
     */
    public function ensureBackupFolder(): string
    {
        $this->initializeClient();

        // Check if folder already exists
        $response = $this->driveService->files->listFiles([
            'q' => "name='TeamVault Backups' and mimeType='application/vnd.google-apps.folder' and trashed=false",
            'fields' => 'files(id, name)',
        ]);

        if (count($response->files) > 0) {
            return $response->files[0]->id;
        }

        // Create new folder
        $fileMetadata = new DriveFile([
            'name' => 'TeamVault Backups',
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);

        $folder = $this->driveService->files->create($fileMetadata, [
            'fields' => 'id'
        ]);

        Log::info('Created TeamVault Backups folder in Google Drive', ['folder_id' => $folder->id]);

        return $folder->id;
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
}
