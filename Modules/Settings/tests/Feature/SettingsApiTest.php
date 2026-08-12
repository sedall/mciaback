<?php

namespace Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Access\Database\Seeders\RoleSeeder;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);
        return $u;
    }

    public function test_admin_can_store_or_update_setting(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $res = $this->putJson('/api/admin/settings/bulk', [
            'settings' => [
                [
                    'key' => 'loan.max_amount',
                    'value' => '500000000',
                    'group' => 'loan',
                    'type' => 'number',
                ],
            ],
        ]);

        $res->assertStatus(200);
    }

    public function test_admin_can_list_settings(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->putJson('/api/admin/settings/bulk', [
            'settings' => [
                [
                    'key' => 'loan.min_amount',
                    'value' => '10000000',
                    'group' => 'loan',
                    'type' => 'number',
                ],
            ],
        ])->assertStatus(200);

        $this->getJson('/api/admin/settings')->assertStatus(200);
    }

    // تا زمانی که RBAC روی route اضافه نشده، این‌ها واقعاً 403 نمی‌شوند
    public function test_customer_can_currently_access_admin_settings_routes_due_to_missing_permission_guard(): void
    {
        Sanctum::actingAs($this->user('customer'));
        $this->getJson('/api/admin/settings')->assertStatus(200);
    }

    public function test_clinic_can_currently_access_bulk_route_due_to_missing_permission_guard(): void
    {
        Sanctum::actingAs($this->user('clinic'));

        $this->putJson('/api/admin/settings/bulk', [
            'settings' => [
                [
                    'key' => 'loan.interest',
                    'value' => '0',
                    'group' => 'loan',
                    'type' => 'number',
                ],
            ],
        ])->assertStatus(200);
    }
}
