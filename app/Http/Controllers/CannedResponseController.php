<?php

namespace App\Http\Controllers;

use App\Models\CannedResponse;
use Illuminate\Http\Request;

class CannedResponseController extends Controller
{
    private function activeWorkspaceId()
    {
        return auth()->user()->active_workspace_id;
    }

    public function index()
    {
        $responses = CannedResponse::where('workspace_id', $this->activeWorkspaceId())->get();
        return response()->json($responses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shortcut' => 'required|string|max:50',
            'content'  => 'required|string|max:5000',
        ]);

        $response = CannedResponse::create([
            'workspace_id' => $this->activeWorkspaceId(),
            'shortcut'     => $validated['shortcut'],
            'content'      => $validated['content'],
        ]);

        return response()->json($response);
    }

    public function update(Request $request, CannedResponse $cannedResponse)
    {
        abort_if($cannedResponse->workspace_id !== $this->activeWorkspaceId(), 403);

        $validated = $request->validate([
            'shortcut' => 'required|string|max:50',
            'content'  => 'required|string|max:5000',
        ]);

        $cannedResponse->update($validated);

        return response()->json($cannedResponse);
    }

    public function destroy(CannedResponse $cannedResponse)
    {
        abort_if($cannedResponse->workspace_id !== $this->activeWorkspaceId(), 403);

        $cannedResponse->delete();

        return response()->json(['success' => true]);
    }
}
