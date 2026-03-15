<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\MessageLog;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    private function workspaceId(): int
    {
        return auth()->user()->active_workspace_id ?? 0;
    }

    public function index()
    {
        $wid = $this->workspaceId();

        // ── Aggregate KPIs ────────────────────────────────────
        $totalSent      = MessageLog::whereHas('campaign', fn($q) => $q->where('workspace_id', $wid))->count();
        $totalDelivered  = MessageLog::whereHas('campaign', fn($q) => $q->where('workspace_id', $wid))->where('status', 'delivered')->count();
        $totalFailed     = MessageLog::whereHas('campaign', fn($q) => $q->where('workspace_id', $wid))->where('status', 'failed')->count();
        $deliveryRate    = $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 0;

        // ── Messages per platform ─────────────────────────────
        $platformSplit = MessageLog::whereHas('campaign', fn($q) => $q->where('workspace_id', $wid))
            ->select('platform', DB::raw('count(*) as total'))
            ->groupBy('platform')
            ->pluck('total', 'platform')
            ->toArray();

        // ── Messages over time (last 30 days) ─────────────────
        $messagesOverTime = MessageLog::whereHas('campaign', fn($q) => $q->where('workspace_id', $wid))
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn($row) => ['date' => $row->date, 'total' => $row->total])
            ->toArray();

        // ── Top 5 campaigns by message count ──────────────────
        $topCampaigns = Campaign::where('workspace_id', $wid)
            ->withCount([
                'messageLogs as total_sent',
                'messageLogs as total_delivered' => fn($q) => $q->where('status', 'delivered'),
                'messageLogs as total_failed'    => fn($q) => $q->where('status', 'failed'),
            ])
            ->orderByDesc('total_sent')
            ->limit(5)
            ->get(['id', 'name', 'platform', 'status', 'created_at']);

        // ── Conversation stats ────────────────────────────────
        $openConversations   = Conversation::where('workspace_id', $wid)->where('status', 'open')->count();
        $closedConversations = Conversation::where('workspace_id', $wid)->where('status', 'closed')->count();

        return Inertia::render('Analytics/Index', [
            'kpis' => [
                'totalSent'     => $totalSent,
                'totalDelivered' => $totalDelivered,
                'totalFailed'    => $totalFailed,
                'deliveryRate'   => $deliveryRate,
            ],
            'platformSplit'    => $platformSplit,
            'messagesOverTime' => $messagesOverTime,
            'topCampaigns'     => $topCampaigns,
            'conversations'    => [
                'open'   => $openConversations,
                'closed' => $closedConversations,
            ],
        ]);
    }
}
