<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotConfig extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\TenantScope);
    }
    protected $fillable = [
        'workspace_id', 
        'is_enabled', 
        'openai_api_key', 
        'model',
        'system_prompt', 
        'trigger_keywords', 
        'off_hours_only', 
        'escalate_keyword'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'off_hours_only' => 'boolean',
        'trigger_keywords' => 'array',
        'openai_api_key' => 'encrypted',
    ];

    public static function getCachedForWorkspace(int $workspaceId): ?self
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "workspace:{$workspaceId}:chatbot_config",
            now()->addMinutes(60),
            fn() => static::withoutGlobalScope(\App\Scopes\TenantScope::class)->where('workspace_id', $workspaceId)->first()
        );
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
