<?php

namespace Modules\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    protected $fillable = [
        'ticket_message_id', 'disk', 'path', 'original_name', 'extension', 'size_bytes', 'mime_type'
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }
}
