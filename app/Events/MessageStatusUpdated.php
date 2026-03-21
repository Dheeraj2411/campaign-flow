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
 *
 * Channel:  private-chat.{workspaceId}
 * Event:    .message.status.updated
 */
class MessageStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $messageId;
    public string $status;
    public int $workspaceId;

    public function __construct(ConversationMessage $conversationMessage)
    {
        $this->messageId = $conversationMessage->id;
        $this->status = $conversationMessage->status;
        $this->workspaceId = $conversationMessage->conversation->workspace_id;
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
     * Frontend listens with: .listen('.message.status.updated', ...)
     */
    public function broadcastAs(): string
    {
        return 'message.status.updated';
    }

    /**
     * Explicit payload sent to the frontend.
     */
    public function broadcastWith(): array
    {
        return [
            'messageId'   => $this->messageId,
            'status'      => $this->status,
            'workspaceId' => $this->workspaceId,
        ];
    }
}
