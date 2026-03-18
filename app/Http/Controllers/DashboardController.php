<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Services\UsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $usage;

    public function __construct(UsageService $usage)
    {
        $this->usage = $usage;
    }

    public function index()
    {
        $workspaceId = auth()->user()->active_workspace_id ?? 0;
        $user = auth()->user();

        $dashboardData = Cache::remember("dashboard_data:{$workspaceId}", 60, function() use ($workspaceId, $user) {
            $activeWorkspace = $user->activeWorkspace;
            return [
                'stats' => [
                    'contacts'  => Contact::where('workspace_id', $workspaceId)->count(),
                    'campaigns' => Campaign::where('workspace_id', $workspaceId)->count(),
                    'messages'  => MessageLog::whereHas('campaign', fn($q) => $q->where('workspace_id', $workspaceId))->count(),
                    'inbox'     => Conversation::where('workspace_id', $workspaceId)->where('status', 'open')->count(),
                ],
                'recentCampaigns' => Campaign::where('workspace_id', $workspaceId)
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'name', 'platform', 'status', 'scheduled_at', 'created_at']),
                'usage' => $this->usage->getUsageStats($activeWorkspace),
                'plan' => $activeWorkspace?->plan ?? 'free',
            ];
        });

        return Inertia::render('Dashboard', $dashboardData);
    }
}
