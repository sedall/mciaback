<?php

namespace Modules\Loans\Models;


use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Loans\Database\Factories\InstallmentFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installment extends Model
{
    protected $table = 'installments';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_PARTIAL = 'partial';

    use HasFactory;

    protected static function newFactory(): Factory
    {
        return InstallmentFactory::new();
    }
    protected $fillable = [
        'loan_id',
        'sequence',
        'principal_amount',
        'fee_amount',
        'paid_amount',
        'late_fee_amount',
        'status',
        'due_date',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'principal_amount' => 'integer',
        'fee_amount' => 'integer',
        'paid_amount' => 'integer',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoanTransaction::class);
    }
}
