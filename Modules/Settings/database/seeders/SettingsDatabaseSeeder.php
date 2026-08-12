<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\Setting;

class SettingsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSettings = [
            // Ticket management options
            [
                'key' => 'ticket_attachments_enabled',
                'value' => 'true',
                'group' => 'ticket',
                'type' => 'boolean',
                'is_public' => true
            ],
            [
                'key' => 'ticket_max_file_size_mb',
                'value' => '10',
                'group' => 'ticket',
                'type' => 'integer',
                'is_public' => true
            ],
            [
                'key' => 'ticket_allowed_extensions',
                'value' => json_encode(['jpg', 'jpeg', 'png', 'pdf', 'zip']),
                'group' => 'ticket',
                'type' => 'json',
                'is_public' => true
            ],
            // Core system options
            [
                'key' => 'loan_interest_rate',
                'value' => '4.0',
                'group' => 'loan',
                'type' => 'string',
                'is_public' => true
            ]
        ];

        foreach ($defaultSettings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
