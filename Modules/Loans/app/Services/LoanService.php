<?php

namespace Modules\Loans\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Loans\Models\Loan;
use Modules\Loans\Models\Installment;
use Modules\Loans\Models\LoanTransaction;
use RuntimeException;

class LoanService
{
    public function __construct(
        protected InstallmentGenerator $installmentGenerator
    ) {}

    public function createLoan(int $customerId, int $amount, int $tenureMonths): Loan
    {
        $this->assertMvpRules($amount, $tenureMonths);

        $feeAmount = (int) round($amount * 0.04); // 4%
        $totalPayable = $amount + $feeAmount;

        return Loan::create([
            'customer_id' => $customerId,
            'amount' => $amount,
            'fee_amount' => $feeAmount,
            'total_payable' => $totalPayable,
            'tenure_months' => $tenureMonths,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function approve(Loan $loan, int $adminId, ?string $note = null): Loan
    {
        if (!in_array($loan->status, ['submitted', 'under_review'], true)) {
            throw new RuntimeException('Loan is not in approvable state.');
        }

        $loan->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $adminId,
            'admin_note' => $note,
        ]);

        return $loan->refresh();
    }

    public function reject(Loan $loan, int $adminId, string $reason): Loan
    {
        if (!in_array($loan->status, ['submitted', 'under_review', 'approved'], true)) {
            throw new RuntimeException('Loan is not in rejectable state.');
        }

        $loan->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $adminId,
            'rejection_reason' => $reason,
        ]);

        return $loan->refresh();
    }

    public function fund(Loan $loan, int $actorId): Loan
    {
        if ($loan->status !== 'approved') {
            throw new RuntimeException('Only approved loan can be funded.');
        }

        return DB::transaction(function () use ($loan, $actorId) {
            $loan->refresh();

            if ($loan->status !== 'approved') {
                throw new RuntimeException('Loan status changed. Try again.');
            }

            $fundedAt = Carbon::now();

            $loan->update([
                'status' => 'funded',
                'funded_at' => $fundedAt,
            ]);

            $schedule = $this->installmentGenerator->generate(
                principalAmount: (int) $loan->amount,
                feeAmount: (int) $loan->fee_amount,
                tenureMonths: (int) $loan->tenure_months,
                fundedAt: $fundedAt
            );

            foreach ($schedule as $row) {
                Installment::create([
                    'loan_id' => $loan->id,
                    ...$row,
                ]);
            }

            LoanTransaction::create([
                'loan_id' => $loan->id,
                'type' => 'fund',
                'amount' => (int) $loan->amount,
                'meta' => ['actor_id' => $actorId],
                'transacted_at' => now(),
            ]);

            return $loan->refresh();
        });
    }

    protected function assertMvpRules(int $amount, int $tenureMonths): void
    {
        if ($amount < 10_000_000 || $amount > 500_000_000) {
            throw new RuntimeException('Amount must be between 10,000,000 and 500,000,000 IRR.');
        }

        if (!in_array($tenureMonths, [3, 6, 12], true)) {
            throw new RuntimeException('Tenure must be one of 3, 6, 12 months.');
        }
    }
}
