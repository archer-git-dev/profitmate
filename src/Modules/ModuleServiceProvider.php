<?php

namespace Modules;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    protected array $modules = [
        'Auth',
        // ...
    ];

    public function boot(): void
    {
        foreach ($this->modules as $module) {
            $this->loadRoutesForModule($module);
            $this->loadMigrationsForModule($module);
        }
    }

    protected function loadRoutesForModule(string $module): void
    {
        $routePath = base_path("src/Modules/{$module}/Routes/api.php");

        if (file_exists($routePath)) {
            Route::prefix('api/v1')
                ->middleware('api')
                ->group($routePath);
        }
    }

    protected function loadMigrationsForModule(string $module): void
    {
        $migrationPath = base_path("src/Modules/{$module}/Database/Migrations");

        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }
}
