<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
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
        // Only local development should ever read the public/hot file.
        // Non-local environments must always prefer built assets.
        if (! app()->isLocal()) {
            Vite::useHotFile(storage_path('framework/vite.hot'));
        }
    }
}
