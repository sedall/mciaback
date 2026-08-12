<?php

namespace Modules\Tickets\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketAttachmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'extension' => $this->extension,
            'size_bytes' => $this->size_bytes,
            'mime_type' => $this->mime_type,
            'path' => $this->path,
            'disk' => $this->disk,
            'created_at' => $this->created_at,
        ];
    }
}

