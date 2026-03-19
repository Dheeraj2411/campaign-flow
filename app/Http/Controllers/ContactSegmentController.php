<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactSegmentRequest;
use App\Models\ContactSegment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactSegmentController extends Controller
{
    private function workspaceId(): int
    {
        return auth()->user()->active_workspace_id ?? 0;
    }

    public function index()
    {
        $workspaceId = $this->workspaceId();

        return Inertia::render('Contacts/Segments', [
            'segments' => ContactSegment::where('workspace_id', $workspaceId)->get(),
        ]);
    }

    public function store(ContactSegmentRequest $request)
    {
        $workspaceId = $this->workspaceId();

        ContactSegment::create(array_merge($request->validated(), ['workspace_id' => $workspaceId]));

        return back()->with('success', 'Segment created.');
    }

    public function update(ContactSegmentRequest $request, ContactSegment $contactSegment)
    {
        abort_if($contactSegment->workspace_id !== $this->workspaceId(), 403);

        $contactSegment->update($request->validated());
        return back()->with('success', 'Segment updated.');
    }

    public function destroy(ContactSegment $contactSegment)
    {
        abort_if($contactSegment->workspace_id !== $this->workspaceId(), 403);

        $contactSegment->delete();
        return back()->with('success', 'Segment deleted.');
    }
}
