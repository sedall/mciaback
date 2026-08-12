<?php

namespace Modules\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id', 'sender_id', 'sender_type', 'body'
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }
}
