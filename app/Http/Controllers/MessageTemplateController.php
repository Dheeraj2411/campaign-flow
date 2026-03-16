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
            'templates' => MessageTemplate::where('workspace_id', $this->workspaceId())
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'platform' => 'required|in:whatsapp,telegram,both',
            'body'     => 'required|string',
        ]);

        // Extract variable names from body
        preg_match_all('/\{(\w+)\}/', $data['body'], $matches);
        $data['variables']  = array_unique($matches[1]);
        $data['workspace_id'] = $this->workspaceId();

        MessageTemplate::create($data);

        return back()->with('success', 'Template created.');
    }

    public function update(Request $request, MessageTemplate $template)
    {
        abort_if($template->workspace_id !== $this->workspaceId(), 403);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'platform' => 'required|in:whatsapp,telegram,both',
            'body'     => 'required|string',
        ]);

        preg_match_all('/\{(\w+)\}/', $data['body'], $matches);
        $data['variables'] = array_unique($matches[1]);

        $template->update($data);

        return back()->with('success', 'Template updated.');
    }

    public function destroy(MessageTemplate $template)
    {
        abort_if($template->workspace_id !== $this->workspaceId(), 403);
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
