<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\ChapterRepositoryInterface;
use App\Repositories\ChapterRepository;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\LanguageRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ChapterRepositoryInterface::class,
            ChapterRepository::class
        );

        $this->app->bind(
            LanguageRepositoryInterface::class,
            LanguageRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
