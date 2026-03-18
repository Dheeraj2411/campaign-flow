<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
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

    public function index(Request $request)
    {
        return Inertia::render('Contacts/Index', [
            'filters' => $request->only(['search', 'tag']),
            'contacts' => Contact::filter($request->only(['search', 'tag']))
            ->latest()
            ->paginate(20)
            ->withQueryString(),
            'allTags' => Contact::whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values(),
            'lastImportError' => auth()->user()->activeWorkspace->last_import_error ?? null,
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->usage->canAddContact(auth()->user()->activeWorkspace)) {
            return back()->with('error', 'Limit reached! Please upgrade your plan to add more contacts.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'telegram_username' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
        ]);

        Contact::create($data);

        return back()->with('success', 'Contact added.');
    }

    public function create()
    {
        return redirect()->route('contacts.index');
    }

    public function update(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'telegram_username' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
        ]);

        $contact->update($data);

        return back()->with('success', 'Contact updated.');
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return back()->with('success', 'Contact deleted.');
    }

    public function importForm()
    {
        return Inertia::render('Contacts/Import');
    }

    public function importCsv(Request $request)
    {
        if (!$this->usage->canAddContact(auth()->user()->activeWorkspace)) {
            return back()->with('error', 'Limit reached! Please upgrade your plan to import more contacts.');
        }

        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        // Store file and dispatch job
        $path = $request->file('file')->store('imports');

        // For small files (< 100 lines), process synchronously for immediate feedback
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $lineCount = 0;
        while (!feof($handle) && $lineCount <= 101) {
            fgets($handle);
            $lineCount++;
        }
        fclose($handle);

        if ($lineCount <= 100) {
            try {
                dispatch_sync(new \App\Jobs\ImportContactsFromCsv($path, $this->workspaceId()));
                return redirect()->route('contacts.index')->with('success', 'Import completed successfully.');
            } catch (\Exception $e) {
                return redirect()->route('contacts.index')->with('error', $e->getMessage());
            }
        }

        dispatch(new \App\Jobs\ImportContactsFromCsv($path, $this->workspaceId()));

        return redirect()->route('contacts.index')->with('success', 'Import started. Contacts will appear shortly.');
    }

    public function clearImportError()
    {
        auth()->user()->activeWorkspace->update(['last_import_error' => null]);
        return back();
    }
}
