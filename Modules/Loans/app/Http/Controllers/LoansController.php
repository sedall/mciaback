<?php

namespace Modules\Loans\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Loans\Http\Requests\StoreLoanRequest;
use Modules\Loans\Models\Loan;
use Modules\Loans\Services\LoanService;

class LoansController extends Controller
{
    public function __construct(
        protected LoanService $loanService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $loans = Loan::query()
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->paginate();

        return response()->json([
            'data' => $loans->items(),
            'meta' => [
                'total' => $loans->total(),
                'current_page' => $loans->currentPage(),
                'last_page' => $loans->lastPage(),
            ],
        ]);
    }
    public function installments(Loan $loan): JsonResponse
    {
        abort_unless($loan->customer_id === auth()->id(), 403);

        return response()->json([
            'data' => $loan->installments()->orderBy('sequence')->get(),
        ]);
    }
    public function store(StoreLoanRequest $request): JsonResponse
    {
        $loan = $this->loanService->createLoan(
            customerId: $request->user()->id,
            amount: (int) $request->validated('amount'),
            tenureMonths: (int) $request->validated('tenure_months'),
        );

        return response()->json([
            'message' => 'loan_created',
            'data' => $loan->fresh(),
        ], 201);
    }
}
