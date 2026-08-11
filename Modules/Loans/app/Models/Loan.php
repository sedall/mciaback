<?php
namespace Modules\Loans\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Loans\Database\Factories\LoanFactory;

class Loan extends Model
{
    use HasFactory;
    protected static function newFactory(): Factory
    {
        return LoanFactory::new();
    }
    public const STATUS_PENDING = 'pending';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FUNDED = 'funded';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DEFAULTED = 'defaulted';

    protected $fillable = [
        'customer_id',
        'principal_amount',
        'fee_amount',
        'total_payable',
        'installments_count',
        'status',
        'admin_note',
        'submitted_at',
        'approved_at',
        'approved_amount',
        'approved_term_months',
        'funded_at',
        'started_at',
        'completed_at',
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
