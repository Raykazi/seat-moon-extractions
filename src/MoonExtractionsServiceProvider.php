<?php

namespace mrmajestic\Seat\MoonExtractions;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Seat\Services\AbstractSeatPlugin;

class MoonExtractionsServiceProvider extends AbstractSeatPlugin
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/moon-extractions.php', 'moon-extractions');
    }


    /**
     * Return the plugin public name as it should be displayed into settings.
     *
     * @return string
     *
     * @example SeAT Web
     */
    public function getName(): string
    {
        return 'SeAT Moon Extractions';
    }

    /**
     * Return the plugin repository address.
     *
     * @example https://github.com/eveseat/web
     *
     * @return string
     */
    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/tjerge/seat-moon-extractions';
    }

    /**
     * Return the plugin technical name as published on package manager.
     *
     * @return string
     *
     * @example web
     */
    public function getPackagistPackageName(): string
    {
        return 'seat-moon-extractions';
    }

    /**
     * Return the plugin vendor tag as published on package manager.
     *
     * @return string
     *
     * @example eveseat
     */
    public function getPackagistVendorName(): string
    {
        return 'mrmajestic/seat-moon-extractions';
    }
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        if (! $this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/moon-extractions.php' => config_path('moon-extractions.php'),
        ], 'config');
        
        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');
        
		// API Documentation (L5-Swagger): add our annotation paths even if the helper doesn't exist
		$pluginAnnotationPaths = [
			__DIR__ . '/Http/Resources',
			__DIR__ . '/Http/Controllers/Api/V2',
		];
		$this->registerApiAnnotationsPath($pluginAnnotationPaths);
	
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\SyncMoonExtractions::class,
            ]);
        }
    }
}
