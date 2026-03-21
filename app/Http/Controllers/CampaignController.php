<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends Controller
{
    protected $usage;

    public function __construct(\App\Services\UsageService $usage)
    {
        $this->usage = $usage;
    }

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
        $this->authorize('create-campaign');

        $tags = Contact::where('workspace_id', $this->workspaceId())
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values()
            ->map(fn($tag) => ['id' => $tag, 'name' => "Tag: $tag"]);

        $segments = \App\Models\ContactSegment::where('workspace_id', $this->workspaceId())->get();

        return Inertia::render('Campaigns/Create', [
            'contactGroups' => array_merge([['id' => 'all', 'name' => 'All Contacts']], $tags->toArray()),
            'contactSegments' => $segments,
            'templates' => MessageTemplate::where(function ($q) {
                $q->where('platform', 'telegram')
                    ->orWhere('status', MessageTemplate::STATUS_APPROVED);
            })->get(['id', 'name', 'body', 'platform', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create-campaign');

        $workspace = auth()->user()->activeWorkspace;

        if (!$this->usage->canCreateCampaign($workspace)) {
            return back()->with('error', 'Campaign limit reached for your plan! Please upgrade.');
        }

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'platform'         => 'required|in:whatsapp,telegram',
            'template_id'      => 'required|exists:message_templates,id',
            'contact_group_id' => 'required|string',
            'contact_segment_id' => 'nullable|exists:contact_segments,id',
            'scheduled_at'     => 'nullable|date|after:now',
        ]);

        // Estimate message count — scope to this workspace
        $query = Contact::where('workspace_id', $workspace->id);

        if (!empty($data['contact_segment_id'])) {
            $segment = \App\Models\ContactSegment::find($data['contact_segment_id']);
            if ($segment) {
                $query = $query->segment($segment);
            }
        } else if ($data['contact_group_id'] !== 'all') {
            $query->whereRaw("tags @> ?::jsonb", [json_encode([$data['contact_group_id']])]);
        }

        $contactCount = $query->count();

        if (!$this->usage->canSendMessage($workspace, $contactCount)) {
            return back()->with('error', "Your remaining message quota is insufficient for this campaign ({$contactCount} contacts).");
        }

        // Proactive WhatsApp token check
        if ($data['platform'] === 'whatsapp') {
            $ws = new \App\Services\WhatsAppService($workspace);
            if (!$ws->verifyToken()) {
                return back()->with('error', 'WhatsApp API token is expired or missing. A notification has been sent to your team.');
            }
        }

        $campaign = Campaign::create([
            ...$data,
            'workspace_id' => $workspace->id,
            'contact_group_id' => $data['contact_group_id'] === 'all' ? null : $data['contact_group_id'],
            'status' => $data['scheduled_at'] ? Campaign::STATUS_SCHEDULED : Campaign::STATUS_DRAFT,
        ]);

        if (!$data['scheduled_at']) {
            \App\Jobs\DispatchCampaignJob::dispatch($campaign->id);
        }

        return redirect()->route('campaigns.index')->with('success', 'Campaign created and queued.');
    }

    public function show(Campaign $campaign)
    {
        $this->authorize('view-campaign', $campaign);

        $campaign->load(['template']);
        $campaign->loadCount([
            'messageLogs as total_sent',
            'messageLogs as total_delivered' => fn($q) => $q->whereIn('status', ['sent', 'delivered']),
            'messageLogs as total_failed'    => fn($q) => $q->where('status', 'failed'),
        ]);

        $logs = $campaign->messageLogs()
            ->with(['contact:id,name,phone,telegram_username'])
            ->latest()
            ->paginate(50);

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
            'logs'     => $logs,
        ]);
    }

    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete-campaign', $campaign);
        $campaign->delete();
        return back()->with('success', 'Campaign deleted.');
    }
}
