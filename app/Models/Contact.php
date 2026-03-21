<?php

namespace App\Models;

use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\TenantScope);
    }
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
                $query->where('name', 'ilike', '%' . $search . '%')
                    ->orWhere('phone', 'ilike', '%' . $search . '%')
                    ->orWhere('telegram_username', 'ilike', '%' . $search . '%');
            });
        })->when($filters['tag'] ?? null, function ($query, $tag) {
            // Postgres JSONB containment check
            $query->whereRaw("tags @> ?::jsonb", [json_encode([$tag])]);
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

    public function normalizedTags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'contact_tag');
    }
}
