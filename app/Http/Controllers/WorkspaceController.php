<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function switch(Request $request, Workspace $workspace)
    {
        // Ensure user belongs to this workspace
        if (!$request->user()->workspaces->contains($workspace->id)) {
            abort(403);
        }

        $request->user()->update(['active_workspace_id' => $workspace->id]);

        return back()->with('success', 'Switched to workspace: ' . $workspace->name);
    }
}
