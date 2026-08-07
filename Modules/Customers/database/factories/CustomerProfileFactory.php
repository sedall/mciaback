<?php

namespace Modules\Customers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Customers\Models\CustomerProfile;

class CustomerProfileFactory extends Factory
{
    protected $model = CustomerProfile::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'father_name' => $this->faker->name,
            'national_code' => $this->faker->numerify('##########'),
            'birth_date' => '1995-01-01',
            'gender' => 'male',
            'province' => 'Tehran',
            'city' => 'Tehran',
            'address' => $this->faker->address,
            'postal_code' => $this->faker->numerify('##########'),
            'landline_phone' => $this->faker->phoneNumber,
        ];
    }
}
