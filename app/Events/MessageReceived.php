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
 *
 * Channel:  private-chat.{workspaceId}
 * Event:    .message.received
 */
class MessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $message;
    public int $conversationId;
    public int $workspaceId;

    public function __construct(ConversationMessage $conversationMessage)
    {
        $conversation = $conversationMessage->conversation;
        $contact = $conversation->contact;

        $this->workspaceId = $conversation->workspace_id;
        $this->conversationId = $conversation->id;

        $this->message = [
            'id'              => $conversationMessage->id,
            'conversation_id' => $conversationMessage->conversation_id,
            'direction'       => $conversationMessage->direction,
            'type'            => $conversationMessage->type,
            'body'            => $conversationMessage->body,
            'media_url'       => $conversationMessage->media_url,
            'caption'         => $conversationMessage->caption,
            'status'          => $conversationMessage->status,
            'sent_at'         => $conversationMessage->sent_at?->toISOString(),
            'created_at'      => $conversationMessage->created_at?->toISOString(),
        ];
    }

    /**
     * Channel to broadcast on — scoped to the workspace.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->workspaceId),
        ];
    }

    /**
     * Custom event name for the frontend.
     * Frontend listens with: .listen('.message.received', ...)
     */
    public function broadcastAs(): string
    {
        return 'message.received';
    }

    /**
     * Explicit payload sent to the frontend.
     */
    public function broadcastWith(): array
    {
        return [
            'message'        => $this->message,
            'conversationId' => $this->conversationId,
            'workspaceId'    => $this->workspaceId,
        ];
    }
}
