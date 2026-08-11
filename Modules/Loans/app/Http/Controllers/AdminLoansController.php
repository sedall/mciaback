<?php

namespace Modules\Loans\Http\Controllers;

use Modules\Loans\Models\Loan;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Loans\Models\Installment;
use Modules\Loans\Services\LoanService;
use Illuminate\Validation\ValidationException;
use Modules\Loans\Http\Requests\FundLoanRequest;
use Modules\Loans\Http\Requests\RejectLoanRequest;
use Modules\Loans\Http\Requests\ApproveLoanRequest;
use Modules\Loans\Http\Requests\RepayInstallmentRequest;

class AdminLoansController extends Controller
{
    public function __construct(
        protected LoanService $loanService
    ) {
    }

    public function index(): JsonResponse
    {
        $loans = Loan::query()
            ->latest('id')
            ->paginate(15);

        return response()->json($loans);
    }

    public function show(Loan $loan): JsonResponse
    {

        $loan->load([
            'customer',
        //    'clinic',
            'installments',
            'transactions',
        ]);
        return response()->json([
            'data' => $loan,
        ]);
    }

    public function installments(Loan $loan): JsonResponse
    {
        return response()->json([
            'data' => $loan->installments()->orderBy('sequence')->get(),
        ]);
    }

    public function approve(ApproveLoanRequest $request, Loan $loan): JsonResponse
    {
        $data = $request->validated();
        $loan = $this->loanService->approveLoan($loan, $data);

        return response()->json([
            'message' => 'Loan has been approved successfully',
            'data' => $loan,
        ]);
    }

    public function fund(FundLoanRequest $request, Loan $loan): JsonResponse
    {
        $data = $request->validated();
        $loan = $this->loanService->fundLoan($loan, $data);

        return response()->json([ 'data' => $loan->fresh()]);
    }

    public function reject(RejectLoanRequest $request, Loan $loan): JsonResponse
    {
        $data = $request->validated();
        $loan = $this->loanService->rejectLoan($loan, $data);

        return response()->json([
            'message' => 'Loan rejected successfully.',
            'data' => $loan,
        ]);
    }

    public function repay(RepayInstallmentRequest $request, Loan $loan, Installment $installment) {
        $validated = $request->validated();
        $loan = $this->loanService->repayInstallment(
            $loan,
            (int) $installment->id,
            (int) $validated['amount'],
            $validated['reference'] ?? null
        );
        return response()->json([
            'data' => $loan,
        ]);
    }
}
