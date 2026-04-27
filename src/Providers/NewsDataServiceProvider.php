<?php

namespace hexa_package_newsdata\Providers;

use Illuminate\Support\ServiceProvider;
use hexa_package_newsdata\Services\NewsDataService;
use hexa_core\Services\PackageRegistryService;

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

        // Sidebar links — registered via PackageRegistryService with auto permission checks
        if (!config('hexa.app_controls_sidebar', false)) {
            $registry = app(PackageRegistryService::class);
            // HWS-SIDEBAR-MENU-3L-BEGIN
            $registry->registerDomainGroup('Discovery', 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 20);
            $registry->registerSectionGroup('Sandbox', 'Discovery', '', 20);
            // HWS-SIDEBAR-MENU-3L-END

            $registry->registerSidebarLink('newsdata.index', 'NewsData', 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z', 'Sandbox', 'newsdata', 82);
            $registry->registerPackage('newsdata', 'hexawebsystems/laravel-hexa-package-newsdata', [
                'title' => 'NewsData',
                'description' => 'NewsData source configuration for discovery and article sourcing.',
                'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
                'color' => 'sky',
                'settingsRoute' => 'settings.newsdata',
            ]);
        }

        // Settings card on /settings page
        $this->registerSettingsCard();
    }

    /**
     * Register settings card on the core settings page.
     *
     * @return void
     */
    private function registerSettingsCard(): void
    {
        view()->composer('settings.index', function ($view) {
            $view->getFactory()->startPush('settings-cards', view('newsdata::partials.settings-card')->render());
        });
    }
}
