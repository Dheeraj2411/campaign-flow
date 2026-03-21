<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\MessageLog;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles Meta/WhatsApp webhook events:
 * 1. Verification handshake (GET)
 * 2. Delivery status updates (sent, delivered, read, failed)
 * 3. Incoming customer messages (text, image, video, document, interactive replies)
 */
class WhatsAppWebhookController extends Controller
{
    // ─── WEBHOOK VERIFICATION ──────────────────────────────

    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token', 'pingos_secret')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    // ─── WEBHOOK HANDLER ───────────────────────────────────

    public function handle(Request $request)
    {
        if (!$this->verifySignature($request)) {
            return response('Forbidden', 403);
        }

        $payload = $request->all();

        $workspace = $this->resolveWorkspace($payload);
        if (!$workspace) {
            return response()->json(['success' => true]); 
        }

        $statuses = data_get($payload, 'entry.0.changes.0.value.statuses', []);
        foreach ($statuses as $status) {
            $this->handleStatusUpdate($status, $workspace);
        }

        $messages = data_get($payload, 'entry.0.changes.0.value.messages', []);
        foreach ($messages as $message) {
            $this->handleIncomingMessage($message, $workspace);
        }

        return response()->json(['success' => true]);
    }

    // ─── PRIVATE HELPERS ───────────────────────────────────

    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            Log::warning('WhatsApp Webhook received without signature header — rejecting.');
            return false;
        }

        $secret = config('services.whatsapp.app_secret');
        if (empty($secret)) {
            Log::error('WHATSAPP_APP_SECRET is not configured — cannot verify webhook.');
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($expected, $signature)) {
            Log::warning('WhatsApp Webhook signature mismatch.');
            return false;
        }

        return true;
    }

    private function resolveWorkspace(array $payload): ?Workspace
    {
        $metadata           = data_get($payload, 'entry.0.changes.0.value.metadata');
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;
        $phoneNumberId      = $metadata['phone_number_id'] ?? null;

        if (!$displayPhoneNumber) {
            Log::warning('WhatsApp Webhook received without display_phone_number', $payload);
            return null;
        }

        $workspace = Workspace::whereRaw("settings->>'whatsapp_display_phone_number' = ?", [$displayPhoneNumber])
            ->orWhereRaw("settings->>'whatsapp_phone_number_id' = ?", [$phoneNumberId ?? ''])
            ->first();

        if (!$workspace) {
            Log::warning("WhatsApp Webhook: unknown phone number: {$displayPhoneNumber}");
        }

        return $workspace;
    }

    private function handleStatusUpdate(array $status, Workspace $workspace): void
    {
        $wamid       = $status['id'] ?? null;
        $statusName  = $status['status'] ?? null;
        $recipientId = $status['recipient_id'] ?? null;

        if (!$wamid || !$statusName) return;

        $log = MessageLog::where('platform', 'whatsapp')
            ->where('platform_message_id', $wamid)
            ->first();

        if (!$log && $recipientId) {
            $normalizedRecipient = '+' . ltrim(preg_replace('/[^0-9]/', '', $recipientId), '+');
            $log = MessageLog::where('platform', 'whatsapp')
                ->whereHas('contact', fn($q) => $q->where('phone', $normalizedRecipient))
                ->where('status', 'pending')
                ->latest()
                ->first();
        }

        if ($log) {
            $log->update(['status' => $statusName]);
            
            \App\Models\MessageStatusHistory::create([
                'message_log_id' => $log->id,
                'status'         => $statusName,
                'raw_status'     => json_encode($status),
                'occurred_at'    => now(),
            ]);
        }

        $convMsg = ConversationMessage::where('platform_message_id', $wamid)->first();
        if ($convMsg) {
            $convMsg->update(['status' => $statusName]);

            try {
                broadcast(new \App\Events\MessageStatusUpdated($convMsg));
            } catch (\Exception $e) {
                Log::warning("Broadcast failed (status update): " . $e->getMessage());
            }
        }
    }

    private function handleIncomingMessage(array $message, Workspace $workspace): void
    {
        $fromPhone = $message['from'] ?? null;
        $msgType   = $message['type'] ?? 'text';
        $wamid     = $message['id'] ?? null;

        if (!$fromPhone) return;

        $normalizedPhone = '+' . ltrim(preg_replace('/[^0-9]/', '', $fromPhone), '+');
        $contact = Contact::where('workspace_id', $workspace->id)
            ->where('phone', $normalizedPhone)
            ->first();

        if (!$contact) {
            $contact = Contact::withoutGlobalScope(\App\Scopes\TenantScope::class)->firstOrCreate([
                'workspace_id' => $workspace->id,
                'name'         => $message['profile']['name'] ?? 'Unknown WhatsApp User',
                'phone'        => $normalizedPhone,
            ]);
        }

        $conversation = Conversation::withoutGlobalScope(\App\Scopes\TenantScope::class)->firstOrCreate(
            ['workspace_id' => $workspace->id, 'contact_id' => $contact->id, 'platform' => 'whatsapp'],
            ['last_message_at' => now(), 'status' => 'open']
        );

        $msgData = [
            'direction'           => 'inbound',
            'status'              => 'delivered',
            'sent_at'             => now(),
            'platform_message_id' => $wamid,
            'metadata'            => [],
        ];

        if ($msgType === 'text') {
            $msgData['body'] = $message['text']['body'] ?? '';
            $msgData['type'] = 'text';
        } elseif ($msgType === 'interactive') {
            $interactiveType = $message['interactive']['type'] ?? '';
            if ($interactiveType === 'button_reply') {
                $msgData['body'] = $message['interactive']['button_reply']['title'] ?? 'Button Reply';
                $msgData['type'] = 'button_reply';
                $msgData['metadata'] = ['button_id' => $message['interactive']['button_reply']['id'] ?? null];
            } elseif ($interactiveType === 'list_reply') {
                $msgData['body'] = $message['interactive']['list_reply']['title'] ?? 'List Reply';
                $msgData['type'] = 'list_reply';
                $msgData['metadata'] = ['list_id' => $message['interactive']['list_reply']['id'] ?? null];
            } else {
                $msgData['body'] = '[Interactive Reply]';
                $msgData['type'] = 'interactive';
            }
        } else {
            $mediaObj          = $message[$msgType] ?? [];
            $mediaId            = $mediaObj['id'] ?? null;
            $msgData['media_url'] = $mediaId ? $this->resolveMediaUrl($mediaId, $workspace) : null;
            $msgData['caption']   = $mediaObj['caption'] ?? null;
            $msgData['body']      = "[Received {$msgType}]";
            $msgData['type'] = $msgType;
        }

        $convMessage = $conversation->messages()->create($msgData);

        $conversation->update([
            'last_message_at' => now(),
            'last_incoming_at' => now(),
            'last_message_preview' => mb_substr($msgData['body'] ?? "[Received {$msgType}]", 0, 100),
        ]);

        $conversation->increment('unread_count');

        try {
            broadcast(new \App\Events\MessageReceived($convMessage->load('conversation.contact')));
        } catch (\Exception $e) {
            Log::warning("Broadcast failed (incoming message): " . $e->getMessage());
        }

        try {
            $chatbot = app(\App\Services\ChatbotService::class);
            if ($chatbot->shouldRespond($conversation, $msgData['body'] ?? '')) {
                $config = \App\Models\ChatbotConfig::getCachedForWorkspace($workspace->id);
                if (str_contains(strtolower($msgData['body'] ?? ''), strtolower($config->escalate_keyword ?? 'human'))) {
                    $chatbot->handleEscalation($conversation);
                    $handoffMsg = "Connecting you to a human agent...";
                    \App\Jobs\SendConversationMessageJob::dispatch($conversation, $handoffMsg, $workspace);
                } else {
                    $reply = $chatbot->generateReply($workspace, $conversation, $msgData['body'] ?? '');
                    \App\Jobs\SendConversationMessageJob::dispatch($conversation, $reply, $workspace);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Chatbot execution failed: " . $e->getMessage());
        }

        try {
            $aws = new \App\Services\AutomationWorkflowService();

            if ($msgType === 'interactive') {
                $interactiveType = $message['interactive']['type'] ?? '';
                $replyId = null;
                if ($interactiveType === 'button_reply') {
                    $replyId = $message['interactive']['button_reply']['id'] ?? null;
                } elseif ($interactiveType === 'list_reply') {
                    $replyId = $message['interactive']['list_reply']['id'] ?? null;
                }

                if ($replyId) {
                    $aws->trigger('interactive_reply', [
                        'workspace_id' => $workspace->id,
                        'contact_id' => $contact->id,
                        'conversation_id' => $conversation->id,
                        'message_id' => $convMessage->id,
                        'button_id' => $interactiveType === 'button_reply' ? $replyId : null,
                        'list_id' => $interactiveType === 'list_reply' ? $replyId : null,
                        'interactive_id' => $replyId,
                        'platform' => 'whatsapp',
                    ]);
                }
            }

            $aws->trigger('incoming_message', [
                'workspace_id' => $workspace->id,
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'message_id' => $convMessage->id,
                'message' => $convMessage->body,
                'platform' => 'whatsapp',
            ]);

            $recentCampaignLog = \App\Models\MessageLog::where('contact_id', $contact->id)
                ->where('created_at', '>=', now()->subHours(24))
                ->whereNotNull('campaign_id')
                ->latest()
                ->first();

            if ($recentCampaignLog) {
                $aws->trigger('campaign_replied', [
                    'workspace_id' => $workspace->id,
                    'contact_id' => $contact->id,
                    'conversation_id' => $conversation->id,
                    'campaign_id' => $recentCampaignLog->campaign_id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Automation workflow failed: " . $e->getMessage());
        }
    }

    private function resolveMediaUrl(string $mediaId, Workspace $workspace): ?string
    {
        try {
            $encToken = $workspace->getCachedSettings()['whatsapp_access_token'] ?? null;
            if (!$encToken) return null;

            try {
                $token = \Illuminate\Support\Facades\Crypt::decryptString($encToken);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $token = $encToken;
            }

            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->get("https://graph.facebook.com/v22.0/{$mediaId}");

            return $response->json('url');
        } catch (\Exception $e) {
            Log::warning("Failed to resolve media URL for {$mediaId}: " . $e->getMessage());
            return null;
        }
    }
}
