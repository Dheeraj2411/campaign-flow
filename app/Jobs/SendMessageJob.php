<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Sends a single campaign message to one contact via WhatsApp or Telegram.
 * Also creates a ConversationMessage record so the message appears in the Inbox.
 *
 * Flow:
 * 1. DispatchCampaignJob creates MessageLog records and dispatches this job
 * 2. This job sends the actual message via the platform API
 * 3. On success: creates a ConversationMessage for inbox visibility
 * 4. On failure: marks the log as failed (with retry support)
 */
class SendMessageJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    /** Max retry attempts. */
    public int $tries = 3;

    /** Wait time between retries (seconds). */
    public array $backoff = [60, 300];

    /** Timeout for each job (seconds). */
    public int $timeout = 120;

    public function __construct(public int $messageLogId) {}

    public function handle(): void
    {
        $log = MessageLog::withoutGlobalScopes()
            ->with('campaign.workspace', 'contact')
            ->find($this->messageLogId);

        if (!$log || $log->status !== 'pending') {
            return;
        }

        $workspace = $log->campaign?->workspace;
        $contact   = $log->contact;

        if (!$workspace || !$contact) {
            $log->update(['status' => 'failed', 'error_message' => 'Missing workspace or contact data.']);
            return;
        }

        $throttleKey = "campaign-send:workspace:{$workspace->id}:{$log->platform}";
        $limit = in_array($log->platform, ['whatsapp', 'telegram']) ? 30 : 60;

        if (RateLimiter::tooManyAttempts($throttleKey, $limit)) {
            Log::warning("SendMessageJob throttle limit hit: {$throttleKey}");
            $this->release(15);
            return;
        }

        RateLimiter::hit($throttleKey, 60);

        \App\Tenancy\TenantContext::setWorkspace($workspace);

        try {
            $result = $this->sendMessage($log, $workspace, $contact);

            // Mark the campaign log as sent
            $log->update([
                'status'              => 'sent',
                'error_message'       => null,
                'platform_message_id' => $result['platform_message_id'] ?? null,
            ]);

            // Create/update conversation and message for inbox visibility
            $this->syncToInbox($log, $workspace, $contact, $result);

            // Update campaign state after each message result.
            (new \App\Services\CampaignStatusService())->refresh($log->campaign);
        } catch (\Exception $e) {
            // Keep pending until retries are exhausted. Store last error for diagnostics.
            $log->update([
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);
            throw $e; // Re-throw so Laravel can retry with backoff
        } finally {
            \App\Tenancy\TenantContext::setWorkspace(null);
        }
    }

    /**
     * Send the message via the platform API (WhatsApp or Telegram).
     */
    private function sendMessage(MessageLog $log, $workspace, $contact): array
    {
        if ($log->platform === 'whatsapp') {
            $service = new \App\Services\WhatsAppService($workspace);
            return $service->sendMessage($contact->phone, $log->final_message);
        }

        if ($log->platform === 'telegram') {
            $service    = new \App\Services\TelegramService($workspace);
            $identifier = $contact->telegram_chat_id ?: $contact->telegram_username ?: $contact->phone;

            if (empty($identifier)) {
                throw new \Exception('No Telegram chat ID found.');
            }

            return $service->sendMessage($identifier, $log->final_message);
        }

        throw new \Exception("Unsupported platform: {$log->platform}");
    }

    /**
     * Create a ConversationMessage so campaign messages appear in the Inbox.
     * This is what makes campaign messages visible when clicking a contact.
     */
    private function syncToInbox(MessageLog $log, $workspace, $contact, array $result): void
    {
        // Create or update the conversation
        $conversation = \App\Models\Conversation::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'contact_id'   => $contact->id,
                'platform'     => $log->platform,
            ],
            [
                'last_message_at'      => now(),
                'last_outgoing_at'     => now(),
                'last_message_preview' => mb_substr($log->final_message, 0, 100),
                'unread_count'         => 0,
                'status'               => 'open',
            ]
        );

        // Create the conversation message record
        $convMessage = $conversation->messages()->create([
            'direction'           => 'outbound',
            'type'                => 'text',
            'status'              => 'sent',
            'body'                => $log->final_message,
            'sent_at'             => now(),
            'platform_message_id' => $result['platform_message_id'] ?? null,
        ]);

        // Broadcast for real-time inbox updates
        try {
            broadcast(new \App\Events\MessageReceived($convMessage->load('conversation.contact')));
        } catch (\Exception $e) {
            Log::warning("Broadcast failed (campaign message): " . $e->getMessage());
        }
    }

    /**
     * Handle permanent failure after all retries are exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        $log = MessageLog::find($this->messageLogId);

        if ($log) {
            $log->update([
                'status'        => 'failed',
                'error_message' => 'All retries exhausted: ' . ($exception?->getMessage() ?? 'Unknown error'),
            ]);

            // Ensure campaign reflects final counts once message send is definitely complete.
            if ($campaign = $log->campaign) {
                (new \App\Services\CampaignStatusService())->refresh($campaign);
            }
        }
    }
}
