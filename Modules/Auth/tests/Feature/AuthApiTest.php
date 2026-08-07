<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Auth\Models\OtpCode;
use Modules\Access\Database\Seeders\RoleSeeder;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_customer_can_request_otp_with_valid_mobile(): void
    {
        $response = $this->postJson('/api/customer/auth/request-otp', [
            'mobile' => '09123456789',
            'purpose' => 'login',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('otp_codes', [
            'mobile' => '09123456789',
        ]);
    }

    public function test_request_otp_validates_mobile_format(): void
    {
        $response = $this->postJson('/api/customer/auth/request-otp', [
            'mobile' => '9123456789',
            'purpose' => 'login',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mobile']);
    }

    public function test_customer_can_verify_otp_and_receive_token_and_user(): void
    {
        $otp = OtpCode::create([
            'mobile' => '09120000002',
            'code' => '123456',
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->postJson('/api/customer/verify-otp', [
            'mobile' => '09120000002',
            'code' => '123456',
        ]);
        $response
            ->assertOk()
            ->assertJsonPath('message', 'OTP verified successfully.')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'mobile',
                        'panel',
                        'primary_role',
                        'roles',
                    ],
                ],
            ]);

    }

    public function test_verify_otp_fails_with_wrong_code(): void
    {
        $mobile = '09123456789';

        $this->postJson('/api/customer/auth/request-otp', [
            'mobile' => $mobile,
            'purpose' => 'login',
        ])->assertOk();

        $response = $this->postJson('/api/customer/auth/verify-otp', [
            'mobile' => $mobile,
            'code' => '000000',
            'purpose' => 'login',
        ]);

        $response->assertStatus(422);
    }

    public function test_verify_otp_fails_when_code_is_expired(): void
    {
        $mobile = '09123456789';

        $this->postJson('/api/customer/auth/request-otp', [
            'mobile' => $mobile,
            'purpose' => 'login',
        ])->assertOk();

        $otp = OtpCode::query()
            ->where('mobile', $mobile)
            ->latest('id')
            ->first();

        $this->assertNotNull($otp);

        Carbon::setTestNow(now()->addSeconds(121));

        $response = $this->postJson('/api/customer/auth/verify-otp', [
            'mobile' => $mobile,
            'code' => $otp->code,
            'purpose' => 'login',
        ]);

        $response->assertStatus(422);

        Carbon::setTestNow();
    }

    public function test_verify_otp_validates_code_length(): void
    {
        $response = $this->postJson('/api/customer/auth/verify-otp', [
            'mobile' => '09123456789',
            'code' => '123',
            'purpose' => 'login',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_customer_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/customer/me');

        $response->assertStatus(401);
    }

    public function test_customer_me_returns_authenticated_user_for_correct_panel(): void
    {
        $user = User::factory()->create([
            'mobile' => '09123456789',
            'mobile_verified_at' => now(),
        ]);

        $user->assignRole('customer');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/customer/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'mobile',
                    ],
                ],
            ]);
    }

    public function test_customer_me_forbids_user_with_wrong_panel_role(): void
    {
        $user = User::factory()->create([
            'mobile' => '09123456789',
            'mobile_verified_at' => now(),
        ]);

        $user->assignRole('clinic');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/customer/me');

        $response->assertStatus(403);
    }

    public function test_admin_me_allows_admin_role(): void
    {
        $user = User::factory()->create([
            'mobile' => '09126734589',
            'mobile_verified_at' => now(),
        ]);

        $user->assignRole('admin');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'mobile',
                    ],
                ],
            ]);
    }

    public function test_admin_me_allows_admin_role_against_panel_access_check(): void
    {
        $user = User::factory()->create([
            'mobile' => '09123456789',
            'mobile_verified_at' => now(),
        ]);

        $user->assignRole('admin');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/me');

        $response->assertOk();
    }
}
