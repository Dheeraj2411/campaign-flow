<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'workspace_id', 'gateway', 'gateway_payment_id', 'gateway_order_id',
        'plan', 'amount', 'currency', 'status', 'receipt_data', 'activated_at',
    ];

    protected $casts = [
        'receipt_data'  => 'array',
        'activated_at'  => 'datetime',
        'amount'        => 'integer',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
