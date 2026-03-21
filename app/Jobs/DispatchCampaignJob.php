<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DispatchCampaignJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $campaignId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaign = Campaign::withoutGlobalScopes()->with('workspace')->find($this->campaignId);

        if (!$campaign || in_array($campaign->status, [Campaign::STATUS_COMPLETED, Campaign::STATUS_FAILED])) {
            return;
        }

        if (!$campaign->workspace) {
            Log::error("DispatchCampaignJob: missing workspace for campaign {$this->campaignId}");
            return;
        }

        \App\Tenancy\TenantContext::setWorkspace($campaign->workspace);

        try {
            $campaign->update(['status' => Campaign::STATUS_RUNNING]);
            $campaign->load('template');

            $query = Contact::query(); // TenantScope handles isolation

            if ($campaign->contact_segment_id) {
                $segment = \App\Models\ContactSegment::find($campaign->contact_segment_id);
                if ($segment) {
                    $query->segment($segment);
                }
            } elseif ($campaign->contact_group_id && $campaign->contact_group_id !== 'all') {
                $query->whereRaw("tags @> ?::jsonb", [json_encode([$campaign->contact_group_id])]);
            }

            $chunkSize = $campaign->workspace->campaign_chunk_size ?? 50;
            $jobs = [];

            $query->chunk($chunkSize, function ($contacts) use ($campaign, &$jobs) {
                $jobs[] = new CampaignSendChunkJob($campaign->id, $contacts->pluck('id')->toArray());
            });

            if (empty($jobs)) {
                $campaign->update(['status' => Campaign::STATUS_COMPLETED]);
                return;
            }

            // We cannot mark campaign completed until all SendMessageJob operations are finished.
            // Batch completion here means only dispatch chunks; actual delivery completion is tracked per message.
            \Illuminate\Support\Facades\Bus::batch($jobs)
                ->name("campaign-{$campaign->id}-batch")
                ->catch(function (\Illuminate\Bus\Batch $batch, \Throwable $e) use ($campaign) {
                    Log::error("Campaign batch failed ({$campaign->id}): " . $e->getMessage());
                    $campaign->update(['status' => Campaign::STATUS_FAILED]);
                })
                ->dispatch();
        } catch (\Throwable $e) {
            Log::error("Campaign #{$this->campaignId} dispatch failed: {$e->getMessage()}");
            $campaign->update(['status' => Campaign::STATUS_FAILED]);
            throw $e;
        } finally {
            \App\Tenancy\TenantContext::setWorkspace(null);
        }
    }
}
