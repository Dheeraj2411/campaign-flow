<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InboxController extends Controller
{
    private function activeWorkspaceId()
    {
        return auth()->user()->active_workspace_id;
    }

    public function index()
    {
        $conversations = Conversation::with(['contact'])
            ->where('workspace_id', $this->activeWorkspaceId())
            ->orderBy('last_message_at', 'desc')
            ->paginate(50);

        return Inertia::render('Inbox/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        // Reset unread count
        $conversation->update(['unread_count' => 0]);

        $messages = $conversation->messages()->orderBy('sent_at', 'asc')->get();

        return response()->json([
            'conversation' => $conversation->load('contact'),
            'messages'     => $messages,
        ]);
    }

    public function reply(Request $request, Conversation $conversation)
    {
        abort_if($conversation->workspace_id !== $this->activeWorkspaceId(), 403);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $workspace = $conversation->workspace;
        $contact   = $conversation->contact;
        $status    = 'failed';
        $error     = null;

        try {
            if ($conversation->platform === 'whatsapp') {
                $service = new \App\Services\WhatsAppService($workspace);
                $service->sendMessage($contact->phone, $validated['body']);
            } elseif ($conversation->platform === 'telegram') {
                $service = new \App\Services\TelegramService($workspace);
                $identifier = $contact->telegram_username ?: $contact->phone; 
                $service->sendMessage($identifier, $validated['body']);
            }
            $status = 'sent';
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        $msg = $conversation->messages()->create([
            'direction' => 'outbound',
            'body'      => $validated['body'],
            'status'    => $status,
            'sent_at'   => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'success' => $status === 'sent',
            'message' => $msg,
            'error'   => $error,
        ]);
    }
}
