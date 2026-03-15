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
}
