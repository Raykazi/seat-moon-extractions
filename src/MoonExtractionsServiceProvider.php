<?php

namespace YourNamespace\Seat\MoonExtractions;

use Illuminate\Support\ServiceProvider;

class MoonExtractionsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/moon-extractions.php', 'moon-extractions');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        
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
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\SyncMoonExtractions::class,
            ]);
        }
    }
}
