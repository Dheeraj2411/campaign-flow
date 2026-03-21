<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageStatusHistory extends Model
{
    protected $table = 'message_status_history';

    protected $fillable = [
        'message_log_id',
        'status',
        'raw_status',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function messageLog(): BelongsTo
    {
        return $this->belongsTo(MessageLog::class);
    }
}
