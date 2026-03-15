<?php

namespace App\Console\Commands;

use App\Jobs\DispatchCampaignJob;
use App\Models\Campaign;
use Illuminate\Console\Command;

class DispatchScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch-scheduled';
    protected $description = 'Dispatch campaigns whose scheduled_at time has arrived';

    public function handle(): int
    {
        $campaigns = Campaign::where('status', Campaign::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No scheduled campaigns ready to dispatch.');
            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $campaign->update(['status' => Campaign::STATUS_RUNNING]);
            DispatchCampaignJob::dispatch($campaign->id);
            $this->info("Dispatched campaign: {$campaign->name} (ID: {$campaign->id})");
        }

        $this->info("Dispatched {$campaigns->count()} campaign(s).");
        return self::SUCCESS;
    }
}
