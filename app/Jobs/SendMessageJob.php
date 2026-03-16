<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Backoff intervals (in seconds) between retries.
     */
    public array $backoff = [60, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(public int $messageLogId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = \App\Models\MessageLog::with('campaign.workspace', 'contact')->find($this->messageLogId);

        if (!$log || $log->status !== 'pending') {
            return;
        }

        $workspace = $log->campaign?->workspace;
        $contact   = $log->contact;

        if (!$workspace || !$contact) {
            $log->update(['status' => 'failed', 'error_message' => 'Missing workspace or contact data.']);
            return;
        }

        try {
            if ($log->platform === 'whatsapp') {
                $service = new \App\Services\WhatsAppService($workspace);
                $service->sendMessage($contact->phone, $log->final_message);
            } elseif ($log->platform === 'telegram') {
                $service = new \App\Services\TelegramService($workspace);
                // Prefer the numeric chat_id over username/phone - Telegram requires this
                $identifier = $contact->telegram_chat_id ?: $contact->telegram_username ?: $contact->phone; 
                if (empty($identifier)) {
                    $log->update(['status' => 'failed', 'error_message' => 'No Telegram chat ID found. The contact must message the bot first.']);
                    return;
                }
                $service->sendMessage($identifier, $log->final_message);
            }

            $log->update(['status' => 'sent', 'error_message' => null]);
            
        } catch (\Exception $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => substr($e->getMessage(), 0, 500), // Ensure it fits in DB
            ]);
            
            throw $e; // Re-throw so Laravel can retry with backoff
        }
    }

    /**
     * Handle a job failure after all retries exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        $log = \App\Models\MessageLog::find($this->messageLogId);

        if ($log) {
            $log->update([
                'status'        => 'failed',
                'error_message' => 'All retries exhausted: ' . ($exception?->getMessage() ?? 'Unknown error'),
            ]);
        }
    }
}
