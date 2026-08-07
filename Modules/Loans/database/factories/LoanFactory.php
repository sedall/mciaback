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
            'principal_amount' => $this->faker->numberBetween(10000, 100000),
            'fee_amount' => $this->faker->numberBetween(0, 1000),
            'total_payable' => $this->faker->numberBetween(10000, 100000),
            'installments_count' => $this->faker->randomElement([3, 6, 12]),
            'status' => 'submitted',
            'submitted_at' => now(),
        ];
    }

    public function approved(): Factory
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function rejected(): Factory
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'rejected_at' => now(),
            'admin_note' => $this->faker->sentence(),
        ]);
    }

    public function funded(): Factory
    {
        return $this->state(fn () => [
            'status' => 'funded',
            'funded_at' => now(),
        ]);
    }

    public function active(): Factory
    {
        return $this->state(fn () => [
            'status' => 'active',
            'funded_at' => now(),
        ]);
    }

    public function closed(): Factory
    {
        return $this->state(fn () => [
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }
}
