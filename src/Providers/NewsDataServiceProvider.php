<?php

namespace hexa_package_newsdata\Providers;

use Illuminate\Support\ServiceProvider;
use hexa_package_newsdata\Services\NewsDataService;

/**
 * NewsDataServiceProvider — registers NewsData package services, routes, views.
 */
class NewsDataServiceProvider extends ServiceProvider
{
    /**
     * Register services into the container.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/newsdata.php', 'newsdata');
        $this->app->singleton(NewsDataService::class);
    }

    /**
     * Bootstrap package resources.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/newsdata.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'newsdata');
        $this->registerSidebarItems();
    }

    /**
     * Push sidebar menu items and settings card into core layout stacks.
     *
     * @return void
     */
    private function registerSidebarItems(): void
    {
        view()->composer('layouts.app', function ($view) {
            if (config('hexa.app_controls_sidebar', false)) return;
            $view->getFactory()->startPush('sidebar-menu', view('newsdata::partials.sidebar-menu')->render());
        });

        view()->composer('settings.index', function ($view) {
            $view->getFactory()->startPush('settings-cards', view('newsdata::partials.settings-card')->render());
        });
    }
}
