<?php

namespace Modules\Loans\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class LoanTransaction extends Model
{
    protected $table = 'loan_transactions';

    protected $fillable = [
        'loan_id',
        'installment_id',
        'type',
        'amount',
        'performed_by',
        'reference',
        'description',
        'meta',
        'transacted_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'transacted_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
