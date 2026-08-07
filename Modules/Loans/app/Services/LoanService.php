<?php

namespace Modules\Loans\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Customers\Services\KycStatusService;
use Modules\Loans\Models\Loan;

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
        $this->assertKycApproved($customerId);
        $this->assertMvpRules($amount, $tenureMonths);

        $feeAmount = (int) round($amount * 0.04);
        $totalPayable = $amount + $feeAmount;

        return Loan::query()->create([
            'customer_id' => $customerId,
            'principal_amount' => $amount,
            'fee_amount' => $feeAmount,
            'total_payable' => $totalPayable,
            'installments_count' => $tenureMonths,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }


    public function approveLoan(Loan $loan, ?string $adminNote = null): Loan
    {
        if (! in_array($loan->status, ['submitted', 'under_review'], true)) {
            throw ValidationException::withMessages([
                'loan' => ['Loan is not in approvable state.'],
            ]);
        }

        $loan->update([
            'status' => 'approved',
            'admin_note' => $adminNote,
            'approved_at' => now(),
        ]);

        return $loan->refresh();
    }

    public function rejectLoan(Loan $loan, ?string $adminNote = null): Loan
    {
        if (! in_array($loan->status, ['submitted', 'under_review', 'approved'], true)) {
            throw ValidationException::withMessages([
                'loan' => ['Loan is not in rejectable state.'],
            ]);
        }

        $loan->update([
            'status' => 'rejected',
            'admin_note' => $adminNote,
            'rejected_at' => now(),
        ]);

        return $loan->refresh();
    }

    public function fundLoan(Loan $loan, array $data): Loan
    {
        if ($loan->status !== 'approved') {
            throw ValidationException::withMessages([
                'loan' => ['Loan is not in fundable state.'],
            ]);
        }

        $loan->update([
            'status' => 'funded',
            'funded_at' => now(),
        ]);

        return $loan->refresh();
    }


    protected function assertMvpRules(int $amount, int $tenureMonths): void
    {
        $errors = [];

        if ($amount < 10_000_000 || $amount > 500_000_000) {
            $errors['amount'] = ['Amount must be between 10,000,000 and 500,000,000 IRR.'];
        }

        if (! in_array($tenureMonths, [3, 6, 12], true)) {
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
                'kyc' => ['KYC is not approved. Current status: ' . $kycStatus],
            ]);
        }
    }
}
