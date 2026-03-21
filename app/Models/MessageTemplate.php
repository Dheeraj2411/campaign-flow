<?php

namespace App\Models;

use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTemplate extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\TenantScope);
    }
    /** @use HasFactory<\Database\Factories\MessageTemplateFactory> */
    use HasFactory, TenantScope;

    protected $fillable = [
        'workspace_id',
        'name',
        'body',
        'platform',
        'meta_template_id',
        'category',
        'language',
        'content_structure',
        'status',
        'reason',
    ];

    protected $casts = [
        'variables'         => 'array',
        'content_structure' => 'array',
    ];

    const STATUS_DRAFT    = 'DRAFT';
    const STATUS_PENDING  = 'PENDING';
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_REJECTED = 'REJECTED';

    const CAT_MARKETING      = 'MARKETING';

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

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
