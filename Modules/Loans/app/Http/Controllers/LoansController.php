<?php

namespace Modules\Loans\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
        // این پیاده‌سازی پایه است؛ منطق فیلترینگ را بعدا اضافه کنید
        $loans = Loan::where('customer_id', $request->user()->id)->paginate();

        return response()->json([
            'data' => $loans->items(),
            'meta' => ['total' => $loans->total()]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:10000000', 'max:500000000'],
            'tenure_months' => ['required', 'integer', 'in:3,6,12'],
            'purpose' => ['nullable', 'string', 'max:1000'],
        ]);

        $loan = $this->loanService->createLoan(
            customerId: (int) auth()->id(),
            amount: (int) $validated['amount'],
            tenureMonths: (int) $validated['tenure_months']
        );

        return response()->json([
            'message' => 'Loan created successfully.',
            'data' => $loan,
        ], 201);
    }
}
