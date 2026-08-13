<?php

namespace Modules\Loans\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanTransaction extends Model
{

    public const TYPE_DISBURSEMENT = 'disbursement';
    public const TYPE_REPAYMENT = 'repayment';
    public const TYPE_PENALTY = 'penalty';

    protected $fillable = [
        'loan_id',
        'type',
        'amount',
        'performed_by',
        'reference',
        'meta',
        'transacted_at',
        'reference',
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
