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

class SendMessageJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [30, 60, 120];
    public int $timeout = 120;

    public function __construct(public int $messageLogId) {}

    public function handle(): void
    {
        $log = MessageLog::withoutGlobalScopes()
            ->with('campaign.workspace', 'contact', 'campaign.template')
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

        if ($workspace->messages_sent_this_month >= ($workspace->monthly_message_limit ?? 1000)) {
            $log->update(['status' => 'failed', 'failure_reason' => 'Monthly message limit reached']);
            return;
        }

        $throttleKey = "campaign-send:workspace:{$workspace->id}:{$log->platform}";
        $limit = $workspace->msg_per_minute ?? 60;

        if (RateLimiter::tooManyAttempts($throttleKey, $limit)) {
            Log::warning("SendMessageJob throttle limit hit: {$throttleKey}");
            $this->release(60);
            return;
        }

        RateLimiter::hit($throttleKey, 60);

        \App\Tenancy\TenantContext::setWorkspace($workspace);

        try {
            $result = $this->sendMessage($log, $workspace, $contact);

            $workspace->increment('messages_sent_this_month');

            $log->update([
                'status'              => 'sent',
                'error_message'       => null,
                'platform_message_id' => $result['platform_message_id'] ?? null,
            ]);

            $this->syncToInbox($log, $workspace, $contact, $result);

            (new \App\Services\CampaignStatusService())->refresh($log->campaign);
        } catch (\Exception $e) {
            $log->update([
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);
            throw $e; 
        } finally {
            \App\Tenancy\TenantContext::setWorkspace(null);
        }
    }

    private function sendMessage(MessageLog $log, $workspace, $contact): array
    {
        if ($log->platform === 'whatsapp') {
            $service = new \App\Services\WhatsAppService($workspace);

            $template = $log->campaign?->template;
            
            if ($template) {
                // Determine message_type
                $messageType = $template->message_type ?? 'text';
                
                // Get interactive config properly depending on if it was cast as array
                $interactiveConfig = is_string($template->interactive_config ?? null) 
                    ? json_decode($template->interactive_config, true) 
                    : ($template->interactive_config ?? []);

                if ($messageType === 'interactive_buttons') {
                    return $service->sendInteractiveButtons(
                        $contact->phone, 
                        $log->final_message, 
                        $interactiveConfig['buttons'] ?? []
                    );
                }

                if ($messageType === 'interactive_list') {
                    return $service->sendInteractiveList(
                        $contact->phone, 
                        $interactiveConfig['header_text'] ?? 'Options', 
                        $log->final_message, 
                        $interactiveConfig['button_label'] ?? 'Menu', 
                        $interactiveConfig['sections'] ?? []
                    );
                }
            }

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

    private function syncToInbox(MessageLog $log, $workspace, $contact, array $result): void
    {
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

        // Include any interactive metadata so the UI renders it cleanly
        $template = $log->campaign?->template;
        $metadata = [];
        if ($template) {
            $messageType = $template->message_type ?? 'text';
            $interactiveConfig = is_string($template->interactive_config ?? null) 
                ? json_decode($template->interactive_config, true) 
                : ($template->interactive_config ?? []);

            if ($messageType === 'interactive_buttons') {
                $metadata = ['buttons' => $interactiveConfig['buttons'] ?? []];
            } elseif ($messageType === 'interactive_list') {
                $metadata = ['list' => $interactiveConfig];
            }
        }

        $convMessage = $conversation->messages()->create([
            'direction'           => 'outbound',
            'type'                => 'text',
            'status'              => 'sent',
            'body'                => $log->final_message,
            'sent_at'             => now(),
            'platform_message_id' => $result['platform_message_id'] ?? null,
            'metadata'            => $metadata,
        ]);

        try {
            broadcast(new \App\Events\MessageReceived($convMessage->load('conversation.contact')));
        } catch (\Exception $e) {
            Log::warning("Broadcast failed (campaign message): " . $e->getMessage());
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $log = MessageLog::find($this->messageLogId);

        if ($log) {
            $log->update([
                'status'         => 'failed',
                'error_message'  => 'All retries exhausted.',
                'failure_reason' => $exception?->getMessage() ?? 'Unknown error',
            ]);

            if ($campaign = $log->campaign) {
                (new \App\Services\CampaignStatusService())->refresh($campaign);
            }
        }
    }
}
