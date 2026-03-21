<?php

namespace App\Jobs;

use App\Models\ConversationMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sends a single conversation reply message via WhatsApp or Telegram API.
 * This job is dispatched when a user sends a reply from the Inbox UI.
 *
 * Flow:
 * 1. User types a reply in the Inbox
 * 2. InboxController creates a ConversationMessage with status = "pending"
 * 3. This job is dispatched to send the actual message via the platform API
 * 4. On success: status → "sent", on failure: status → "failed"
 * 5. A MessageStatusUpdated event is broadcast for real-time UI updates
 */
class SendConversationMessageJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public string $queue = 'high';

    /** Max retry attempts before marking as permanently failed. */
    public int $tries = 3;

    /** Wait time between retries (seconds). */
    public array $backoff = [10, 30];

    public function __construct(public int $messageId) {}

    public function handle(): void
    {
        // Load the message with its conversation, workspace, and contact
        $message = ConversationMessage::with(['conversation.workspace', 'conversation.contact'])
            ->find($this->messageId);

        // Skip if message doesn't exist, isn't outbound, or already sent
        if (!$message || $message->direction !== 'outbound' || $message->status === 'sent') {
            return;
        }

        $conversation = $message->conversation;
        $workspace    = $conversation->workspace;
        $contact      = $conversation->contact;

        if ($workspace) {
            \App\Tenancy\TenantContext::setWorkspace($workspace);
        }

        try {
            $result = [];

            // Send via the appropriate platform
            if ($conversation->platform === 'whatsapp') {
                $result = $this->sendViaWhatsApp($workspace, $contact, $message);
            } elseif ($conversation->platform === 'telegram') {
                $result = $this->sendViaTelegram($workspace, $contact, $message);
            }

            // Mark as sent and store the platform message ID
            $message->update([
                'status'              => 'sent',
                'platform_message_id' => $result['platform_message_id'] ?? null,
            ]);

            // Update conversation timestamps
            $conversation->update([
                'last_message_at'      => now(),
                'last_outgoing_at'     => now(),
                'last_message_preview' => mb_substr($message->body ?: "[{$message->type}]", 0, 100),
                'unread_count'         => 0,
            ]);

            // Broadcast status change (try-catch so it doesn't affect the queue)
            $this->broadcastStatus($message);
        } catch (\Exception $e) {
            Log::error("Failed to send Inbox message {$this->messageId}: " . $e->getMessage());
            $message->update(['status' => 'failed']);
            $this->broadcastStatus($message);
            throw $e; // Re-throw so Laravel can retry
        } finally {
            \App\Tenancy\TenantContext::setWorkspace(null);
        }
    }

    /**
     * Send a message via WhatsApp Cloud API.
     */
    private function sendViaWhatsApp($workspace, $contact, $message): array
    {
        $service = new \App\Services\WhatsAppService($workspace);

        if ($message->type === ConversationMessage::TYPE_TEXT) {
            return $service->sendMessage($contact->phone, $message->body);
        }

        return $service->sendMedia($contact->phone, $message->type, $message->media_url, $message->caption);
    }

    /**
     * Send a message via Telegram Bot API.
     */
    private function sendViaTelegram($workspace, $contact, $message): array
    {
        $service    = new \App\Services\TelegramService($workspace);
        $identifier = $contact->telegram_chat_id ?: $contact->telegram_username ?: $contact->phone;

        if (empty($identifier)) {
            throw new \Exception("No Telegram chat ID found.");
        }

        if ($message->type === ConversationMessage::TYPE_TEXT) {
            return $service->sendMessage($identifier, $message->body);
        }

        return $service->sendMedia($identifier, $message->type, $message->media_url, $message->caption);
    }

    /**
     * Broadcast a status update event (wrapped in try-catch).
     * If Reverb isn't running, this just logs a warning — doesn't crash.
     */
    private function broadcastStatus(ConversationMessage $message): void
    {
        try {
            \App\Events\MessageStatusUpdated::dispatch($message);
        } catch (\Exception $e) {
            Log::warning("Broadcast MessageStatusUpdated failed for message {$this->messageId}: " . $e->getMessage());
        }
    }
}
