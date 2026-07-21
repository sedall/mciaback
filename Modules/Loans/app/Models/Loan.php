<?php

namespace Modules\Loans\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

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
        'rejected_at',
        'rejection_reason',
        'funded_at',
    ];

    protected $casts = [
        'principal_amount' => 'integer',
        'fee_amount' => 'integer',
        'total_payable' => 'integer',
        'installments_count' => 'integer',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'funded_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'meta' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'customer_id');
    }



    public function installments()
    {
        return $this->hasMany(Installment::class);
    }

    public function transactions()
    {
        return $this->hasMany(LoanTransaction::class);
    }
}
