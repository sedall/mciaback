<?php

namespace Modules\Loans\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Loans\Models\Loan;
use App\Models\User;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'clinic_id' => null,
            'principal_amount' => 10_000_000,
            'fee_amount' => 400_000,
            'total_payable' => 10_400_000,
            'installments_count' => 6,
            'status' => 'submitted',
            'submitted_at' => now(),
        ];
    }
}
