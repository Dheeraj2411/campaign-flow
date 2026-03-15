<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTemplate extends Model
{
    protected $fillable = ['workspace_id', 'name', 'body', 'platform', 'variables'];

    protected $casts = ['variables' => 'array'];

    // ── Relations ──────────────────────────────────────────────
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    // ── Helpers ────────────────────────────────────────────────
    public function interpolate(array $data): string
    {
        return preg_replace_callback('/\{(\w+)\}/', fn($m) => $data[$m[1]] ?? $m[0], $this->body);
    }
}
