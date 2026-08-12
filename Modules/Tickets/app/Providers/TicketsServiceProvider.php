<?php

namespace Modules\Tickets\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Modules\Tickets\Providers\EventServiceProvider;
use Modules\Tickets\Providers\RouteServiceProvider;

class TicketsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Tickets';

    public function boot(): void
    {
        $this->registerConfig();
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }

    public function register(): void
    {
        //
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path('tickets.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            'tickets'
        );
    }
}
