<?php

namespace Modules\Loans\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Loans\Models\Installment;
use Modules\Loans\Models\Loan;

class InstallmentFactory extends Factory
{
    protected $model = Installment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'due_date' => now()->addMonth(),
            'fee_amount' => 1000000,
            'paid_amount' => 0,
            'status' => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'paid_amount' => 550000,
            'status' => 'paid',
        ]);
    }
}
