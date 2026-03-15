<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    private function workspaceId(): int
    {
        return auth()->user()->active_workspace_id ?? 0;
    }

    public function index()
    {
        return Inertia::render('Contacts/Index', [
            'contacts' => Contact::forWorkspace($this->workspaceId())
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'phone'              => 'required|string|max:50',
            'telegram_username'  => 'nullable|string|max:100',
            'tags'               => 'nullable|array',
        ]);

        Contact::create([...$data, 'workspace_id' => $this->workspaceId()]);

        return back()->with('success', 'Contact added.');
    }

    public function create()
    {
        return redirect()->route('contacts.index');
    }

    public function update(Request $request, Contact $contact)
    {
        abort_if($contact->workspace_id !== $this->workspaceId(), 403);

        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'phone'              => 'required|string|max:50',
            'telegram_username'  => 'nullable|string|max:100',
            'tags'               => 'nullable|array',
        ]);

        $contact->update($data);

        return back()->with('success', 'Contact updated.');
    }

    public function destroy(Contact $contact)
    {
        abort_if($contact->workspace_id !== $this->workspaceId(), 403);
        $contact->delete();
        return back()->with('success', 'Contact deleted.');
    }

    public function importForm()
    {
        return Inertia::render('Contacts/Import');
    }

    public function importCsv(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        dispatch(new \App\Jobs\ImportContactsFromCsv(
            $request->file('file')->store('imports'),
            $this->workspaceId()
        ));

        return back()->with('success', 'Import started. Contacts will appear shortly.');
    }
}
