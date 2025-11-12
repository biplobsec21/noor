<?php

namespace App\Providers;

use App\Services\QuranApi\AuthService;
use App\Services\QuranApi\ChapterService;
use Illuminate\Support\ServiceProvider;

class QuranApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register AuthService as a singleton
        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService();
        });

        // Register ChapterService as a singleton
        $this->app->singleton(ChapterService::class, function ($app) {
            return new ChapterService($app->make(AuthService::class));
        });

        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/quran.php',
            'quran'
        );
    }

    public function boot(): void
    {
        // Publish configuration if needed
        $this->publishes([
            __DIR__ . '/../../config/quran.php' => config_path('quran.php'),
        ], 'quran-config');
    }
}
