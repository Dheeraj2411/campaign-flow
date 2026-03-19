<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkflowController extends Controller
{
    private function workspaceId(): int
    {
        return auth()->user()->active_workspace_id ?? 0;
    }

    /**
     * Display the workflows listing page.
     */
    public function index()
    {
        $workspaceId = $this->workspaceId();

        $workflows = Workflow::where('workspace_id', $workspaceId)
            ->withCount('executions')
            ->latest()
            ->get();

        return Inertia::render('Workflows/Index', [
            'workflows' => $workflows,
        ]);
    }

    /**
     * Store a new workflow.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'trigger'    => 'required|string|max:100',
            'conditions' => 'nullable|array',
            'actions'    => 'required|array|min:1',
            'is_active'  => 'boolean',
        ]);

        $workflow = Workflow::create(array_merge($validated, [
            'workspace_id' => $this->workspaceId(),
            'is_active'    => $validated['is_active'] ?? true,
        ]));

        return back()->with('success', 'Workflow created successfully.');
    }

    /**
     * Update an existing workflow.
     */
    public function update(Request $request, Workflow $workflow)
    {
        abort_if($workflow->workspace_id !== $this->workspaceId(), 403);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'trigger'    => 'required|string|max:100',
            'conditions' => 'nullable|array',
            'actions'    => 'required|array|min:1',
            'is_active'  => 'boolean',
        ]);

        $workflow->update($validated);

        return back()->with('success', 'Workflow updated.');
    }

    /**
     * Toggle workflow active/inactive.
     */
    public function toggleStatus(Workflow $workflow)
    {
        abort_if($workflow->workspace_id !== $this->workspaceId(), 403);

        $workflow->update(['is_active' => !$workflow->is_active]);

        return back()->with('success', $workflow->is_active ? 'Workflow activated.' : 'Workflow deactivated.');
    }

    /**
     * Delete a workflow.
     */
    public function destroy(Workflow $workflow)
    {
        abort_if($workflow->workspace_id !== $this->workspaceId(), 403);

        $workflow->delete();

        return back()->with('success', 'Workflow deleted.');
    }
}
