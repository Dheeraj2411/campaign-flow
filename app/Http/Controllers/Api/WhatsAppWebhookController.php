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
 * 3. Incoming customer messages (text, image, video, document)
 */
class WhatsAppWebhookController extends Controller
{
    // ─── WEBHOOK VERIFICATION ──────────────────────────────

    /**
     * Meta calls this GET endpoint to verify your webhook URL.
     * It sends hub_mode, hub_verify_token, and hub_challenge.
     * We verify the token matches our env and return the challenge.
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token', 'campaignflow_secret')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    // ─── WEBHOOK HANDLER ───────────────────────────────────

    /**
     * Meta calls this POST endpoint for every WhatsApp event:
     * - Message status changes (sent → delivered → read)
     * - Incoming messages from customers
     */
    public function handle(Request $request)
    {
        // Step 1: Verify webhook signature (security)
        if (!$this->verifySignature($request)) {
            return response('Forbidden', 403);
        }

        $payload = $request->all();

        // Step 2: Find which workspace owns this phone number
        $workspace = $this->resolveWorkspace($payload);
        if (!$workspace) {
            return response()->json(['success' => true]); // Acknowledge to avoid Meta retries
        }

        // Step 3: Process delivery status updates
        $statuses = data_get($payload, 'entry.0.changes.0.value.statuses', []);
        foreach ($statuses as $status) {
            $this->handleStatusUpdate($status, $workspace);
        }

        // Step 4: Process incoming messages
        $messages = data_get($payload, 'entry.0.changes.0.value.messages', []);
        foreach ($messages as $message) {
            $this->handleIncomingMessage($message, $workspace);
        }

        return response()->json(['success' => true]);
    }

    // ─── PRIVATE HELPERS ───────────────────────────────────

    /**
     * Verify the X-Hub-Signature-256 header from Meta.
     * Returns true if signature is valid or not present (for local testing).
     */
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

    /**
     * Find the workspace that owns the receiving phone number.
     * Multi-tenant isolation: each workspace has its own WhatsApp credentials.
     */
    private function resolveWorkspace(array $payload): ?Workspace
    {
        $metadata           = data_get($payload, 'entry.0.changes.0.value.metadata');
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;
        $phoneNumberId      = $metadata['phone_number_id'] ?? null;

        if (!$displayPhoneNumber) {
            Log::warning('WhatsApp Webhook received without display_phone_number', $payload);
            return null;
        }

        $workspace = Workspace::whereJsonContains('settings->whatsapp_display_phone_number', $displayPhoneNumber)
            ->orWhereJsonContains('settings->whatsapp_phone_number_id', $phoneNumberId ?? '')
            ->first();

        if (!$workspace) {
            Log::warning("WhatsApp Webhook: unknown phone number: {$displayPhoneNumber}");
        }

        return $workspace;
    }

    /**
     * Handle a message delivery status update (sent, delivered, read, failed).
     * Updates both MessageLog (campaigns) and ConversationMessage (inbox).
     */
    private function handleStatusUpdate(array $status, Workspace $workspace): void
    {
        $wamid       = $status['id'] ?? null;
        $statusName  = $status['status'] ?? null;
        $recipientId = $status['recipient_id'] ?? null;

        if (!$wamid || !$statusName) return;

        // Update campaign message log (if this was a campaign message)
        $log = MessageLog::where('platform', 'whatsapp')
            ->where('platform_message_id', $wamid)
            ->first();

        if (!$log && $recipientId) {
            // Fallback: find the latest pending message for this recipient (exact match)
            $normalizedRecipient = '+' . ltrim(preg_replace('/[^0-9]/', '', $recipientId), '+');
            $log = MessageLog::where('platform', 'whatsapp')
                ->whereHas('contact', fn($q) => $q->where('phone', $normalizedRecipient))
                ->where('status', 'pending')
                ->latest()
                ->first();
        }

        if ($log) {
            $log->update(['status' => $statusName]);
        }

        // Update inbox conversation message
        $convMsg = ConversationMessage::where('platform_message_id', $wamid)->first();
        if ($convMsg) {
            $convMsg->update(['status' => $statusName]);

            // Broadcast status change for real-time UI updates
            try {
                broadcast(new \App\Events\MessageStatusUpdated($convMsg));
            } catch (\Exception $e) {
                Log::warning("Broadcast failed (status update): " . $e->getMessage());
            }
        }
    }

