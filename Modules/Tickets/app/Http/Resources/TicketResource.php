<?php

namespace Modules\Tickets\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'creator_id' => $this->creator_id,
            'entry_point' => $this->entry_point,
            'subject' => $this->subject,
            'status' => $this->status,
            'closed_at' => $this->closed_at,
            'closed_by' => $this->closed_by,
            'last_message_at' => $this->last_message_at,
            'messages' => TicketMessageResource::collection($this->whenLoaded('messages')),
            'created_at' => $this->created_at,
        ];
    }
}
