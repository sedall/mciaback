<?php

namespace Modules\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'creator_id', 'entry_point', 'subject', 'status', 'closed_at', 'closed_by', 'last_message_at'
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'creator_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'closed_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }
}
