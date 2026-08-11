<?php

namespace Modules\Loans\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Modules\Customers\Models\CustomerProfile;
use Modules\Customers\Services\KycStatusService;
use Modules\Loans\Models\Loan;
use Tests\TestCase;

class LoanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_loan_request(): void
    {
        $customer = User::factory()->create();

        CustomerProfile::forceCreate([
            'user_id' => $customer->id,
        ]);

        $this->app->instance(
            KycStatusService::class,
            Mockery::mock(KycStatusService::class, function ($mock) use ($customer) {
                $mock->shouldReceive('getKycStatus')->with($customer)->andReturn('approved');
            })
        );

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/loans', [
            'principal_amount' => 10000000,
            'term_months' => 3,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('loans', [
            'customer_id' => $customer->id,
            'principal_amount' => 10000000,
            'term_months' => 3,
            'status' => Loan::STATUS_PENDING,
        ]);
    }
}
