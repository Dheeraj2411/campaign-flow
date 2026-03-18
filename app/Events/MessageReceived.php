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
 * Fired when a new message arrives (inbound from customer, or outbound from campaign).
 * Uses ShouldBroadcastNow so it fires immediately (no queue needed).
 * This prevents queue failures when Reverb server is not running.
 */
class MessageReceived implements ShouldBroadcastNow
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
     * Includes full message data + contact info for the sidebar update.
     */
    public function broadcastWith(): array
    {
        $conversation = $this->message->conversation;
        $contact = $conversation->contact;

        return [
            'message' => [
                'id'              => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'direction'       => $this->message->direction,
                'type'            => $this->message->type,
                'body'            => $this->message->body,
                'media_url'       => $this->message->media_url,
                'caption'         => $this->message->caption,
                'status'          => $this->message->status,
                'sent_at'         => $this->message->sent_at?->toISOString(),
            ],
            'conversation' => [
                'id'                   => $conversation->id,
                'contact'              => $contact ? [
                    'id'    => $contact->id,
                    'name'  => $contact->name,
                    'phone' => $contact->phone,
                ] : null,
                'last_message_at'      => $conversation->last_message_at?->toISOString(),
                'last_message_preview' => $conversation->last_message_preview,
                'unread_count'         => $conversation->unread_count,
                'status'               => $conversation->status,
                'platform'             => $conversation->platform,
            ],
        ];
    }
}
