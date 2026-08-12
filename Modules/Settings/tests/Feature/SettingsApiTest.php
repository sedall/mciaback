<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('panel.admin', 'sanctum');
        Permission::findOrCreate('panel.clinic', 'sanctum');
        Permission::findOrCreate('panel.customer', 'sanctum');

        $adminRole = Role::findOrCreate('admin', 'sanctum');
        $adminRole->givePermissionTo('panel.admin');
    }

    private function makeUserWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permission);
        return $user;
    }

    public function test_guest_cannot_access_admin_settings_routes(): void
    {
        $this->getJson('/api/admin/settings')->assertStatus(401);

        $this->putJson('/api/admin/settings/bulk', [
            'settings' => [
                ['key' => 'support.phone', 'value' => '02100000000'],
            ],
        ])->assertStatus(401);
    }

    public function test_admin_can_view_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/settings');

        $response->assertOk();

    }

    public function test_admin_can_update_settings_in_bulk(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $payload = [
            'settings' => [
                ['key' => 'support.phone', 'value' => '02111111111'],
                ['key' => 'support.email', 'value' => 'support@example.com'],
            ],
        ];

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/settings/bulk', $payload)
            ->assertOk();
    }

    public function test_customer_cannot_access_admin_settings_routes(): void
    {
        $customer = $this->makeUserWithPermission('panel.customer');

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/settings')
            ->assertStatus(403);

        $this->actingAs($customer, 'sanctum')
            ->putJson('/api/admin/settings/bulk', [
                'settings' => [
                    ['key' => 'support.phone', 'value' => '02122222222'],
                ],
            ])
            ->assertStatus(403);
    }

    public function test_clinic_cannot_access_admin_settings_routes(): void
    {
        $clinic = $this->makeUserWithPermission('panel.clinic');

        $this->actingAs($clinic, 'sanctum')
            ->getJson('/api/admin/settings')
            ->assertStatus(403);

        $this->actingAs($clinic, 'sanctum')
            ->putJson('/api/admin/settings/bulk', [
                'settings' => [
                    ['key' => 'support.phone', 'value' => '02133333333'],
                ],
            ])
            ->assertStatus(403);
    }
}
