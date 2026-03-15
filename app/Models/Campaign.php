<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'workspace_id', 'name', 'platform', 'status',
        'contact_group_id', 'body', 'template_id', 'scheduled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT     = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_RUNNING   = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED    = 'failed';

    // ── Relations ──────────────────────────────────────────────
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }
}
