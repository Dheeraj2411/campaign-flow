<?php

namespace App\Jobs;

use App\Models\ConversationMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendConversationMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [10, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(public int $messageId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $message = ConversationMessage::with(['conversation.workspace', 'conversation.contact'])->find($this->messageId);

        if (!$message || $message->direction !== 'outbound' || $message->status === 'sent') {
            return;
        }

        $conversation = $message->conversation;
        $workspace    = $conversation->workspace;
        $contact      = $conversation->contact;

        try {
            if ($conversation->platform === 'whatsapp') {
                $service = new \App\Services\WhatsAppService($workspace);
                $service->sendMessage($contact->phone, $message->body);
            } elseif ($conversation->platform === 'telegram') {
                $service = new \App\Services\TelegramService($workspace);
                
                $identifier = $contact->telegram_chat_id ?: $contact->telegram_username ?: $contact->phone;
                
                if (empty($identifier)) {
                    throw new \Exception("No Telegram chat ID found.");
                }

                $service->sendMessage($identifier, $message->body);
            }

            $message->update(['status' => 'sent']);
            \App\Events\MessageStatusUpdated::dispatch($message);
            
        } catch (\Exception $e) {
            Log::error("Failed to send Inbox message {$this->messageId}: " . $e->getMessage());
            $message->update(['status' => 'failed']);
            \App\Events\MessageStatusUpdated::dispatch($message);
            throw $e;
        }
    }
}
