<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\MessageLog;
use Illuminate\Support\Facades\DB;

class CampaignAnalyticsService
{
    public function getSummaryForWorkspace(int $workspaceId, string $startDate, string $endDate): array
    {
        $baseQuery = MessageLog::whereHas('campaign', fn($q) => $q->where('workspace_id', $workspaceId))
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalSent = (clone $baseQuery)->count();

        $totalDelivered = (clone $baseQuery)->where('status', 'delivered')->count();
        $totalFailed = (clone $baseQuery)->where('status', 'failed')->count();

        $campaigns = Campaign::where('workspace_id', $workspaceId)
            ->withCount(['messageLogs as total_sent' => fn($q) => $q, 'messageLogs as total_failed' => fn($q) => $q->where('status', 'failed')])
            ->orderByDesc('total_sent')
            ->limit(10)
            ->get(['id', 'name', 'platform', 'status']);

        return [
            'totalSent' => $totalSent,
            'totalDelivered' => $totalDelivered,
            'totalFailed' => $totalFailed,
            'campaigns' => $campaigns,
        ];
    }

    public function getByCampaign(int $campaignId): array
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return [];
        }

        $stats = $campaign->messageLogs()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return array_merge(['campaign_name' => $campaign->name], $stats);
    }
}
