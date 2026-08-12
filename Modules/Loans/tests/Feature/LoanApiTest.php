<?php

namespace Modules\Loans\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Access\Database\Seeders\RoleSeeder;
use Modules\CustomerDocuments\Models\CustomerDocument;
use Modules\Customers\Models\CustomerProfile;
use Modules\Loans\Database\Factories\LoanFactory;
use Laravel\Sanctum\Sanctum;
use Modules\Loans\Models\Installment;
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

    public function test_customer_can_list_own_loans(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        LoanFactory::new()->count(2)->create(['customer_id' => $customer->id]);
        LoanFactory::new()->count(1)->create(['customer_id' => $otherCustomer->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/customer/loans');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_customer_can_create_loan_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        // استفاده از Factory که ساختیم
        \Modules\Customers\Models\CustomerProfile::factory()->create([
            'user_id' => $customer->id,
        ]);

        // ایجاد ۴ سند اجباری
        $requiredDocs = ['national_card_front', 'national_card_back', 'certificate_of_residence', 'employment_certificate'];
        foreach ($requiredDocs as $type) {
            CustomerDocument::create([
                'user_id' => $customer->id,
                'type' => $type, // در دیتابیس شما نوع مدرک در فیلد type است
                'file_path' => 'documents/test.jpg',
                'status' => 'approved',
            ]);
        }

        $payload = ['amount' => 50000000, 'tenure_months' => 12];

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/loans', $payload);

        $response->assertCreated();
    }

    public function test_admin_can_list_loans(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        LoanFactory::new()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/loans');

        $response->assertOk();
    }

    public function test_admin_can_view_single_loan(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $loan = LoanFactory::new()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/loans/{$loan->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $loan->id);
    }

    public function test_admin_can_approve_loan(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $loan = LoanFactory::new()->create([
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/approve", [
                'approved_amount' => 50000000,
                'approved_term_months' => 12,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_fund_approved_loan(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $loan = LoanFactory::new()->create([
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/fund", [
                'amount' => 50000000,
                'date' => '2026-07-20',
                'reference' => 'REF123456',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('loan_transactions', [
            'loan_id' => $loan->id,
            'type' => 'funding',
            'amount' => 50000000,
            'performed_by' => $admin->id,
        ]);

        $this->assertDatabaseCount('installments', $loan->installments_count);

    }

    public function test_admin_can_reject_pending_loan(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $loan = LoanFactory::new()->create([
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/reject", [
                'reason' => 'customer is not eligible',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'rejected',
        ]);
    }

    public function test_customer_cannot_access_admin_loan_routes(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $loan = LoanFactory::new()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("/api/admin/loans/{$loan->id}");

        $response->assertForbidden();
    }

    public function test_customer_cannot_create_loan_when_kyc_is_profile_incomplete(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        // ایزوله‌سازی وضعیت KYC برای این کاربر
        DB::table('customer_documents')->where('user_id', $user->id)->delete();
        DB::table('customer_profiles')->where('user_id', $user->id)->delete();

        // عمداً پروفایل نمی‌سازیم => profile_incomplete

        $payload = [
            'amount' => 20000000,
            'tenure_months' => 12,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/customer/loans', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['kyc']);
    }

    public function test_customer_cannot_create_loan_when_kyc_documents_missing(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        // ایزوله‌سازی وضعیت KYC برای این کاربر
        DB::table('customer_documents')->where('user_id', $user->id)->delete();
        DB::table('customer_profiles')->where('user_id', $user->id)->delete();

        // پروفایل کامل
        CustomerProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name'    => 'Ali',
                'last_name'     => 'Ahmadi',
                'father_name'   => 'Reza',
                'national_code' => '1234527490',
                'birth_date'    => '1995-01-01',
                'gender'        => 'male',
                'province'      => 'Tehran',
                'city'          => 'Tehran',
                'address'       => 'Some address',
                'postal_code'   => '1234567890',
            ]
        );

        // عمداً هیچ مدرکی ثبت نمی‌کنیم => documents_missing

        $payload = [
            'amount' => 20000000,
            'tenure_months' => 12,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/customer/loans', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['kyc']);
    }

    public function test_clinic_cannot_access_customer_loan_routes(): void
    {
        $clinic = User::factory()->create();
        $clinic->assignRole('clinic');

        Sanctum::actingAs($clinic);

        $this->getJson('/api/customer/loans')->assertForbidden();
    }

    public function test_customer_can_create_loan_if_kyc_approved(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        CustomerProfile::factory()->create([
            'user_id' => $customer->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'father_name' => 'Richard',
            'national_code' => '1234567890',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'province' => 'Tehran',
            'city' => 'Tehran',
            'address' => 'Sample Address',
            'postal_code' => '1234567890',
        ]);
        foreach ([
                     'national_card_front',
                     'national_card_back',
                     'certificate_of_residence',
                     'employment_certificate',
                 ] as $type) {
            CustomerDocument::create([
                'user_id' => $customer->id,
                'type' => $type,
                'file_path' => "documents/{$type}.jpg",
                'status' => 'approved',
            ]);
        }

        $response = $this->actingAs($customer)->postJson('/api/customer/loans', [
            'amount' => 10000000,
            'tenure_months' => 12,
        ]);
        $response->assertCreated();
    }

    public function test_customer_cannot_create_loan_if_required_document_missing(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        CustomerProfile::factory()->create([
            'user_id' => $customer->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'father_name' => 'Richard',
            'national_code' => '1234567890',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'province' => 'Tehran',
            'city' => 'Tehran',
            'address' => 'Sample Address',
            'postal_code' => '1234567890',
        ]);

        CustomerDocument::create([
            'user_id' => $customer->id,
            'type' => 'national_card_front',
            'file_path' => 'documents/national_card_front.jpg',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($customer)->postJson('/api/customer/loans', [
            'amount' => 200000000,
            'tenure_months' => 12,
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('loans', [
            'customer_id' => $customer->id,
        ]);
    }

    public function test_admin_can_fund_approved_loan_and_generate_installments(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $loan = Loan::factory()->create([
            'status' => Loan::STATUS_APPROVED,
            'approved_amount' => 12000000,
            'approved_term_months' => 6,
            'installments_count' => 6,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/fund", [
                'reference_number' => 'FUND-1001',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseCount('installments', 6);

        $this->assertDatabaseHas('loan_transactions', [
            'loan_id' => $loan->id,
            'type' => 'funding',
        ]);
    }

    public function test_customer_can_repay_installment_and_status_becomes_paid_or_partial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $loan = Loan::factory()->create([
            'customer_id' => $user->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $installment = Installment::query()->create([
            'loan_id' => $loan->id,
            'sequence' => 1,
            'principal_amount' => 1000000,
            'fee_amount' => 100000,
            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addMonth(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/customer/loans/{$loan->id}/installments/{$installment->id}/repay", [
                'amount' => 1100000,
                'reference' => 'PAY-001',
            ])
            ->assertOk();

        $installment->refresh();

        $this->assertEquals(1100000, $installment->paid_amount);
        $this->assertEquals('paid', $installment->status);

        $this->assertDatabaseHas('loan_transactions', [
            'loan_id' => $loan->id,
            'type' => 'repayment',
            'amount' => 1100000,
            'meta->reference' => 'PAY-001',
        ]);
    }

    public function test_loan_becomes_completed_when_all_installments_are_paid(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $loan = Loan::factory()->create([
            'customer_id' => $user->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $first = Installment::query()->create([
            'loan_id' => $loan->id,
            'sequence' => 1,
            'principal_amount' => 500000,
            'fee_amount' => 50000,
            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addMonth(),
        ]);

        $second = Installment::query()->create([
            'loan_id' => $loan->id,
            'sequence' => 2,
            'principal_amount' => 500000,
            'fee_amount' => 50000,

            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addMonths(2),
        ]);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/customer/loans/{$loan->id}/installments/{$first->id}/repay", [
                'amount' => 550000,
            ]);

        $response->assertOk();


        $this->actingAs($user, 'sanctum')
            ->postJson("/api/customer/loans/{$loan->id}/installments/{$second->id}/repay", [
                'amount' => 550000,
            ])
            ->assertOk();

        $loan->refresh();

        $this->assertEquals(Loan::STATUS_COMPLETED, $loan->status);
    }

    public function test_admin_can_fund_approved_loan_and_installments_are_generated(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $loan = Loan::factory()->create([
            'status' => Loan::STATUS_APPROVED,
            'principal_amount' => 12000000,
            'fee_amount' => 1200000,
            'approved_term_months' => 6,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/fund", [
                'amount' => 12000000,
                'reference' => 'FUND-001',
            ])
            ->assertOk();

        $loan->refresh();

        $this->assertEquals(Loan::STATUS_ACTIVE, $loan->status);
        $this->assertDatabaseHas('loan_transactions', [
            'loan_id' => $loan->id,
            'type' => 'funding',
            'amount' => 12000000,
        ]);

        $this->assertDatabaseCount('installments', $loan->installments_count);
        $this->assertDatabaseHas('installments', [
            'loan_id' => $loan->id,
            'sequence' => 1,
        ]);
    }
    public function test_user_can_partially_repay_installment(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $loan = Loan::factory()->create([
            'customer_id' => $customer->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $installment = Installment::factory()->create([
            'loan_id' => $loan->id,
            'sequence' => 1,
            'status' => Installment::STATUS_PENDING,
            'principal_amount' => 1000,
            'fee_amount' => 100,
            'paid_amount' => 0,
            'due_date' => now()->addDay(),
        ]);

        $this->actingAs($customer);

        $response = $this->postJson("/api/customer/loans/{$loan->id}/installments/{$installment->id}/repay", [
            'amount' => 500,
            'reference' => 'partial_payment',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('installments', [
            'id' => $installment->id,
            'paid_amount' => 500,
            'status' => Installment::STATUS_PARTIAL,
        ]);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('loan_transactions', [
            'loan_id' => $loan->id,
            'type' => 'repayment',
            'amount' => 500,
        ]);

        $this->assertDatabaseHas('loan_transactions', [
            'loan_id' => $loan->id,
            'type' => 'repayment',
            'meta->reference' =>'partial_payment',

        ]);
    }

    public function test_user_cannot_overpay_installment(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $loan = Loan::factory()->create([
            'customer_id' => $customer->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $installment = Installment::factory()->create([
            'loan_id' => $loan->id,
            'sequence' => 1,
            'status' => Installment::STATUS_PENDING,
            'principal_amount' => 1000,
            'fee_amount' => 100,
            'paid_amount' => 0,
            'due_date' => now()->addDay(),
        ]);

        $this->actingAs($customer);

        $response = $this->postJson("/api/customer/loans/{$loan->id}/installments/{$installment->id}/repay", [
            'amount' => 1200,
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_repay_with_zero_amount(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $loan = Loan::factory()->create([
            'customer_id' => $customer->id,
            'status' => Loan::STATUS_ACTIVE,
        ]);

        $installment = Installment::factory()->create([
            'loan_id' => $loan->id,
            'sequence' => 1,
            'status' => Installment::STATUS_PENDING,
            'principal_amount' => 1000,
            'fee_amount' => 100,
            'paid_amount' => 0,
            'due_date' => now()->addDay(),
        ]);

        $this->actingAs($customer);

        $response = $this->postJson("/api/customer/loans/{$loan->id}/installments/{$installment->id}/repay", [
            'amount' => 0,
        ]);

        $response->assertStatus(422);
    }




}
