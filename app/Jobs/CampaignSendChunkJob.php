<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\MessageLog;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CampaignSendChunkJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'campaign-send';

    public function __construct(public int $campaignId, public array $contactIds) {}

    public function handle(): void
    {
        $campaign = Campaign::withoutGlobalScopes()->with('workspace')->find($this->campaignId);

        if (!$campaign || $campaign->status !== Campaign::STATUS_RUNNING) {
            return;
        }

        if (!$campaign->workspace) {
            Log::error("CampaignSendChunkJob: missing workspace for campaign {$this->campaignId}");
            return;
        }

        \App\Tenancy\TenantContext::setWorkspace($campaign->workspace);

        try {
            $contacts = Contact::whereIn('id', $this->contactIds)->get();
            $logs = [];

            foreach ($contacts as $contact) {
                $body = $campaign->template ? $campaign->template->body : $campaign->body;
                $message = (new \App\Services\MessagePersonalizationService())->personalize($body, $contact);

                $logs[] = [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'platform' => $campaign->platform,
                    'final_message' => $message,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($logs)) {
                MessageLog::insert($logs);
                $insertedLogs = MessageLog::where('campaign_id', $campaign->id)
                    ->where('status', 'pending')
                    ->whereIn('contact_id', $this->contactIds)
                    ->get();

                foreach ($insertedLogs as $log) {
                    SendMessageJob::dispatch($log->id)->onQueue('campaign-send');
                }
            }
        } catch (\Throwable $e) {
            Log::error("CampaignSendChunkJob failed for campaign {$this->campaignId}: {$e->getMessage()}");
            throw $e;
        } finally {
            \App\Tenancy\TenantContext::setWorkspace(null);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("CampaignSendChunkJob permanently failed for campaign {$this->campaignId}: " . $exception->getMessage());
    }
}
