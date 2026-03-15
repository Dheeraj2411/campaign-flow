<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'workspace_id', 'name', 'phone', 'telegram_username', 'tags', 'custom_attributes',
    ];

    protected $casts = [
        'tags'              => 'array',
        'custom_attributes' => 'array',
    ];

    // ── Scopes ─────────────────────────────────────────────────
    public function scopeForWorkspace($query, $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    // ── Relations ──────────────────────────────────────────────
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
