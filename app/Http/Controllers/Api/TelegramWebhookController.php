<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming webhook updates from Telegram.
     * We pass the workspace ID in the webhook URL to know which workspace it belongs to.
     */
    public function handle(Request $request, Workspace $workspace)
    {
        $payload = $request->all();

        // Telegram sends incoming messages here
        $msg = data_get($payload, 'message');
        if ($msg && isset($msg['text'])) {
            $fromUsername = $msg['from']['username'] ?? null;
            $fromId = $msg['from']['id'] ?? null;
            $text = $msg['text'];

            // Try to find the contact in this workspace by telegram username or phone (id)
            $contact = \App\Models\Contact::where('workspace_id', $workspace->id)
                ->where(function ($query) use ($fromUsername, $fromId) {
                    if ($fromUsername) {
                        $query->where('telegram_username', $fromUsername)
                              ->orWhere('telegram_username', '@' . $fromUsername);
                    }
                    if ($fromId) {
                        $query->orWhere('phone', $fromId); // Sometimes saved as phone
                    }
                })->first();

            if (!$contact) {
                // Auto-create contact for inbound lead
                $contact = \App\Models\Contact::create([
                    'workspace_id' => $workspace->id,
                    'name' => $msg['from']['first_name'] ?? 'Unknown Telegram User',
                    'telegram_username' => $fromUsername ? '@' . $fromUsername : $fromId,
                    'phone' => $fromId,
                ]);
            }

            $conversation = \App\Models\Conversation::updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'contact_id'   => $contact->id,
                    'platform'     => 'telegram',
                ],
                [
                    'last_message_at' => now(),
                    'status'          => 'open',
                ]
            );

            $conversation->increment('unread_count');

            $convMessage = $conversation->messages()->create([
                'direction' => 'inbound',
                'body'      => $text,
                'status'    => 'delivered',
                'sent_at'   => now(),
            ]);

            broadcast(new \App\Events\MessageReceived($convMessage->load('conversation.contact')));
        } else {
             Log::info('Received Telegram webhook for workspace: ' . $workspace->name, $payload);
        }

        return response()->json(['success' => true]);
    }
}
