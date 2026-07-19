<?php

namespace Modules\Loans\Services;

use Carbon\Carbon;

class InstallmentGenerator
{
    public function generate(
        int $principalAmount,
        int $feeAmount,
        int $tenureMonths,
        Carbon $fundedAt
    ): array {
        $totalPayable = $principalAmount + $feeAmount;

        $base = intdiv($totalPayable, $tenureMonths);
        $remainder = $totalPayable % $tenureMonths;

        $items = [];
        for ($i = 1; $i <= $tenureMonths; $i++) {
            $amount = $base + ($i === $tenureMonths ? $remainder : 0);

            $dueDate = $fundedAt->copy()->addDays(30)->addMonthsNoOverflow($i - 1);

            $items[] = [
                'installment_no' => $i,
                'amount' => $amount,
                'principal_component' => null, // فعلاً MVP
                'fee_component' => null,       // فعلاً MVP
                'penalty_amount' => 0,
                'status' => 'pending',
                'due_date' => $dueDate->toDateString(),
            ];
        }

        return $items;
    }
}
