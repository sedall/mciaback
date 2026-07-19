<?php

namespace Modules\Loans\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Access\Http\Middleware\EnsurePanelAccess;

class LoansServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Loans';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'loans';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
    public function boot(): void
    {
        parent::boot();

        Route::aliasMiddleware('panel.access', EnsurePanelAccess::class);
    }
}
