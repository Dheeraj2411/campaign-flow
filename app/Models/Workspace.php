<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'slug', 'plan', 'plan_id', 'settings', 'last_import_error',
        'msg_per_minute', 'subscription_status', 'trial_ends_at', 'subscription_ends_at',
        'monthly_message_limit', 'messages_sent_this_month', 'limit_reset_at', 'campaign_chunk_size'
    ];

    protected $casts = ['settings' => 'array'];

    public function getCachedSettings(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "workspace:{$this->id}:settings",
            now()->addMinutes(30),
            fn() => $this->settings ?? []
        );
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')->withPivot('role')->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->members();
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(MessageTemplate::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }
}

