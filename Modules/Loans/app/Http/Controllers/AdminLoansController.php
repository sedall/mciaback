<?php

namespace Modules\Loans\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

    public function approve(ApproveLoanRequest $request, Loan $loan): JsonResponse
    {

        $loan = $this->loanService->approveLoan(
            $loan,
            $request->get('term'),
            auth()->id()
        );
        return response()->json([
            'message' => 'Loan has been approved successfully',
            'data' => $loan,
        ]);

    }

    public function fund(FundLoanRequest $request, Loan $loan): JsonResponse
    {

        $loan = $this->loanService->fundLoan(
            $loan,
            auth()->id(),
        );

        return response()->json([
            'message' => 'Loan funded successfully.',
            'data' => $loan,
        ]);
    }

    public function reject(RejectLoanRequest $request, Loan $loan): JsonResponse
    {
        $loan = $this->loanService->rejectLoan(
            $loan,
            $request->get('reason')
        );

        return response()->json([
            'message' => 'Loan rejected successfully.',
            'data' => $loan,
        ]);
    }
}
