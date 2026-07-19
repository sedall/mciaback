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
        $principalBase = intdiv($principalAmount, $tenureMonths);
        $principalRemainder = $principalAmount % $tenureMonths;

        $feeBase = intdiv($feeAmount, $tenureMonths);
        $feeRemainder = $feeAmount % $tenureMonths;

        $items = [];

        for ($i = 1; $i <= $tenureMonths; $i++) {
            $principalPart = $principalBase + ($i === $tenureMonths ? $principalRemainder : 0);
            $feePart = $feeBase + ($i === $tenureMonths ? $feeRemainder : 0);

            $dueDate = $fundedAt->copy()
                ->addDays(30)
                ->addMonthsNoOverflow($i - 1);

            $items[] = [
                'sequence' => $i,
                'due_date' => $dueDate->toDateString(),
                'principal_amount' => $principalPart,
                'fee_amount' => $feePart,
                'late_fee_amount' => 0,
                'paid_amount' => 0,
                'status' => 'pending',
            ];
        }

        return $items;
    }
}
