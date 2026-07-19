<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (!File::isDirectory($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $module) {
            $moduleName = basename($module);

            // Register routes
            $routesPath = $module . '/routes.php';
            if (File::exists($routesPath)) {
                Route::middleware('web')
                    ->group($routesPath);
            }

            // Register views
            $viewsPath = $module . '/Views';
            if (File::isDirectory($viewsPath)) {
                $this->loadViewsFrom($viewsPath, $moduleName);
            }
        }
    }
}
