<?php

namespace App\Http\Controllers;

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
        $query = Conversation::with(['contact', 'assignee'])
            ->where('workspace_id', $this->activeWorkspaceId());

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

        $conversations = $query->orderBy('last_message_at', 'desc')
            ->paginate(50)
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
        // Security: only allow access to own workspace conversations
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        // Reset unread badge since user is now viewing this conversation
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

    /**
     * Send a reply message in a conversation (text, image, video, or document).
     */
    public function reply(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        $validated = $request->validate([
            'body'      => 'nullable|string|max:5000',
            'type'      => 'nullable|string|in:text,image,video,document',
            'media_url' => 'nullable|string',
            'caption'   => 'nullable|string|max:1000',
        ]);

        $type = $validated['type'] ?? ConversationMessage::TYPE_TEXT;

        // Text messages must have a body
        if ($type === ConversationMessage::TYPE_TEXT && empty($validated['body'])) {
            return response()->json([
                'success' => false,
                'error'   => 'Text message cannot be empty.',
            ], 422);
        }

        // Proactive WhatsApp token check
        if ($conversation->platform === 'whatsapp') {
            $workspace = \App\Models\Workspace::find($this->activeWorkspaceId());
            $ws = new \App\Services\WhatsAppService($workspace);
            if (!$ws->verifyToken()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'WhatsApp API token is expired or missing. Please check your settings.',
                ], 401);
            }
        }

        // Create the message record
        $msg = $conversation->messages()->create([
            'direction' => ConversationMessage::DIRECTION_OUTBOUND,
            'type'      => $type,
            'body'      => $validated['body'] ?? ($type !== ConversationMessage::TYPE_TEXT ? "[{$type}]" : ''),
            'media_url' => $validated['media_url'] ?? null,
            'caption'   => $validated['caption'] ?? null,
            'status'    => 'pending',
            'sent_at'   => now(),
        ]);

        // Update conversation metadata
        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => Str::limit($validated['body'] ?? "[{$type}]", 100),
            'unread_count'         => 0,
        ]);

        // Broadcast for real-time UI updates in other browser tabs/users
        try {
            broadcast(new \App\Events\MessageReceived($msg->load('conversation.contact')))->toOthers();
        } catch (\Exception $e) {
            // If Reverb is not running, log and continue — the polling fallback will pick it up
            \Illuminate\Support\Facades\Log::warning("Broadcast failed (reply): " . $e->getMessage());
        }

        // Dispatch the actual sending job (WhatsApp/Telegram API call) to the queue
        if ($msg instanceof \App\Models\ConversationMessage) {
            \App\Jobs\SendConversationMessageJob::dispatch($msg->id);
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
        ]);
    }

    // ─── ASSIGN CONVERSATION ───────────────────────────────

    /**
     * Assign a conversation to a team member.
     */
    public function assign(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
        ]);

        $conversation->update(['assigned_to' => $validated['user_id']]);

        return response()->json(['success' => true]);
    }

    // ─── UPDATE STATUS ─────────────────────────────────────

    /**
     * Open / Close / Set pending on a conversation.
     */
    public function updateStatus(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        $validated = $request->validate([
            'status' => 'required|string|in:open,closed,pending',
        ]);

        $conversation->update(['status' => $validated['status']]);

        return response()->json(['success' => true]);
    }

    // ─── INTERNAL NOTES ────────────────────────────────────

    /**
     * Add an internal team note to a conversation (not visible to the customer).
     */
    public function addNote(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $note = $conversation->notes()->create([
            'user_id' => auth()->id(),
            'body'    => $validated['body'],
        ]);

        return response()->json([
            'success' => true,
            'note'    => $note->load('user'),
        ]);
    }

    /**
     * Fetch all internal notes for a conversation.
     */
    public function fetchNotes(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        $query = $conversation->notes()
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($request->filled('before')) {
            $query->where('created_at', '<', $request->before);
        }

        $notes = $query->limit(20)->get();

        return response()->json([
            'notes'    => $notes,
            'has_more' => $notes->count() === 20,
        ]);
    }
}
