<?php

namespace Modules\Loans\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Customers\Services\KycStatusService;
use Modules\Loans\Models\Loan;
use RuntimeException;

class LoanService
{
    public function __construct(
        protected InstallmentGenerator $installmentGenerator,
        protected KycStatusService $kycStatusService
    ) {
    }

    public function createLoan(
        int $customerId,
        int $amount,
        int $tenureMonths
    ): Loan {
        // 1) KYC gate (business-critical)
        $this->assertKycApproved($customerId);

        // 2) Product rules
        $this->assertMvpRules($amount, $tenureMonths);

        $feeAmount = (int) round($amount * 0.04);
        $totalPayable = $amount + $feeAmount;

        return Loan::query()->create([
            'customer_id' => $customerId,
            'principal_amount' => $amount,
            'fee_amount' => $feeAmount,
            'total_payable' => $totalPayable,
            'installments_count' => $tenureMonths,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function approveLoan(Loan $loan): Loan
    {
        if (!in_array($loan->status, ['submitted', 'under_review'], true)) {
            throw new RuntimeException('Loan is not in approvable state.');
        }

        $loan->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return $loan->refresh();
    }

    public function rejectLoan(Loan $loan, string $reason): Loan
    {
        if (!in_array($loan->status, ['submitted', 'under_review', 'approved'], true)) {
            throw new RuntimeException('Loan is not in rejectable state.');
        }

        $loan->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $loan->refresh();
    }

    public function fundLoan(Loan $loan, int $actorId): Loan
    {
        return DB::transaction(function () use ($loan, $actorId) {
            $loan = Loan::query()
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($loan->status !== 'approved') {
                throw new RuntimeException('Only approved loan can be funded.');
            }

            $fundedAt = now();

            $loan->update([
                'status' => 'funded',
                'funded_at' => $fundedAt,
            ]);

            $loan->transactions()->create([
                'type' => 'disbursement',
                'amount' => (int) $loan->principal_amount,
                'performed_by' => $actorId,
                'transacted_at' => $fundedAt,
                'meta' => null,
            ]);

            $installments = $this->installmentGenerator->generate(
                principalAmount: (int) $loan->principal_amount,
                feeAmount: (int) $loan->fee_amount,
                tenureMonths: (int) $loan->installments_count,
                fundedAt: $fundedAt,
            );

            $loan->installments()->createMany($installments);

            return $loan->refresh();
        });
    }

    protected function assertMvpRules(int $amount, int $tenureMonths): void
    {
        $errors = [];

        // بهتره بعداً از config بخونی، فعلاً hard-coded قابل قبول MVP
        if ($amount < 10_000_000 || $amount > 500_000_000) {
            $errors['amount'] = ['Amount must be between 10,000,000 and 500,000,000 IRR.'];
        }

        if (!in_array($tenureMonths, [3, 6, 12], true)) {
            $errors['tenure_months'] = ['Tenure must be one of 3, 6, 12 months.'];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function assertKycApproved(int $customerId): void
    {
        $user = User::find($customerId);
        if (! $user) {
            throw ValidationException::withMessages([
                'customer_id' => ['Customer not found.'],
            ]);
        }

        $kycStatus = $this->kycStatusService->getKycStatus($user);
        if ($kycStatus !== 'approved') {
            throw ValidationException::withMessages([
                'kyc' => ['KYC is not approved. Current status:' . $kycStatus],
            ]);
        }
    }

}
