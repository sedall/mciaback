<?php

namespace Modules\Settings\Providers;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Settings';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }
}
