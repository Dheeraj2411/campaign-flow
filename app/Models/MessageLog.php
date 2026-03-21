<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageLog extends Model
{
    protected $fillable = [
        'campaign_id',
        'contact_id',
        'platform',
        'final_message',
        'status',
        'error_message',
        'platform_message_id',
        'failure_reason',
        'attempt_count',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(MessageStatusHistory::class);
    }

    /**
     * Record a status transition in the history table.
     */
    public function recordStatus(string $status, ?string $rawStatus = null): void
    {
        $this->statusHistory()->create([
            'status'      => $status,
            'raw_status'  => $rawStatus,
            'occurred_at' => now(),
        ]);
    }
}
