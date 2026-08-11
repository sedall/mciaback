<?php

namespace Modules\CustomerDocuments\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CustomerDocuments\Http\Requests\RejectCustomerDocumentRequest;
use Modules\CustomerDocuments\Http\Resources\CustomerDocumentResource;
use Modules\CustomerDocuments\Models\CustomerDocument;

class AdminCustomerDocumentsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(
            max($request->integer('per_page', 15), 1),
            100
        );

        $documents = CustomerDocument::query()
            ->with(['user', 'reviewer'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->value());
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->string('type')->value());
            })
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $query->where('user_id', $request->integer('user_id'));
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate(
                    'created_at',
                    '>=',
                    $request->string('from_date')->value()
                );
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate(
                    'created_at',
                    '<=',
                    $request->string('to_date')->value()
                );
            })
            ->latest()
            ->paginate($perPage);

        return CustomerDocumentResource::collection($documents);
    }

    public function show(CustomerDocument $customerDocument)
    {
        $customerDocument->load(['user', 'reviewer']);

        return new CustomerDocumentResource($customerDocument);
    }

    public function approve(CustomerDocument $customerDocument)
    {
        if ($customerDocument->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending documents can be approved.',
            ], 422);
        }

        DB::transaction(function () use ($customerDocument) {
            $customerDocument->update([
                'status' => 'approved',
                'rejection_reason' => null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        });

        $customerDocument->load(['user', 'reviewer']);

        return new CustomerDocumentResource($customerDocument);
    }

    public function reject(
        RejectCustomerDocumentRequest $request,
        CustomerDocument $customerDocument
    ) {
        if ($customerDocument->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending documents can be rejected.',
            ], 422);
        }

        DB::transaction(function () use ($request, $customerDocument) {
            $customerDocument->update([
                'status' => 'rejected',
                'rejection_reason' => $request
                    ->string('rejection_reason')
                    ->value(),
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        });

        $customerDocument->load(['user', 'reviewer']);

        return new CustomerDocumentResource($customerDocument);
    }
}
