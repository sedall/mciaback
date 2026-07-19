<?php

namespace Modules\Customers\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Customers\Models\CustomerProfile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
        Role::findOrCreate('clinic');
        Role::findOrCreate('expert');
    }

    protected function createCustomerUser(array $attributes = []): User
    {
        $user = User::query()->create(array_merge([
            'mobile' => fake()->unique()->numerify('091########'),
            'password' => Hash::make('password'),
        ], $attributes));

        $user->assignRole('customer');

        return $user;
    }

    public function test_guest_cannot_access_profile_endpoints(): void
    {
        $this->getJson('/api/customer/profile')
            ->assertStatus(401);

        $this->postJson('/api/customer/profile', [])
            ->assertStatus(401);
    }

    public function test_non_customer_cannot_access_profile_endpoints(): void
    {
        $user = User::query()->create([
            'mobile' => '09120000001',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('admin');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/customer/profile')
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/customer/profile', [])
            ->assertStatus(403);
    }

    public function test_customer_can_get_own_profile(): void
    {
        $user = $this->createCustomerUser([
            'mobile' => '09120000002',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/customer/profile')
            ->assertOk();

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $user->id,
        ]);

        $profile = CustomerProfile::query()->where('user_id', $user->id)->firstOrFail();

        $response->assertJson([
            'data' => [
                'id' => $profile->id,
                'user_id' => $user->id,
                'first_name' => null,
                'last_name' => null,
                'father_name' => null,
                'national_code' => null,
                'birth_date' => null,
                'gender' => null,
                'province' => null,
                'city' => null,
                'address' => null,
                'postal_code' => null,
                'landline_phone' => null,
            ],
        ]);
    }

    public function test_customer_can_upsert_own_profile(): void
    {
        $user = $this->createCustomerUser([
            'mobile' => '09120000003',
        ]);

        $payload = [
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'father_name' => 'Reza',
            'national_code' => '1234567890',
            'birth_date' => '1995-01-20',
            'gender' => 'male',
            'province' => 'Tehran',
            'city' => 'Tehran',
            'address' => 'Sample address',
            'postal_code' => '1234567890',
            'landline_phone' => '09122345678',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/customer/profile', $payload)
            ->assertOk();

        $this->assertDatabaseHas('customer_profiles', array_merge($payload, [
            'birth_date' => '1995-01-20 00:00:00',
            'user_id' => $user->id,
        ]));

        $response->assertJson([
            'message' => 'پروفایل با موفقیت به‌روزرسانی شد.',
            'data' => array_merge($payload, [
                'user_id' => $user->id,
            ]),
        ]);
    }

    public function test_customer_upsert_updates_existing_profile_instead_of_creating_duplicate(): void
    {
        $user = $this->createCustomerUser([
            'mobile' => '09120000004',
        ]);

        $profile = CustomerProfile::query()->create([
            'user_id' => $user->id,
            'first_name' => 'Old',
            'national_code' => '1234567890',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/customer/profile', [
                'first_name' => 'New',
                'national_code' => '1234567890',
                'gender' => 'male',
            ])
            ->assertOk();

        $this->assertDatabaseCount('customer_profiles', 1);

        $this->assertDatabaseHas('customer_profiles', [
            'id' => $profile->id,
            'user_id' => $user->id,
            'first_name' => 'New',
            'national_code' => '1234567890',
            'gender' => 'male',
        ]);
    }

    public function test_customer_cannot_use_duplicate_national_code(): void
    {
        $firstUser = $this->createCustomerUser([
            'mobile' => '09120000005',
        ]);

        CustomerProfile::query()->create([
            'user_id' => $firstUser->id,
            'national_code' => '1234567890',
        ]);

        $secondUser = $this->createCustomerUser([
            'mobile' => '09120000006',
        ]);

        $this->actingAs($secondUser, 'sanctum')
            ->postJson('/api/customer/profile', [
                'national_code' => '1234567890',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['national_code']);
    }

    public function test_customer_can_keep_own_national_code_when_updating_profile(): void
    {
        $user = $this->createCustomerUser([
            'mobile' => '09120000007',
        ]);

        CustomerProfile::query()->create([
            'user_id' => $user->id,
            'national_code' => '1234567890',
            'first_name' => 'Ali',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/customer/profile', [
                'national_code' => '1234567890',
                'first_name' => 'Updated',
            ])
            ->assertOk();

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $user->id,
            'national_code' => '1234567890',
            'first_name' => 'Updated',
        ]);
    }

    public function test_profile_fields_are_validated(): void
    {
        $user = $this->createCustomerUser([
            'mobile' => '09120000008',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/customer/profile', [
                'first_name' => str_repeat('a', 101),
                'last_name' => str_repeat('b', 101),
                'father_name' => str_repeat('c', 101),
                'national_code' => '123',
                'postal_code' => '456',
                'birth_date' => 'invalid-date',
                'gender' => 'other',
                'landline_phone' => str_repeat('1', 21),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'father_name',
                'national_code',
                'postal_code',
                'birth_date',
                'gender',
                'landline_phone',
            ]);
    }
}