    /**
     * Handle an incoming customer message.
     * Creates or finds the contact, creates or finds the conversation,
     * saves the message, and broadcasts it.
     */
    private function handleIncomingMessage(array $message, Workspace $workspace): void
    {
        $fromPhone = $message['from'] ?? null;
        $msgType   = $message['type'] ?? 'text';
        $wamid     = $message['id'] ?? null;

        if (!$fromPhone) return;

        // Find or create the contact (exact match on normalized phone)
        $normalizedPhone = '+' . ltrim(preg_replace('/[^0-9]/', '', $fromPhone), '+');
        $contact = Contact::where('workspace_id', $workspace->id)
            ->where('phone', $normalizedPhone)
            ->first();

        if (!$contact) {
            $contact = Contact::create([
                'workspace_id' => $workspace->id,
                'name'         => $message['profile']['name'] ?? 'Unknown WhatsApp User',
                'phone'        => $normalizedPhone,
            ]);
        }

        // Find or create the conversation
        $conversation = Conversation::updateOrCreate(
            ['workspace_id' => $workspace->id, 'contact_id' => $contact->id, 'platform' => 'whatsapp'],
            ['last_message_at' => now(), 'status' => 'open']
        );

        // Build the message data
        $msgData = [
            'direction'           => 'inbound',
            'type'                => $msgType,
            'status'              => 'delivered',
            'sent_at'             => now(),
            'platform_message_id' => $wamid,
        ];

        if ($msgType === 'text') {
            $msgData['body'] = $message['text']['body'] ?? '';
        } else {
            // Media message (image, video, document, audio, sticker)
            $mediaObj          = $message[$msgType] ?? [];
            $mediaId            = $mediaObj['id'] ?? null;
            $msgData['media_url'] = $mediaId ? $this->resolveMediaUrl($mediaId, $workspace) : null;
            $msgData['caption']   = $mediaObj['caption'] ?? null;
            $msgData['body']      = "[Received {$msgType}]";
        }

        // Save the message
        $convMessage = $conversation->messages()->create($msgData);

        // Update conversation metadata (atomic increment to avoid race condition)
        $conversation->update([
            'last_message_at'      => now(),
            'last_incoming_at'     => now(),
            'unread_count'         => \Illuminate\Support\Facades\DB::raw('unread_count + 1'),
            'last_message_preview' => mb_substr($msgData['body'] ?? "[Received {$msgType}]", 0, 100),
        ]);

        // Broadcast for real-time inbox updates
        try {
            broadcast(new \App\Events\MessageReceived($convMessage->load('conversation.contact')));
        } catch (\Exception $e) {
            Log::warning("Broadcast failed (incoming message): " . $e->getMessage());
        }

        // Trigger automation workflows for incoming messages
        try {
            (new \App\Services\AutomationWorkflowService())->executeTrigger('incoming_message', [
                'workspace_id' => $workspace->id,
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'message_id' => $convMessage->id,
                'message' => $convMessage->body,
                'platform' => 'whatsapp',
            ]);
        } catch (\Throwable $e) {
            Log::warning("Automation workflow failed: " . $e->getMessage());
        }
    }

    /**
     * Resolve a WhatsApp media ID to a downloadable URL via Graph API.
     */
    private function resolveMediaUrl(string $mediaId, Workspace $workspace): ?string
    {
        try {
            $encToken = $workspace->settings['whatsapp_access_token'] ?? null;
            if (!$encToken) return null;

            try {
                $token = \Illuminate\Support\Facades\Crypt::decryptString($encToken);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $token = $encToken; // legacy unencrypted
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
