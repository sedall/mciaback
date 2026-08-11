<?php

namespace Modules\CustomerDocuments\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CustomerDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,

            'file_path' => $this->file_path,
            'file_url' => $this->file_path
                ? Storage::url($this->file_path)
                : null,

            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,

            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toISOString(),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'mobile' => $this->user->mobile
                        ?? $this->user->phone
                        ?? null,
                ];
            }),

            'reviewer' => $this->whenLoaded('reviewer', function () {
                return [
                    'id' => $this->reviewer->id,
                    'name' => $this->reviewer->name,
                ];
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
