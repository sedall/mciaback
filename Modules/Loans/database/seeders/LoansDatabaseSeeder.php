<?php

namespace Modules\Loans\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Services\SettingService;

class LoansDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /** @var SettingService $settings */
        $settings = app(SettingService::class);

        // set(key, value, group, type, isPublic)
        $settings->set('min_amount', 10000000, 'loans', 'integer');
        $settings->set('max_amount', 500000000, 'loans', 'integer');
        $settings->set('fee_rate', 0.04, 'loans', 'float');
        $settings->set('allowed_tenures', [3, 6, 12], 'loans', 'json');
    }
}
