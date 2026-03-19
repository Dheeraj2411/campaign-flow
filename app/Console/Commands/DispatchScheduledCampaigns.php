<?php

namespace App\Console\Commands;

use App\Jobs\DispatchCampaignJob;
use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class DispatchScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch-scheduled';
    protected $description = 'Dispatch campaigns whose scheduled_at time has arrived';

    public function handle(): int
    {
        $campaignTable = (new Campaign())->getTable();

        if (!Schema::hasTable($campaignTable)) {
            $this->warn("Table '{$campaignTable}' does not exist. Skipping scheduled campaign dispatch.");
            return self::SUCCESS;
        }

        try {
            $campaigns = Campaign::where('status', Campaign::STATUS_SCHEDULED)
                ->where('scheduled_at', '<=', now())
                ->get();
        } catch (QueryException $e) {
            $this->error("DB query failed in '{$campaignTable}': " . $e->getMessage());
            return self::FAILURE;
        }

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
