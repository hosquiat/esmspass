<?php

use App\Http\Controllers\Api\RecordController;
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
});
