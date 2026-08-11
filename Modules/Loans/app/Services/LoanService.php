<?php

namespace Modules\Loans\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Customers\Services\KycStatusService;
use Modules\Loans\Models\Installment;
use Modules\Loans\Models\Loan;

class LoanService
{
    public function __construct(
        protected InstallmentGenerator $installmentGenerator,
        protected KycStatusService $kycStatusService
    ) {
    }

    public function createLoan(int $customerId, int $amount, int $tenureMonths): Loan {
        $this->assertKycApproved($customerId);
        $this->assertMvpRules($amount, $tenureMonths);
        $this->ensureEligibility($customerId);
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

    public function ensureEligibility(int $customerId): void
    {
        $user = User::query()->findOrFail($customerId);

        $kycStatus = $this->kycStatusService->getKycStatus($user);

        if ($kycStatus !== 'approved') {
            throw ValidationException::withMessages([
                'kyc' => ['KYC is not approved. Current status: ' . $kycStatus],
            ]);
        }

        $hasOpenLoan = Loan::query()
            ->where('customer_id', $customerId)
            ->whereIn('status', [
                Loan::STATUS_PENDING,
                Loan::STATUS_SUBMITTED,
                Loan::STATUS_UNDER_REVIEW,
                Loan::STATUS_APPROVED,
                Loan::STATUS_FUNDED,
                Loan::STATUS_ACTIVE,
            ])
            ->exists();

        if ($hasOpenLoan) {
            throw ValidationException::withMessages([
                'loan' => 'customer_has_active_or_pending_loan',
            ]);
        }
    }

    public function approveLoan(Loan $loan, array $data): Loan
    {
        if (!in_array($loan->status, [
            Loan::STATUS_PENDING,
            Loan::STATUS_SUBMITTED,
            Loan::STATUS_UNDER_REVIEW,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'فقط وام‌های در انتظار بررسی قابل تایید هستند.',
            ]);
        }

        return DB::transaction(function () use ($loan, $data) {
            $loan->update([
                'status' => Loan::STATUS_APPROVED,
                'approved_amount' => $data['approved_amount'],
                'approved_term_months' => $data['approved_term_months'],
                'approved_installment_amount' => $data['approved_installment_amount'] ?? null,
                'admin_note' => $data['admin_note'] ?? null,
                'approved_at' => now(),
            ]);

            return $loan->fresh();
        });
    }

    public function rejectLoan(Loan $loan, array $data): Loan
    {
        if (!in_array($loan->status, [
            Loan::STATUS_PENDING,
            Loan::STATUS_SUBMITTED,
            Loan::STATUS_UNDER_REVIEW,
            Loan::STATUS_APPROVED,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'فقط وام‌های در انتظار بررسی قابل رد هستند.',
            ]);
        }

        return DB::transaction(function () use ($loan, $data) {
            $loan->update([
                'status' => Loan::STATUS_REJECTED,
                'admin_note' => $data['admin_note'] ?? null,
                'rejection_reason' => $data['reason'] ?? null,
                'rejected_at' => now(),
            ]);

            return $loan->fresh();
        });
    }

    public function fundLoan(Loan $loan, array $data): Loan
    {
        return DB::transaction(function () use ($loan, $data) {
            if ($loan->status !== Loan::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'status' => 'Only approved loans can be funded.',
                ]);
            }

            $fundedAt = !empty($data['date'])
                ? Carbon::parse($data['date'])
                : now();

            $tenureMonths = (int) ($loan->installments_count ?? 0);

            if ($tenureMonths <= 0) {
                throw ValidationException::withMessages([
                    'installments_count' => 'Loan installments count is invalid.',
                ]);
            }

            $installments = $this->installmentGenerator->generate(
                principalAmount: (int) $loan->principal_amount,
                feeAmount: (int) $loan->fee_amount,
                tenureMonths: $tenureMonths,
                fundedAt: $fundedAt,
            );

            foreach ($installments as $item) {
                $loan->installments()->create([
                    'sequence' => $item['sequence'],
                    'due_date' => $item['due_date'],
                    'principal_amount' => $item['principal_amount'],
                    'fee_amount' => $item['fee_amount'] ?? 0,
                    'late_fee_amount' => $item['late_fee_amount'] ?? 0,
                    'paid_amount' => 0,
                    'status' => 'pending',
                ]);
            }

            $loan->transactions()->create([
                'type' => 'funding',
                'amount' => (int) ($data['amount'] ?? $loan->total_payable),
                'performed_by' => auth()->id(),
                'meta' => [
                    'reference' => $data['reference'] ?? null,
                    'funded_at' => $fundedAt->toDateTimeString(),
                ],
                'transacted_at' => $fundedAt,
            ]);

            $loan->forceFill([
                'status' => Loan::STATUS_ACTIVE,
                'funded_at' => $fundedAt,
                'started_at' => $fundedAt,
            ])->save();

            return $loan->fresh(['installments', 'transactions']);
        });
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


    public function repayInstallment(Loan $loan, int $installmentId, int $amount, ?string $reference = null): Loan
    {
        return DB::transaction(function () use ($loan, $installmentId, $amount, $reference) {
            /** @var Installment $installment */
            $installment = $loan->installments()->lockForUpdate()->findOrFail($installmentId);

            $dueAmount = (int) $installment->principal_amount + (int) $installment->fee_amount;
            $newPaidAmount = (int) $installment->paid_amount + $amount;

            if ($amount <= 0) {
                abort(422, 'amount must be greater than zero');
            }

            if ($newPaidAmount > $dueAmount) {
                abort(422, 'repayment amount exceeds installment due amount');
            }

            $installment->update([
                'paid_amount' => $newPaidAmount,
                'status' => $newPaidAmount >= $dueAmount ? 'paid' : 'partial',
                'paid_at' => $newPaidAmount >= $dueAmount ? now() : null,
            ]);

            $loan->transactions()->create([
                'loan_id' => $loan->id,
                'type' => 'repayment',
                'amount' => $amount,
                'reference' => $reference,
                'transacted_at' => now(),
            ]);

            $hasUnpaidInstallment = $loan->installments()
                ->where('status', '!=', 'paid')
                ->exists();

            if (! $hasUnpaidInstallment) {
                $loan->update([
                    'status' => Loan::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
            }

            return $loan->fresh(['installments', 'transactions']);
        });
    }
}
