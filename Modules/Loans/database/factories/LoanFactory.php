<?php

namespace Modules\Loans\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Loans\Models\Loan;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'clinic_id' => null,
            'principal_amount' => $this->faker->numberBetween(10000000, 500000000),
            'interest_rate' => 0,
            'term_months' => $this->faker->randomElement([3, 6, 12]),
            'monthly_installment' => null,
            'status' => Loan::STATUS_PENDING,
            'requested_at' => now(),
            'meta' => null,
        ];
    }
}
