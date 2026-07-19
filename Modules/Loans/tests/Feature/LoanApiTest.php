<?php

namespace Modules\Loans\Tests\Feature;

use App\Models\User;
use Modules\Loans\Models\Loan;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'sanctum');
        Role::findOrCreate('customer', 'sanctum');
        Role::findOrCreate('clinic', 'sanctum');
        Role::findOrCreate('expert', 'sanctum');
    }
    protected function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'mobile' => fake()->unique()->numerify('09#########'),
            'password' => bcrypt('password'),
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_customer_can_create_loan_request(): void
    {
        $customer = $this->createUserWithRole('customer');

        $payload = [
            'amount' => 10000000,
            'term_months' => 10,
            'purpose' => 'clinic treatment',
        ];

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/loans', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('loans', [
            'customer_id' => $customer->id,
            'amount' => 10000000,
        ]);
    }

    public function test_customer_can_view_their_loans(): void
    {
        $customer = $this->createUserWithRole('customer');

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/customer/loans')
            ->assertOk();
    }

    public function test_admin_can_approve_loan(): void
    {
        $customer = $this->createUserWithRole('customer');
        $admin = $this->createUserWithRole('admin');

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/loans', [
                'amount' => 15000000,
                'term_months' => 12,
                'purpose' => 'medical expense',
            ])
            ->assertStatus(201);

        $loan = Loan::query()->first();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/approve")
            ->assertOk();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_fund_approved_loan(): void
    {
        $customer = $this->createUserWithRole('customer');
        $admin = $this->createUserWithRole('admin');

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/loans', [
                'amount' => 20000000,
                'term_months' => 8,
                'purpose' => 'treatment',
            ])
            ->assertStatus(201);

        $loan = Loan::query()->first();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/approve")
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/fund")
            ->assertOk();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'funded',
        ]);

        $this->assertDatabaseCount('loan_transactions', 1);
    }
}
