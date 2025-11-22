<?php

namespace App\Providers;

use App\Models\Record;
use App\Observers\RecordObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Record::observe(RecordObserver::class);

        // Define admin-only gate
        Gate::define('admin-only', function ($user) {
            return $user->role === 'admin';
        });
    }
}
