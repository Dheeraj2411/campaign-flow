<?php

namespace App\Http\Controllers;

use App\Events\AgentTyping;
use App\Events\ConversationLocked;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Handles the Inbox page — listing conversations, showing messages,
 * sending replies, assigning agents, and internal notes.
 */
class InboxController extends Controller
{
    /**
     * Get the currently active workspace ID for the logged-in user.
     */
    private function activeWorkspaceId(): int
    {
        return auth()->user()->active_workspace_id;
    }

    // ─── LIST CONVERSATIONS ─────────────────────────────────

    /**
     * Show the inbox page with a filtered list of conversations.
     */
    public function index(Request $request)
    {
        $query = Conversation::where('workspace_id', $this->activeWorkspaceId())
            ->with(['contact:id,name,phone', 'latestMessage', 'assignee']);

        // Apply optional filters
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('assignee_id')) {
            if ($request->assignee_id === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assignee_id);
            }
        }

        if ($request->filled('engagement')) {
            if ($request->engagement === 'needs_reply') {
                $query->where(function ($q) {
                    $q->whereNull('last_outgoing_at')
                      ->orWhereRaw('last_incoming_at > last_outgoing_at');
                });
            } elseif ($request->engagement === 'replied') {
                $query->whereRaw('last_outgoing_at >= last_incoming_at');
            }
        }

        $conversations = $query->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $workspace = \App\Models\Workspace::find($this->activeWorkspaceId());

        // Team members for the "Assigned To" dropdown
        $teamMembers = $workspace->users()->select('users.id', 'users.name')->get();

        // Quick-reply canned responses
        $cannedResponses = \App\Models\CannedResponse::where('workspace_id', $workspace->id)->get();

        return Inertia::render('Inbox/Index', [
            'conversations'   => $conversations,
            'filters'         => $request->all(['platform', 'status', 'assignee_id', 'engagement']),
            'teamMembers'     => $teamMembers,
            'cannedResponses' => $cannedResponses,
        ]);
    }

    // ─── SHOW CONVERSATION MESSAGES ────────────────────────

    /**
     * Fetch all messages for a single conversation (AJAX call).
     */
    public function show(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        // Reset unread badge
        $conversation->update(['unread_count' => 0]);

        $query = $conversation->messages()->orderBy('sent_at', 'desc');

        if ($request->filled('before')) {
            $query->where('sent_at', '<', $request->before);
        }

        $messages = $query->limit(20)->get()->reverse()->values();

        return response()->json([
            'conversation' => $conversation->load('contact'),
            'messages'     => $messages,
            'has_more'     => $messages->count() === 20,
        ]);
    }

    // ─── SEND A REPLY ──────────────────────────────────────

    public function reply(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        // Check if locked by another agent before sending
        if ($conversation->locked_by && $conversation->locked_by !== auth()->id()) {
            $agentName = \App\Models\User::find($conversation->locked_by)?->name ?? 'another agent';
            return response()->json([
                'success' => false,
                'error'   => "Conversation locked by {$agentName}",
            ], 423);
        }

        $validated = $request->validate([
            'body'      => 'nullable|string|max:5000',
            'type'      => 'nullable|string|in:text,image,video,document',
            'media_url' => 'nullable|string',
            'caption'   => 'nullable|string|max:1000',
        ]);

        $type = $validated['type'] ?? ConversationMessage::TYPE_TEXT;

        if ($type === ConversationMessage::TYPE_TEXT && empty($validated['body'])) {
            return response()->json([
                'success' => false,
                'error'   => 'Text message cannot be empty.',
            ], 422);
        }

        if ($conversation->platform === 'whatsapp') {
            $workspace = \App\Models\Workspace::find($this->activeWorkspaceId());
            $ws = new \App\Services\WhatsAppService($workspace);
            if (!$ws->verifyToken()) {
                return response()->json(['success' => false, 'error' => 'WhatsApp API token is expired or missing.'], 401);
            }
        }

        $msg = $conversation->messages()->create([
            'direction' => ConversationMessage::DIRECTION_OUTBOUND,
            'type'      => $type,
            'body'      => $validated['body'] ?? ($type !== ConversationMessage::TYPE_TEXT ? "[{$type}]" : ''),
            'media_url' => $validated['media_url'] ?? null,
            'caption'   => $validated['caption'] ?? null,
            'status'    => 'pending',
            'sent_at'   => now(),
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => Str::limit($validated['body'] ?? "[{$type}]", 100),
            'unread_count'         => 0,
        ]);

        try {
            broadcast(new \App\Events\MessageReceived($msg->load('conversation.contact')))->toOthers();
        } catch (\Exception $e) {}

        if ($msg instanceof \App\Models\ConversationMessage) {
            \App\Jobs\SendConversationMessageJob::dispatch($msg->id);
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    // ─── ASSIGN CONVERSATION ───────────────────────────────

    // Old assign method for fallback
    public function assign(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);
        $validated = $request->validate(['user_id' => 'nullable|exists:users,id']);
        $conversation->update(['assigned_to' => $validated['user_id']]);
        return response()->json(['success' => true]);
    }

    public function assignConversation(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);
        $validated = $request->validate(['agent_id' => 'nullable|exists:users,id']);
        
        $conversation->update(['assigned_to' => $validated['agent_id']]);
        
        // Hypothetical ConversationAssigned event if required
        // broadcast(new ConversationAssigned($conversation))->toOthers();

        return response()->json(['success' => true]);
    }

    // ─── MULTI-AGENT ACTIONS ───────────────────────────────

    public function lockConversation(Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        if ($conversation->locked_by && $conversation->locked_by !== auth()->id()) {
            return response()->json(['success' => false, 'error' => 'Already locked by another agent'], 409);
        }

        $conversation->update([
            'locked_by' => auth()->id(),
            'locked_at' => now()
        ]);

        try {
            broadcast(new ConversationLocked(
                $conversation->id,
                auth()->id(),
                auth()->user()->name,
                $conversation->workspace_id
            ))->toOthers();
        } catch (\Exception $e) {}

        return response()->json(['success' => true]);
    }

    public function unlockConversation(Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        if ($conversation->locked_by === auth()->id()) {
            $conversation->update(['locked_by' => null, 'locked_at' => null]);

            try {
                broadcast(new ConversationLocked(
                    $conversation->id,
                    null,
                    null,
                    $conversation->workspace_id
                ))->toOthers();
            } catch (\Exception $e) {}
        }

        return response()->json(['success' => true]);
    }

    public function typingIndicator(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        try {
            broadcast(new AgentTyping(
                $conversation->id,
                auth()->id(),
                auth()->user()->name,
                $conversation->workspace_id
            ))->toOthers();
        } catch (\Exception $e) {}

        return response()->json(['success' => true]);
    }

    // ─── UPDATE STATUS ─────────────────────────────────────

    public function updateStatus(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);
        $validated = $request->validate(['status' => 'required|string|in:open,closed,pending']);
        $conversation->update(['status' => $validated['status']]);

        if ($validated['status'] === 'closed') {
            try {
                app(\App\Services\AutomationWorkflowService::class)->trigger('conversation_closed', [
                    'conversation_id' => $conversation->id,
                    'workspace_id'    => $conversation->workspace_id,
                    'contact_id'      => $conversation->contact_id
                ]);
            } catch (\Throwable $e) {}
        }

        return response()->json(['success' => true]);
    }

    // ─── INTERNAL NOTES ────────────────────────────────────

    public function addNote(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);
        $validated = $request->validate(['body' => 'required|string|max:5000']);
        $note = $conversation->notes()->create([
            'user_id' => auth()->id(),
            'body'    => $validated['body'],
        ]);
        return response()->json(['success' => true, 'note' => $note->load('user')]);
    }

    public function fetchNotes(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);
        $query = $conversation->notes()->with('user')->orderBy('created_at', 'desc');
        if ($request->filled('before')) {
            $query->where('created_at', '<', $request->before);
        }
        $notes = $query->limit(20)->get();
        return response()->json(['notes' => $notes, 'has_more' => $notes->count() === 20]);
    }
}
