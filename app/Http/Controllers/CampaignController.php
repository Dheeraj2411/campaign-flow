<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends Controller
{
    private function workspaceId(): int
    {
        return auth()->user()->active_workspace_id ?? 0;
    }

    public function index()
    {
        return Inertia::render('Campaigns/Index', [
            'campaigns' => Campaign::where('workspace_id', $this->workspaceId())
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create()
    {
        return Inertia::render('Campaigns/Create', [
            'contactGroups' => [
                ['id' => 'all', 'name' => 'All Contacts', 'count' => Contact::forWorkspace($this->workspaceId())->count()],
            ],
            'templates' => MessageTemplate::where('workspace_id', $this->workspaceId())->get(['id', 'name', 'body', 'platform']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'platform'         => 'required|in:whatsapp,telegram,both',
            'body'             => 'required|string',
            'contact_group_id' => 'nullable|string',
            'scheduled_at'     => 'nullable|date|after:now',
        ]);

        // 'all' means send to every contact — store as null
        if (($data['contact_group_id'] ?? null) === 'all') {
            $data['contact_group_id'] = null;
        }

        $campaign = Campaign::create([
            ...$data,
            'workspace_id' => $this->workspaceId(),
            'status'       => $data['scheduled_at'] ? Campaign::STATUS_SCHEDULED : Campaign::STATUS_DRAFT,
        ]);

        if (!$data['scheduled_at']) {
            \App\Jobs\DispatchCampaignJob::dispatch($campaign->id);
        }

        return redirect()->route('campaigns.index')->with('success', 'Campaign created.');
    }

    public function show(Campaign $campaign)
    {
        abort_if($campaign->workspace_id !== $this->workspaceId(), 403);

        $campaign->loadCount([
            'messageLogs as total_sent',
            'messageLogs as total_delivered' => fn($q) => $q->whereIn('status', ['sent', 'delivered']),
            'messageLogs as total_failed'    => fn($q) => $q->where('status', 'failed'),
        ]);

        $logs = $campaign->messageLogs()
            ->with('contact:id,name,phone,telegram_username')
            ->latest()
            ->paginate(50);

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
            'logs'     => $logs,
        ]);
    }

    public function destroy(Campaign $campaign)
    {
        abort_if($campaign->workspace_id !== $this->workspaceId(), 403);
        $campaign->delete();
        return back()->with('success', 'Campaign deleted.');
    }
}
