<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MessageTemplateController extends Controller
{
    private function workspaceId(): int
    {
        return auth()->user()->active_workspace_id ?? 0;
    }

    public function index()
    {
        return Inertia::render('Templates/Index', [
            'templates' => MessageTemplate::latest()
                ->paginate(20),
        ]);
    }

    public function create()
    {
        return Inertia::render('Templates/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'platform'          => 'required|in:whatsapp,telegram',
            'category'          => 'required_if:platform,whatsapp|string',
            'language'          => 'required_if:platform,whatsapp|string|max:10',
            'content_structure' => 'required_if:platform,whatsapp|array',
            'body'              => 'required_if:platform,telegram|string',
        ]);

        // For WhatsApp, extract the body from content_structure for consistency
        if ($data['platform'] === 'whatsapp' && isset($data['content_structure']['body'])) {
            $data['body'] = $data['content_structure']['body'];
        }

        $template = MessageTemplate::create(array_merge($data, [
            'workspace_id' => $this->workspaceId(),
        ]));

        if ($template->platform === 'whatsapp') {
            try {
                $service = new \App\Services\MetaApiService($template->workspace);
                $metaData = $service->createTemplate($template);
                
                // Store the Meta ID and update status to PENDING
                $template->update([
                    'meta_template_id' => $metaData['id'] ?? null,
                    'status' => MessageTemplate::STATUS_PENDING
                ]);
            } catch (\Exception $e) {
                // If API fails, keep as draft and notify
                return back()->with('error', 'Template saved locally but Meta submission failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('templates.index')->with('success', 'Template created.');
    }

    public function sync()
    {
        try {
            $workspace = auth()->user()->activeWorkspace;
            $service = new \App\Services\MetaApiService($workspace);
            $count = $service->syncTemplates();
            return back()->with('success', "Successfully synced $count templates with Meta");
        } catch (\Exception $e) {
            return back()->with('error', "Sync failed: " . $e->getMessage());
        }
    }

    public function update(Request $request, MessageTemplate $template)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'body'     => 'required|string',
        ]);

        $template->update($data);

        return back()->with('success', 'Template updated.');
    }

    public function destroy(MessageTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Template deleted.');
    }

    public function sendTest(Request $request, MessageTemplate $template)
    {
        abort_if($template->workspace_id !== $this->workspaceId(), 403);

        $request->validate([
            'platform'    => 'required|in:whatsapp,telegram',
            'destination' => 'required|string',
        ]);

        $platform    = $request->platform;
        $destination = $request->destination;
        $workspace   = auth()->user()->activeWorkspace;

        // Simple interpolation with dummy data or provided name if any
        $message = str_replace('{name}', 'Test User', $template->body);
        $message = str_replace('{phone}', $destination, $message);
        $message = str_replace('{telegram_username}', '@test_user', $message);

        try {
            if ($platform === 'whatsapp') {
                $service = new \App\Services\WhatsAppService($workspace);
                $service->sendMessage($destination, $message);
            } else {
                $service = new \App\Services\TelegramService($workspace);
                $service->sendMessage($destination, $message);
            }

            return back()->with('success', 'Test message sent successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Test failed: ' . $e->getMessage());
        }
    }
}
