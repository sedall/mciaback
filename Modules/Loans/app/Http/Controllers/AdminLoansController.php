<?php

namespace Modules\Loans\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Modules\Loans\Http\Requests\ApproveLoanRequest;
use Modules\Loans\Http\Requests\FundLoanRequest;
use Modules\Loans\Http\Requests\RejectLoanRequest;
use Modules\Loans\Models\Loan;
use Modules\Loans\Services\LoanService;

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

    /**
     * @throws ValidationException
     */
    public function approve(ApproveLoanRequest $request, Loan $loan): JsonResponse
    {
        $loan = $this->loanService->approveLoan(
            $loan,
            $request->input('admin_note')
        );

        return response()->json([ 'data' => $loan->fresh()]);
    }

    public function fund(FundLoanRequest $request, Loan $loan): JsonResponse
    {
        $loan = $this->loanService->fundLoan(
            $loan,
            $request->only(['amount', 'date', 'reference'])
        );

        return response()->json([ 'data' => $loan->fresh()]);
    }

    public function reject(RejectLoanRequest $request, Loan $loan): JsonResponse
    {
        $reason = $request->input('reason', $request->input('admin_note'));
        $loan = $this->loanService->rejectLoan($loan, $reason);

        return response()->json([ 'data' => $loan->fresh()]);
    }
}
