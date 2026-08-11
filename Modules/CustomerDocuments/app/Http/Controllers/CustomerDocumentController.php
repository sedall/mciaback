<?php

namespace Modules\CustomerDocuments\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\CustomerDocuments\Http\Requests\StoreCustomerDocumentRequest;
use Modules\CustomerDocuments\Http\Resources\CustomerDocumentResource;
use Modules\CustomerDocuments\Models\CustomerDocument;

class CustomerDocumentController extends Controller
{
    public function index()
    {
        $documents = CustomerDocument::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return CustomerDocumentResource::collection($documents);
    }

    public function store(StoreCustomerDocumentRequest $request)
    {
        $userId = auth()->id();
        $type = $request->validated()['type'];

        $existingDocument = CustomerDocument::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->first();

        if ($existingDocument?->file_path && Storage::disk('public')->exists($existingDocument->file_path)) {
            Storage::disk('public')->delete($existingDocument->file_path);
        }

        $path = $request->file('file')->store('customer-documents', 'public');

        $document = CustomerDocument::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'type' => $type,
            ],
            [
                'file_path' => $path,
                'status' => 'pending',
                'rejection_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        return (new CustomerDocumentResource($document->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function indexForAdmin(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $query = CustomerDocument::query()->latest();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        $documents = $query->paginate(15);

        return CustomerDocumentResource::collection($documents);
    }

    public function showForAdmin(CustomerDocument $customerDocument)
    {
        return new CustomerDocumentResource($customerDocument);
    }

    public function approve(CustomerDocument $customerDocument)
    {
        if ($customerDocument->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending documents can be approved.',
            ], 422);
        }

        $customerDocument->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        return new CustomerDocumentResource($customerDocument->fresh());
    }

    public function reject(CustomerDocument $customerDocument, Request $request)
    {
        if ($customerDocument->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending documents can be rejected.',
            ], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $customerDocument->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return new CustomerDocumentResource($customerDocument->fresh());
    }
}
