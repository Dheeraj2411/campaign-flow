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
    public function __construct(public int $campaignId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaign = Campaign::withoutGlobalScopes()->with('workspace')->find($this->campaignId);

        if (!$campaign) {
            return;
        }

        \App\Tenancy\TenantContext::setWorkspace($campaign->workspace);

        if ($campaign->status === Campaign::STATUS_COMPLETED) {
            return;
        }

        try {
            $campaign->update(['status' => Campaign::STATUS_RUNNING]);
            $campaign->load('template');

            $query = Contact::query(); // TenantScope handles isolation

            if ($campaign->contact_group_id && $campaign->contact_group_id !== 'all') {
                $query->whereJsonContains('tags', $campaign->contact_group_id);
            }

            $query->chunk(100, function ($contacts) use ($campaign) {
                $logs = [];
                foreach ($contacts as $contact) {
                    $body = $campaign->template ? $campaign->template->body : $campaign->body;
                    
                    $message = preg_replace_callback('/\{\{?(\w+)\}?\}/', function ($matches) use ($contact) {
                        $key = $matches[1];
                        if ($key === 'name' || $key === '1') return $contact->name;
                        if ($key === 'phone' || $key === '2') return $contact->phone;
                        
                        $custom = $contact->custom_attributes ?? [];
                        return $custom[$key] ?? $matches[0];
                    }, $body);

                    $logs[] = [
                        'campaign_id'   => $campaign->id,
                        'contact_id'    => $contact->id,
                        'platform'      => $campaign->platform,
                        'final_message' => $message,
                        'status'        => 'pending',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }

                MessageLog::insert($logs);

                $insertedLogs = MessageLog::where('campaign_id', $campaign->id)
                    ->where('status', 'pending')
                    ->whereIn('contact_id', $contacts->pluck('id'))
                    ->get();

                foreach ($insertedLogs as $log) {
                    SendMessageJob::dispatch($log->id);
                }
            });

            $campaign->update(['status' => Campaign::STATUS_COMPLETED]);

            // Notify workspace owner that the campaign is done
            try {
                $owner = $campaign->workspace?->owner;
                if ($owner) {
                    $owner->notify(new \App\Notifications\CampaignCompleted($campaign));
                }
            } catch (\Exception $e) {
                Log::warning("Failed to send CampaignCompleted notification: {$e->getMessage()}");
            }

        } catch (\Exception $e) {
            Log::error("Campaign #{$this->campaignId} dispatch failed: {$e->getMessage()}");
            $campaign->update(['status' => Campaign::STATUS_FAILED]);
            throw $e;
        }
    }
}
