<?php

namespace Modules\Loans\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\CustomerDocuments\Models\CustomerDocument;
use Modules\Customers\Models\CustomerProfile;
use Modules\Loans\Database\Factories\LoanFactory;
use Modules\Loans\Models\Loan;
use Tests\TestCase;

class LoanApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Access\Database\Seeders\RoleSeeder::class);
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
        // 1. ساخت کاربر و نقش‌دهی
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        // 2. تکمیل پروفایل مشتری با استفاده از ستون‌های صحیح
        CustomerProfile::create([
            'user_id'              => $customer->id,
            'first_name'           => 'Test',
            'last_name'            => 'User',
            'phone'                => '09123456789',
            'national_code'        => '1234567890',
            'is_profile_completed' => true,
        ]);

        // 3. ایجاد یک سند تأییدشده
        CustomerDocument::create([
            'user_id' => $customer->id,
            'type'        => 'id_card',
            'status'      => 'approved',
            'file_path'        => 'dummy.pdf',
        ]);

        // 4. ارسال درخواست وام
        $payload = [
            'amount'        => 50_000_000,
            'tenure_months' => 12,
        ];

        $response = $this
            ->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/loans', $payload);
        $response->assertCreated();
        // 5. assertions
                $payload = [
                    'amount' => 20000000,
                    'tenure_months' => 12,
                ];

                        $response = $this->actingAs($customer, 'sanctum')
                            ->postJson('/api/customer/loans', $payload);
                        $response->assertStatus(422)
                            ->assertJsonValidationErrors(['kyc']);

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

        $loan = LoanFactory::new()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/approve", []);

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
            ->patchJson("/api/admin/loans/{$loan->id}/fund", []);

        $response->assertOk()
            ->assertJsonPath('data.status', 'funded');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'funded',
        ]);
    }

    public function test_admin_can_reject_pending_loan(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $loan = LoanFactory::new()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/loans/{$loan->id}/reject", [
                'reason' => 'documents incomplete',
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
}
