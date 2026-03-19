<?php

namespace hexa_package_newsdata\Providers;

use Illuminate\Support\ServiceProvider;
use hexa_package_newsdata\Services\NewsDataService;

class NewsDataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        ->mergeConfigFrom(__DIR__ . '/../../config/newsdata.php', 'newsdata');
        $this->app->singleton(NewsDataService::class);
    }

    public function boot(): void {}
}
