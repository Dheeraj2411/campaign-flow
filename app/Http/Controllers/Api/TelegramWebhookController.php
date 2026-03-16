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
        \Illuminate\Support\Facades\Log::debug("Telegram Webhook received for {$workspace->slug}:", $payload);

        // Telegram sends incoming messages here
        $msg = data_get($payload, 'message');
        if ($msg && isset($msg['text'])) {
            $fromUsername = $msg['from']['username'] ?? null;
            $fromId = $msg['from']['id'] ?? null;
            $chatId = (string) ($msg['chat']['id'] ?? $fromId);
            $text = $msg['text'];

            // Try to find the contact in this workspace by telegram username or phone (id)
            $contact = \App\Models\Contact::where('workspace_id', $workspace->id)
                ->where(function ($query) use ($fromUsername, $fromId, $chatId) {
                    if ($fromUsername) {
                        $query->whereRaw('LOWER(telegram_username) = ?', [strtolower($fromUsername)])
                              ->orWhereRaw('LOWER(telegram_username) = ?', [strtolower('@' . $fromUsername)]);
                    }
                    if ($fromId) {
                        $query->orWhere('phone', $fromId); // Sometimes saved as phone
                    }
                    if ($chatId) {
                        $query->orWhere('telegram_chat_id', $chatId);
                    }
                })->first();

            if (!$contact) {
                // Auto-create contact for inbound lead
                $contact = \App\Models\Contact::create([
                    'workspace_id' => $workspace->id,
                    'name' => $msg['from']['first_name'] ?? 'Unknown Telegram User',
                    'telegram_username' => $fromUsername ? '@' . $fromUsername : $fromId,
                    'telegram_chat_id' => $chatId,
                    'phone' => $fromId,
                ]);
            } elseif ($chatId && $contact->telegram_chat_id !== $chatId) {
                // Always update chat_id so we can send messages back
                $contact->update(['telegram_chat_id' => $chatId]);
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
