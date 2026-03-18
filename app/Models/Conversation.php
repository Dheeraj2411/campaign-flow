<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    const STATUS_OPEN    = 'open';
    const STATUS_PENDING = 'pending';
    const STATUS_CLOSED  = 'closed';

    protected $fillable = [
        'workspace_id',
        'contact_id',
        'platform',
        'last_message_at',
        'status',
        'unread_count',
        'assigned_to',
        'last_incoming_at',
        'last_outgoing_at',
        'tags',
        'last_message_preview',
    ];

    protected $casts = [
        'last_message_at'  => 'datetime',
        'last_incoming_at' => 'datetime',
        'last_outgoing_at' => 'datetime',
        'unread_count'     => 'integer',
        'tags'             => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ConversationNote::class);
    }
}
