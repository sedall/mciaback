<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;
use Modules\CustomerDocuments\Models\CustomerDocument;
use Modules\Customers\Models\CustomerProfile;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, SoftDeletes;
    protected string $guard_name = 'sanctum';

    public function getDefaultGuardName(): string
    {
        return 'sanctum';
    }
    protected $fillable = [
        'mobile',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
    public function profile()
    {
        return $this->customerProfile();
    }
    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }
    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class, 'user_id', 'user_id');
    }
}
