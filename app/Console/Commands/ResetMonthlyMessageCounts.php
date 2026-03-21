<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Workspace;

class ResetMonthlyMessageCounts extends Command
{
    protected $signature = 'billing:reset-counts';
    protected $description = 'Reset monthly message counts for all workspaces and check subscription expirations.';

    public function handle()
    {
        $this->info("Resetting monthly limits...");

        Workspace::chunk(100, function ($workspaces) {
            foreach ($workspaces as $workspace) {
                // Check if subscription has expired
                if ($workspace->subscription_ends_at && $workspace->subscription_ends_at < now() && in_array($workspace->subscription_status, ['active', 'cancelled'])) {
                    $workspace->subscription_status = 'expired';
                }

                $workspace->messages_sent_this_month = 0;
                $workspace->limit_reset_at = now();
                $workspace->save();
            }
        });

        $this->info("Successfully reset sizes and verified statuses.");
    }
}
