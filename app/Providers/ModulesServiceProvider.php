<?php

namespace App\Providers;

use App\Core\ModuleManager;
use App\Core\Services\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class, fn ($app) => new ModuleManager($app));
        $this->app->singleton(ModuleRegistry::class, fn ($app) => new ModuleRegistry($app, $app->make(ModuleManager::class)));
    }

    public function boot(): void
    {
        $moduleManager = $this->app->make(ModuleManager::class);

        foreach ($moduleManager->getMigrationPaths() as $path) {
            $this->loadMigrationsFrom($path);
        }

        $moduleManager->boot();
    }
}

