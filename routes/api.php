<?php

use App\Http\Controllers\Api\RecordController;
use App\Http\Controllers\Admin\BackupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// User route - get current authenticated user
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth');

// Records API routes - all protected by web auth middleware
// Using 'auth' middleware for session-based authentication (hybrid Blade+Vue app)
Route::middleware(['auth'])->group(function () {
    // Standard REST routes
    Route::apiResource('records', RecordController::class);

    // Additional archive/restore routes
    Route::patch('/records/{record}/archive', [RecordController::class, 'archive'])
        ->name('records.archive');

    Route::patch('/records/{record}/restore', [RecordController::class, 'restore'])
        ->name('records.restore');

    // Backup management routes (admin only)
    Route::prefix('admin/backups')->group(function () {
        Route::get('/settings', [BackupController::class, 'getSettings']);
        Route::put('/settings', [BackupController::class, 'updateSettings']);
        Route::post('/google-drive/configure', [BackupController::class, 'configureGoogleDrive']);
        Route::get('/google-drive/test', [BackupController::class, 'testGoogleDrive']);
        Route::post('/run', [BackupController::class, 'runBackup']);
        Route::get('/list', [BackupController::class, 'listBackups']);
        Route::get('/google-drive/list', [BackupController::class, 'listGoogleDriveBackups']);
        Route::get('/download/{filename}', [BackupController::class, 'downloadBackup']);
        Route::post('/restore', [BackupController::class, 'restoreBackup']);
    });
});
