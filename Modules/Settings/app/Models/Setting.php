<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'is_public'
    ];

    /**
     * Cast values dynamically based on their type.
     */
    public function getCastValueAttribute()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }
}
