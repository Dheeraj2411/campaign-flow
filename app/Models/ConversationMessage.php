<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'direction',
        'type',
        'body',
        'media_url',
        'caption',
        'status',
        'sent_at',
        'platform_message_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    const DIRECTION_INBOUND  = 'inbound';
    const DIRECTION_OUTBOUND = 'outbound';

    const TYPE_TEXT     = 'text';
    const TYPE_IMAGE    = 'image';
    const TYPE_DOCUMENT = 'document';
    const TYPE_VIDEO    = 'video';

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
