<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'price',
        'max_contacts',
        'max_campaigns',
        'max_messages_per_month',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features'  => 'array',
        'is_active' => 'boolean',
        'price'     => 'integer',
    ];

    protected $appends = ['monthly_message_limit'];

    /**
     * Accessor: alias max_messages_per_month as monthly_message_limit
     * so both backend and frontend can reference it consistently.
     */
    public function getMonthlyMessageLimitAttribute(): int
    {
        return (int) ($this->attributes['max_messages_per_month'] ?? 0);
    }
}
