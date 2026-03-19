<?php

namespace App\Models;

use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory, TenantScope;

    protected $fillable = [
        'workspace_id',
        'name',
        'phone',
        'telegram_username',
        'telegram_chat_id',
        'tags',
        'custom_attributes',
    ];

    protected $casts = [
        'tags'              => 'array',
        'custom_attributes' => 'array',
    ];

    // ── Scopes ─────────────────────────────────────────────────
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('telegram_username', 'like', '%' . $search . '%');
            });
        })->when($filters['tag'] ?? null, function ($query, $tag) {
            // Postgres JSONB containment check
            $query->whereJsonContains('tags', $tag);
        });
    }

    public function scopeSegment($query, ?ContactSegment $segment)
    {
        if (!$segment || !is_array($segment->conditions)) {
            return $query;
        }

        return $segment->scopeApplyConditions($query, $segment->conditions);
    }

    // ── Relations ──────────────────────────────────────────────
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
