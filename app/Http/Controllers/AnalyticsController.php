<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Http\Request;
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

    public function index(Request $request)
    {
        $wid = $this->workspaceId();
        
        // --- Filters ---
        $days = $request->query('days', 30);
        $startDate = $request->query('start_date', now()->subDays($days)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        // --- Aggregate KPIs ---
        $baseQuery = MessageLog::whereHas('campaign', fn($q) => $q->where('workspace_id', $wid))
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalSent      = (clone $baseQuery)->count();
        $totalDelivered  = (clone $baseQuery)->where('status', 'delivered')->count();
        $totalFailed     = (clone $baseQuery)->where('status', 'failed')->count();
        $deliveryRate    = $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 0;

        // --- Messages per platform ---
        $platformSplit = (clone $baseQuery)
            ->select('platform', DB::raw('count(*) as total'))
            ->groupBy('platform')
            ->pluck('total', 'platform')
            ->toArray();

        // --- Messages over time (Granular by Day & Platform) ---
        $messagesOverTime = (clone $baseQuery)
            ->select(
                DB::raw('DATE(created_at) as date'), 
                'platform',
                DB::raw('count(*) as total')
            )
            ->groupBy(DB::raw('DATE(created_at)'), 'platform')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'whatsapp' => $items->where('platform', 'whatsapp')->sum('total'),
                    'telegram' => $items->where('platform', 'telegram')->sum('total'),
                ];
            })
            ->values()
            ->toArray();

        // --- Top 5 campaigns by message count ---
        $topCampaigns = Campaign::where('workspace_id', $wid)
            ->withCount([
                'messageLogs as total_sent',
                'messageLogs as total_delivered' => fn($q) => $q->where('status', 'delivered'),
                'messageLogs as total_failed'    => fn($q) => $q->where('status', 'failed'),
            ])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderByDesc('total_sent')
            ->limit(5)
            ->get(['id', 'name', 'platform', 'status', 'created_at']);

        // --- Active Contacts & Inbox KPI ---
        $activeContacts = Contact::where('workspace_id', $wid)->count();
        $openConversations = Conversation::where('workspace_id', $wid)->where('status', 'open')->count();
        $unreadMessages = Conversation::where('workspace_id', $wid)->sum('unread_count');

        return Inertia::render('Analytics/Index', [
            'kpis' => [
                'totalSent'      => $totalSent,
                'totalDelivered' => $totalDelivered,
                'totalFailed'    => $totalFailed,
                'deliveryRate'   => $deliveryRate,
                'activeContacts' => $activeContacts,
                'unreadMessages' => $unreadMessages,
            ],
            'filters' => [
                'days'       => $days,
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
            'platformSplit'    => $platformSplit,
            'messagesOverTime' => $messagesOverTime,
            'topCampaigns'     => $topCampaigns,
            'conversations'    => [
                'open'   => $openConversations,
            ],
        ]);
    }
}
