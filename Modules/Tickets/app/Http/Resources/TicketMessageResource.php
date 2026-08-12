<?php

namespace Modules\Tickets\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'sender_type' => $this->sender_type,
            'body' => $this->body,
            'attachments' => TicketAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at,
        ];
    }
}
