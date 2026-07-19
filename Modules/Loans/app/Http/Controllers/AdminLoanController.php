<?php

namespace Modules\Loans\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Loans\Models\Loan;
use Modules\Loans\Models\LoanTransaction;

class AdminLoanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Loan::query()->latest()->get()
        );
    }

    public function show(Loan $loan): JsonResponse
    {
        return response()->json($loan);
    }

    public function approve(Loan $loan): JsonResponse
    {
        $loan->update([
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => 'Loan approved successfully.',
            'data' => $loan->fresh(),
        ]);
    }

    public function fund(Loan $loan): JsonResponse
    {
        $loan->update([
            'status' => 'funded',
        ]);

        LoanTransaction::query()->create([
            'loan_id' => $loan->id,
            'type' => 'fund',
            'amount' => $loan->amount,
            'status' => 'success',
        ]);

        return response()->json([
            'message' => 'Loan funded successfully.',
            'data' => $loan->fresh(),
        ]);
    }
}
