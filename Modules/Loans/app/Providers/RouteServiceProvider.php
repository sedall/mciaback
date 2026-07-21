<?php

namespace Modules\Loans\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Loans';

    public function boot(): void
    {
        parent::boot();

        Route::middleware('api')
            ->prefix('api')
            ->group(module_path($this->name, 'routes/api.php'));

        Route::middleware('api')
            ->prefix('api')
            ->group(module_path($this->name, 'routes/api-admin.php'));

        Route::middleware('web')
            ->group(module_path($this->name, 'routes/web.php'));
    }
}
