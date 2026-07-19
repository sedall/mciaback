<?php

namespace Modules\Loans\Http\Controllers;

use App\Http\Controllers\Controller;
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
            'clinic',
            'installments',
            'transactions',
        ]);

        return response()->json([
            'data' => $loan,
        ]);
    }

    public function approve(Loan $loan): JsonResponse
    {
        $loan = $this->loanService->approve(
            loan: $loan,
            adminId: (int) auth()->id()
        );

        return response()->json([
            'message' => 'Loan approved successfully.',
            'data' => $loan,
        ], 200);
    }

    public function fund(Loan $loan): JsonResponse
    {
        $loan = $this->loanService->fund(
            loan: $loan,
            actorId: (int) auth()->id()
        );

        return response()->json([
            'message' => 'Loan funded successfully.',
            'data' => $loan,
        ], 200);
    }

    public function reject(RejectLoanRequest $request, Loan $loan): JsonResponse
    {
        $loan = $this->loanService->reject(
            loan: $loan,
            adminId: auth()->id(),
            reason: $request->input('reason')
        );

        return response()->json([
            'data' => $loan,
        ]);
    }
}
