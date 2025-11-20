<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\RecordController;
use App\Http\Controllers\Auth\BasicAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

// Redirect root to login or records
Route::get('/', function () {
    return auth()->check()
        ? redirect('/records')
        : redirect('/login');
});

// Authentication routes (not protected)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [BasicAuthController::class, 'login'])
    ->name('login.post');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');

Route::post('/logout', [GoogleAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Password change routes (requires auth but not password changed)
Route::middleware('auth')->group(function () {
    Route::get('/change-password', [BasicAuthController::class, 'showChangePasswordForm'])
        ->name('password.change.form');

    Route::post('/change-password', [BasicAuthController::class, 'changePassword'])
        ->name('password.change');
});

// Protected routes (require authentication)
Route::middleware('auth')->group(function () {
    // Blade views
    Route::get('/records', function () {
        return view('records.index');
    })->name('records.index');

    Route::get('/records/{record}', function () {
        return view('records.show');
    })->name('records.show');

    // Admin-only settings page
    Route::middleware('admin')->group(function () {
        Route::get('/settings', function () {
            return view('settings.index');
        })->name('settings.index');
    });

    // API routes for Vue components (using web middleware for session auth)
    Route::prefix('api')->group(function () {
        // Debug route
        Route::get('/debug', function () {
            return response()->json([
                'authenticated' => auth()->check(),
                'user' => auth()->user(),
                'session_id' => session()->getId(),
            ]);
        });

        // User route
        Route::get('/user', function () {
            return auth()->user();
        });

        // Records API
        Route::apiResource('records', RecordController::class)->names([
            'index' => 'api.records.index',
            'store' => 'api.records.store',
            'show' => 'api.records.show',
            'update' => 'api.records.update',
            'destroy' => 'api.records.destroy',
        ]);

        Route::patch('/records/{record}/archive', [RecordController::class, 'archive'])
            ->name('api.records.archive');

        Route::patch('/records/{record}/restore', [RecordController::class, 'restore'])
            ->name('api.records.restore');

        Route::get('/records/{record}/changes', [RecordController::class, 'changes'])
            ->name('api.records.changes');

        // Admin-only API routes
        Route::middleware('admin')->group(function () {
            Route::post('/records/export', [RecordController::class, 'export'])
                ->name('api.records.export');

            Route::post('/records/import', [RecordController::class, 'import'])
                ->name('api.records.import');

            // User management routes
            Route::get('/admin/users', [UserController::class, 'index'])
                ->name('api.admin.users.index');

            Route::patch('/admin/users/{user}/role', [UserController::class, 'updateRole'])
                ->name('api.admin.users.updateRole');

            Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])
                ->name('api.admin.users.destroy');
        });
    });
});
