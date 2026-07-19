<?php

namespace Modules\CustomerDocuments\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\CustomerDocuments\Http\Requests\StoreCustomerDocumentRequest;
use Modules\CustomerDocuments\Http\Resources\CustomerDocumentResource;
use Modules\CustomerDocuments\Models\CustomerDocument;

class CustomerDocumentsController extends Controller
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
        $validated = $request->validated();

        $userId = auth()->id();
        $type = $validated['type'];

        $existingDocument = CustomerDocument::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->first();

        if (
            $existingDocument?->file_path &&
            Storage::disk('public')->exists($existingDocument->file_path)
        ) {
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
}
