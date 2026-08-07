<?php

namespace Modules\Customers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\CustomerDocuments\Models\CustomerDocument;
use Modules\Customers\Database\Factories\CustomerProfileFactory;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'father_name',
        'national_code',
        'birth_date',
        'gender',
        'province',
        'city',
        'address',
        'postal_code',
        'landline_phone',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];
    protected static function newFactory(): CustomerProfileFactory
    {
        return CustomerProfileFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class, 'user_id', 'user_id');
    }
    public function profile(): HasMany
    {
        return $this->hasMany(CustomerDocument::class, 'user_id', 'user_id');
    }
}
