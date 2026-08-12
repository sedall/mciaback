<?php

namespace Tickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Access\Database\Seeders\RoleSeeder;
use Tests\TestCase;

class TicketsApiTest extends TestCase
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

    public function test_customer_can_create_ticket(): void
    {
        Sanctum::actingAs($this->user('customer'));

        $res = $this->postJson('/api/customer/tickets', [
            'subject' => 'مشکل در پرداخت',
            'body' => 'پرداخت ثبت نشده',
            'priority' => 'normal',
        ]);

        $res->assertStatus(201)->assertJsonStructure([
            'data' => ['id', 'subject', 'status']
        ]);
    }

    public function test_customer_can_reply_to_own_ticket(): void
    {
        Sanctum::actingAs($customer = $this->user('customer'));

        $ticketId = $this->postJson('/api/customer/tickets', [
            'subject' => 'پیگیری',
            'body' => 'وضعیت درخواست؟',
            'priority' => 'normal',
        ])->json('data.id');

        Sanctum::actingAs($customer);

        $res = $this->postJson("/api/customer/tickets/{$ticketId}/messages", [
            'body' => 'لطفا بررسی شود',
        ]);

        $res->assertStatus(201);
    }

    public function test_customer_cannot_access_admin_status_endpoint(): void
    {
        Sanctum::actingAs($this->user('customer'));

        $ticketId = $this->postJson('/api/customer/tickets', [
            'subject' => 'تست',
            'body' => '...',
            'priority' => 'normal',
        ])->json('data.id');

        $res = $this->patchJson("/api/admin/tickets/{$ticketId}/status", [
            'status' => 'closed',
        ]);

        $res->assertStatus(403);
    }

    public function test_admin_can_change_ticket_status(): void
    {
        Sanctum::actingAs($this->user('customer'));
        $ticketId = $this->postJson('/api/customer/tickets', [
            'subject' => 'خطا',
            'body' => 'شرح خطا',
            'priority' => 'high',
        ])->json('data.id');

        Sanctum::actingAs($this->user('admin'));
        $res = $this->patchJson("/api/admin/tickets/{$ticketId}/status", [
            'status' => 'closed',
        ]);

        $res->assertStatus(200)->assertJsonPath('data.status', 'closed');
    }
}
