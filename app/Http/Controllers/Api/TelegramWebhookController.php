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
        if ($msg) {
            $msgId = $msg['message_id'] ?? null;
            $fromUsername = $msg['from']['username'] ?? null;
            $fromId = $msg['from']['id'] ?? null;
            $chatId = (string) ($msg['chat']['id'] ?? $fromId);
            
            // Determine message type and content
            $type = \App\Models\ConversationMessage::TYPE_TEXT;
            $body = $msg['text'] ?? '';
            $mediaId = null;
            $caption = $msg['caption'] ?? null;

            if (isset($msg['photo'])) {
                $type = \App\Models\ConversationMessage::TYPE_IMAGE;
                $mediaId = end($msg['photo'])['file_id']; // Latest is highest resolution
                $body = $body ?: "[Photo]";
            } elseif (isset($msg['document'])) {
                $type = \App\Models\ConversationMessage::TYPE_DOCUMENT;
                $mediaId = $msg['document']['file_id'];
                $body = $body ?: "[Document: " . ($msg['document']['file_name'] ?? 'file') . "]";
            } elseif (isset($msg['video'])) {
                $type = \App\Models\ConversationMessage::TYPE_VIDEO;
                $mediaId = $msg['video']['file_id'];
                $body = $body ?: "[Video]";
            }

            // Try to find the contact in this workspace
            $contact = \App\Models\Contact::where('workspace_id', $workspace->id)
                ->where(function ($query) use ($fromUsername, $fromId, $chatId) {
                    if ($fromUsername) {
                        $query->whereRaw('LOWER(telegram_username) = ?', [strtolower($fromUsername)])
                              ->orWhereRaw('LOWER(telegram_username) = ?', [strtolower('@' . $fromUsername)]);
                    }
                    if ($fromId) {
                        $query->orWhere('phone', $fromId); 
                    }
                    if ($chatId) {
                        $query->orWhere('telegram_chat_id', $chatId);
                    }
                })->first();

            if (!$contact) {
                $contact = \App\Models\Contact::create([
                    'workspace_id' => $workspace->id,
                    'name' => trim(($msg['from']['first_name'] ?? 'Unknown') . ' ' . ($msg['from']['last_name'] ?? '')),
                    'telegram_username' => $fromUsername ? '@' . $fromUsername : $fromId,
                    'telegram_chat_id' => $chatId,
                    'phone' => $fromId,
                ]);
            } elseif ($chatId && $contact->telegram_chat_id !== $chatId) {
                $contact->update(['telegram_chat_id' => $chatId]);
            }

            $conversation = \App\Models\Conversation::updateOrCreate(
                ['workspace_id' => $workspace->id, 'contact_id' => $contact->id, 'platform' => 'telegram'],
                ['last_message_at' => now(), 'status' => 'open']
            );

            $convMessage = $conversation->messages()->create([
                'direction'           => \App\Models\ConversationMessage::DIRECTION_INBOUND,
                'type'                => $type,
                'body'                => $body,
                'media_url'           => $mediaId,
                'caption'             => $caption,
                'status'              => 'delivered',
                'sent_at'             => now(),
                'platform_message_id' => $msgId,
            ]);

            $conversation->update([
                'last_message_at'      => now(),
                'last_incoming_at'     => now(),
                'unread_count'         => $conversation->unread_count + 1,
                'last_message_preview' => mb_substr($body ?: "[Received {$type}]", 0, 100),
                'status'               => 'open',
            ]);

            broadcast(new \App\Events\MessageReceived($convMessage->load('conversation.contact')));
        } else {
             Log::info('Received Telegram webhook for workspace: ' . $workspace->name, $payload);
        }

        return response()->json(['success' => true]);
    }
}
