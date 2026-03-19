<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Facades\Log;

class CampaignStatusService
{
    public function refresh(Campaign $campaign): void
    {
        if (!$campaign->exists) {
            return;
        }

        $campaign = $campaign->fresh();

        $total = $campaign->messageLogs()->count();

        if ($total === 0) {
            $campaign->update(['status' => Campaign::STATUS_COMPLETED]);
            return;
        }

        $pending = $campaign->messageLogs()->where('status', 'pending')->count();

        if ($pending > 0) {
            if ($campaign->status !== Campaign::STATUS_RUNNING) {
                $campaign->update(['status' => Campaign::STATUS_RUNNING]);
            }
            return;
        }

        $sent   = $campaign->messageLogs()->where('status', 'sent')->count();
        $failed = $campaign->messageLogs()->where('status', 'failed')->count();

        if ($failed > 0 && $sent > 0) {
            $campaign->update(['status' => Campaign::STATUS_PARTIAL]);
            return;
        }

        if ($failed > 0 && $sent === 0) {
            $campaign->update(['status' => Campaign::STATUS_FAILED]);
            return;
        }

        $campaign->update(['status' => Campaign::STATUS_COMPLETED]);
    }
}
