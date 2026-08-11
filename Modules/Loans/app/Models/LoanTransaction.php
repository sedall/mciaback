<?php

namespace Modules\Loans\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanTransaction extends Model
{
    protected $fillable = [
        'loan_id',
        'type',
        'amount',
        'performed_by',
        'meta',
        'transacted_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'performed_by' => 'integer',
        'meta' => 'array',
        'transacted_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }
}
