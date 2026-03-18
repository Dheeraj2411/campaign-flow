<?php

namespace App\Events;

use App\Models\ConversationMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a message status changes (sent → delivered → read → failed).
 * Uses ShouldBroadcastNow so it fires immediately (no queue needed).
 * This prevents queue failures when Reverb server is not running.
 */
class MessageStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ConversationMessage $message;

    public function __construct(ConversationMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Channel to broadcast on — scoped to the workspace.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('workspace.' . $this->message->conversation->workspace_id),
        ];
    }

    /**
     * Explicit payload sent to the frontend.
     * This avoids serialization issues and keeps the payload clean.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id'              => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'status'          => $this->message->status,
                'direction'       => $this->message->direction,
                'sent_at'         => $this->message->sent_at?->toISOString(),
            ],
        ];
    }
}
