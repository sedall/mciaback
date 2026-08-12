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

        $res = $this->postJson('/api/admin/settings', [
            'key' => 'loan.max_amount',
            'value' => '500000000',
            'group' => 'loan',
            'type' => 'number',
        ]);

        $res->assertStatus(200)->assertJsonStructure([
            'data' => ['id', 'key', 'value']
        ]);
    }

    public function test_admin_can_list_settings(): void
    {
        Sanctum::actingAs($this->user('admin'));

        // create one
        $this->postJson('/api/admin/settings', [
            'key' => 'loan.min_amount',
            'value' => '10000000',
            'group' => 'loan',
            'type' => 'number',
        ])->assertStatus(200);

        $res = $this->getJson('/api/admin/settings');
        $res->assertStatus(200);
    }

    public function test_customer_cannot_access_admin_settings_routes(): void
    {
        Sanctum::actingAs($this->user('customer'));
        $res = $this->getJson('/api/admin/settings');
        $res->assertStatus(403);
    }

    public function test_clinic_cannot_access_admin_settings_routes(): void
    {
        Sanctum::actingAs($this->user('clinic'));

        $res = $this->postJson('/api/admin/settings', [
            'key' => 'loan.interest',
            'value' => '0',
            'group' => 'loan',
            'type' => 'number',
        ]);

        $res->assertStatus(403);
    }
}
