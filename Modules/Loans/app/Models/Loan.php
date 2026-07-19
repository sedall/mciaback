<?php

namespace Modules\Loans\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Loan extends Model
{
    use SoftDeletes;

    protected $table = 'loans';

    protected $fillable = [
        'customer_id',
        'clinic_id',
        'principal_amount',
        'fee_amount',
        'total_payable',
        'installments_count',
        'status',
        'submitted_at',
        'approved_at',
        'funded_at',
        'started_at',
        'completed_at',
        'rejected_at',
        'rejection_reason',
        'meta',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'funded_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'meta' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clinic_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoanTransaction::class);
    }
}
