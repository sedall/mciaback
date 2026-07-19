<?php

namespace Modules\Loans\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Loans\Http\Requests\StoreLoanRequest;
use Modules\Loans\Http\Requests\ApproveLoanRequest;
use Modules\Loans\Http\Requests\RejectLoanRequest;
use Modules\Loans\Http\Requests\FundLoanRequest;
use Modules\Loans\Models\Loan;
use Modules\Loans\Services\LoanService;

class LoansController extends Controller
{
    public function __construct(
        protected LoanService $loanService
    ) {}

    // customer: GET /api/customer/loans
    public function index(): JsonResponse
    {
        $loans = Loan::query()
            ->where('customer_id', auth()->id())
            ->latest('id')
            ->paginate(15);

        return response()->json($loans);
    }

    // customer: GET /api/customer/loans/{id}
    public function show(int $id): JsonResponse
    {
        $loan = Loan::query()
            ->where('customer_id', auth()->id())
            ->with('installments')
            ->findOrFail($id);

        return response()->json($loan);
    }

    // customer: POST /api/customer/loans
    public function store(StoreLoanRequest $request): JsonResponse
    {
        $loan = $this->loanService->createLoan(
            customerId: auth()->id(),
            amount: (int) $request->integer('amount'),
            tenureMonths: (int) $request->integer('tenure_months'),
        );

        return response()->json($loan, 201);
    }

    // admin: GET /api/admin/loans
    public function adminIndex(): JsonResponse
    {
        $loans = Loan::query()
            ->latest('id')
            ->paginate(20);

        return response()->json($loans);
    }

    // admin: POST /api/admin/loans/{id}/approve
    public function approve(ApproveLoanRequest $request, int $id): JsonResponse
    {
        $loan = Loan::findOrFail($id);

        $updated = $this->loanService->approve(
            loan: $loan,
            adminId: (int) auth()->id(),
            note: $request->input('admin_note')
        );

        return response()->json($updated);
    }

    // admin: POST /api/admin/loans/{id}/reject
    public function reject(RejectLoanRequest $request, int $id): JsonResponse
    {
        $loan = Loan::findOrFail($id);

        $updated = $this->loanService->reject(
            loan: $loan,
            adminId: (int) auth()->id(),
            reason: (string) $request->input('reason')
        );

        return response()->json($updated);
    }

    // admin: POST /api/admin/loans/{id}/fund
    public function fund(FundLoanRequest $request, int $id): JsonResponse
    {
        $loan = Loan::findOrFail($id);

        $updated = $this->loanService->fund(
            loan: $loan,
            actorId: (int) auth()->id()
        );

        return response()->json($updated);
    }
}
