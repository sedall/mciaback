<?php

namespace Modules\Access\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminClinicUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. ایجاد یا به‌روزرسانی Admin
        $admin = User::updateOrCreate(
            ['mobile' => '09900000001'],
            [
                'password' => Hash::make('Admin@123456'),
                // اگر فیلد دیگری مثل is_active داری، اینجا اضافه کن
            ]
        );
        $admin->syncRoles(['admin']);

        // 2. ایجاد یا به‌روزرسانی Clinic
        $clinic = User::updateOrCreate(
            ['mobile' => '09900000002'],
            [
                'password' => Hash::make('Clinic@123456'),
            ]
        );
        $clinic->syncRoles(['clinic']);

        $this->command->info('Admin and Clinic users seeded successfully.');
    }
}
