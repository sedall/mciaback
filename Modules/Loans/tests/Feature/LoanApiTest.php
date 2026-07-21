<?php

namespace Modules\Loans\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Access\Database\Seeders\RoleSeeder;
use Modules\Loans\Models\Loan;
use Tests\TestCase;

class LoanApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_customer_can_create_loan_request(): void
    {
        $this->withoutMiddleware();

        $customer = $this->createUser();

        $response = $this
            ->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/loans', [
                'amount' => 10_000_000,
                'tenure_months' => 3,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('loans', [
            'customer_id' => $customer->id,
            'principal_amount' => 10_000_000,
            'fee_amount' => 400_000,
            'total_payable' => 10_400_000,
            'installments_count' => 3,
            'status' => 'submitted',
        ]);
    }

    public function test_admin_can_approve_loan(): void
    {
        $this->withoutMiddleware();

        $admin = $this->createUser();
        $admin->assignRole('admin');

        $loan = Loan::query()->create([
            'customer_id' => $this->createUser()->id,
            'principal_amount' => 10_000_000,
            'fee_amount' => 400_000,
            'total_payable' => 10_400_000,
            'installments_count' => 3,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->assertSame('submitted', $loan->fresh()->status);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'submitted',
        ]);
        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/approve");

        $response->assertOk();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'approved',
        ]);

        $this->assertNotNull($loan->fresh()->approved_at);
    }

    public function test_admin_can_reject_loan(): void
    {
        $this->withoutMiddleware();

        $admin = $this->createUser();
        $admin->assignRole('admin');

        $loan = Loan::query()->create([
            'customer_id' => $this->createUser()->id,
            'principal_amount' => 10_000_000,
            'fee_amount' => 400_000,
            'total_payable' => 10_400_000,
            'installments_count' => 3,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'submitted',
        ]);
        $response = $this
            ->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/reject", [
                'reason' => 'Insufficient KYC score.',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'rejected',
            'rejection_reason' => 'Insufficient KYC score.',
        ]);

        $this->assertNotNull($loan->fresh()->rejected_at);
    }

    public function test_admin_can_fund_approved_loan(): void
    {
        $this->withoutMiddleware();

        $admin = $this->createUser();
        $admin->assignRole('admin');

        $loan = Loan::query()->create([
            'customer_id' => $this->createUser()->id,
            'principal_amount' => 12_000_000,
            'fee_amount' => 480_000,
            'total_payable' => 12_480_000,
            'installments_count' => 3,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'submitted',
        ]);
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/approve")
            ->assertOk();

        $this->assertSame('approved', $loan->fresh()->status);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'approved',
        ]);
        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/fund");

        $response->assertOk();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'funded',
        ]);

        $this->assertNotNull($loan->fresh()->funded_at);

        $this->assertDatabaseHas('loan_transactions', [
            'loan_id' => $loan->id,
            'type' => 'disbursement',
            'amount' => 12_000_000,
            'performed_by' => $admin->id,
        ]);

        $this->assertDatabaseCount('installments', 3);

        $this->assertDatabaseHas('installments', [
            'loan_id' => $loan->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('installments', [
            'loan_id' => $loan->id,
            'sequence' => 2,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('installments', [
            'loan_id' => $loan->id,
            'sequence' => 3,
            'status' => 'pending',
        ]);
    }

    public function test_customer_can_list_their_loans(): void
    {
        $this->withoutMiddleware();

        $customer = $this->createUser();
        $otherCustomer = $this->createUser();

        Loan::query()->create([
            'customer_id' => $customer->id,
            'principal_amount' => 10_000_000,
            'fee_amount' => 400_000,
            'total_payable' => 10_400_000,
            'installments_count' => 3,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        Loan::query()->create([
            'customer_id' => $otherCustomer->id,
            'principal_amount' => 20_000_000,
            'fee_amount' => 800_000,
            'total_payable' => 20_800_000,
            'installments_count' => 6,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $response = $this
            ->actingAs($customer, 'sanctum')
            ->getJson('/api/customer/loans');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        $response->assertJsonFragment([
            'customer_id' => $customer->id,
            'principal_amount' => 10_000_000,
        ]);

        $response->assertJsonMissing([
            'customer_id' => $otherCustomer->id,
            'principal_amount' => 20_000_000,
        ]);
    }

    protected function createUser()
    {
        $userModel = config('auth.providers.users.model');

        return $userModel::factory()->create();
    }
}
