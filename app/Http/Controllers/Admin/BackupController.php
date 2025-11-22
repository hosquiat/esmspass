<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupSetting;
use App\Services\BackupService;
use App\Services\GoogleDriveBackupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService,
        protected GoogleDriveBackupService $googleDriveService
    ) {
        //
    }

    /**
     * Get backup settings
     */
    public function getSettings(): JsonResponse
    {
        Gate::authorize('admin-only');

        return response()->json([
            'filesystem_enabled' => BackupSetting::get('filesystem_enabled', true),
            'filesystem_retention_days' => (int) BackupSetting::get('filesystem_retention_days', 30),
            'google_drive_enabled' => BackupSetting::get('google_drive_enabled', false),
            'google_drive_folder_id' => BackupSetting::get('google_drive_folder_id'),
            'backup_schedule' => BackupSetting::get('backup_schedule', 'daily'),
            'last_backup_at' => BackupSetting::get('last_backup_at'),
        ]);
    }

    /**
     * Update backup settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        Gate::authorize('admin-only');

        $validated = $request->validate([
            'filesystem_enabled' => 'boolean',
            'filesystem_retention_days' => 'integer|min:1|max:365',
            'google_drive_enabled' => 'boolean',
            'google_drive_folder_id' => 'nullable|string',
            'backup_schedule' => 'string|in:hourly,daily,weekly',
        ]);

        foreach ($validated as $key => $value) {
            $type = is_bool($value) ? 'boolean' : 'string';
            BackupSetting::set($key, $value, $type);
        }

        return response()->json([
            'message' => 'Backup settings updated successfully',
        ]);
    }

    /**
     * Configure Google Drive credentials
     */
    public function configureGoogleDrive(Request $request): JsonResponse
    {
        Gate::authorize('admin-only');

        $validated = $request->validate([
            'credentials' => 'required|json',
        ]);

        try {
            // Validate credentials by testing connection
            BackupSetting::set('google_drive_credentials', $validated['credentials'], 'encrypted');

            $testResult = $this->googleDriveService->testConnection();

            if (!$testResult['success']) {
                BackupSetting::set('google_drive_credentials', null, 'encrypted');
                return response()->json([
                    'message' => 'Invalid Google Drive credentials: ' . $testResult['error'],
                ], 422);
            }

            // Create or get backup folder
            $folderId = $this->googleDriveService->ensureBackupFolder();
            BackupSetting::set('google_drive_folder_id', $folderId);

            return response()->json([
                'message' => 'Google Drive configured successfully',
                'user' => $testResult['user'],
                'folder_id' => $folderId,
            ]);

        } catch (\Exception $e) {
            Log::error('Google Drive configuration failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to configure Google Drive: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test Google Drive connection
     */
    public function testGoogleDrive(): JsonResponse
    {
        Gate::authorize('admin-only');

        try {
            $result = $this->googleDriveService->testConnection();

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Run backup manually
     */
    public function runBackup(): JsonResponse
    {
        Gate::authorize('admin-only');

        try {
            $results = $this->backupService->runBackup();

            return response()->json([
                'message' => $results['success'] ? 'Backup completed successfully' : 'Backup failed',
                'results' => $results,
            ], $results['success'] ? 200 : 500);

        } catch (\Exception $e) {
            Log::error('Manual backup failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Backup failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all backups (filesystem)
     */
    public function listBackups(): JsonResponse
    {
        Gate::authorize('admin-only');

        try {
            $backups = $this->backupService->listBackups();

            return response()->json([
                'backups' => $backups,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to list backups: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List Google Drive backups
     */
    public function listGoogleDriveBackups(): JsonResponse
    {
        Gate::authorize('admin-only');

        try {
            $backups = $this->googleDriveService->listBackups();

            return response()->json([
                'backups' => $backups,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to list Google Drive backups: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download backup
     */
    public function downloadBackup(string $filename)
    {
        Gate::authorize('admin-only');

        $filepath = $this->backupService->downloadBackup($filename);

        if (!$filepath) {
            return response()->json([
                'message' => 'Backup file not found',
            ], 404);
        }

        return response()->download($filepath);
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup(Request $request): JsonResponse
    {
        Gate::authorize('admin-only');

        $validated = $request->validate([
            'filename' => 'required|string',
        ]);

        try {
            $this->backupService->restoreBackup($validated['filename']);

            return response()->json([
                'message' => 'Database restored successfully. Please refresh the page.',
            ]);

        } catch (\Exception $e) {
            Log::error('Database restore failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Restore failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
