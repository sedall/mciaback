<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Access\Database\Seeders\AccessDatabaseSeeder;
use Modules\Access\Database\Seeders\RoleSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccessDatabaseSeeder::class,
        ]);
    }
}
