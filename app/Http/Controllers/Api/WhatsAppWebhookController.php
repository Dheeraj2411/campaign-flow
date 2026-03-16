<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Meta Webhook Verification
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        // You would normally check $token against your env or DB.
        // For CampaignFlow prototype, we just accept if token matches our env or just return challenge.
        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token', 'campaignflow_secret')) {
                return response($challenge, 200);
            }
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming webhooks (delivery status + inbound messages)
     */
    public function handle(Request $request)
    {
        // 0. Verify Signature
        $signature = $request->header('X-Hub-Signature-256');
        $secret = config('services.whatsapp.app_secret', 'campaignflow_app_secret');

        if ($signature) {
            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
            if (!hash_equals($expected, $signature)) {
                Log::warning('WhatsApp Webhook signature mismatch.');
                return response('Forbidden', 403);
            }
        }

        $payload = $request->all();

        // ** MULTI-TENANT ISOLATION **
        // Find which Workspace owns the receiving phone number
        $metadata = data_get($payload, 'entry.0.changes.0.value.metadata');
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;

        if (!$displayPhoneNumber) {
            Log::warning('WhatsApp Webhook received without display_phone_number', $payload);
            return response()->json(['success' => true]); // Acknowledge to Meta to avoid retries
        }

        // Search workspaces for the exact display_phone_number in their settings
        // Stored as 'whatsapp_phone_number' or similar during setup
        $workspace = \App\Models\Workspace::whereJsonContains('settings->whatsapp_display_phone_number', $displayPhoneNumber)
            ->orWhereJsonContains('settings->whatsapp_phone_number_id', $metadata['phone_number_id'] ?? '')
            ->first();

        if (!$workspace) {
            Log::warning("WhatsApp Webhook received for an unknown Phone Number: {$displayPhoneNumber}");
            return response()->json(['success' => true]);
        }

        // 1. Look for status updates (Message Delivery Receipts)
        $statuses = data_get($payload, 'entry.0.changes.0.value.statuses', []);
        foreach ($statuses as $status) {
            $wamid         = $status['id'] ?? null;
            $statusName    = $status['status'] ?? null; // 'sent', 'delivered', 'read', 'failed'
            $recipientId   = $status['recipient_id'] ?? null;

            if ($statusName && $recipientId) {
                // Find pending MessageLog for this phone number and workspace
                $log = MessageLog::where('platform', 'whatsapp')
                    ->whereHas('contact', function ($q) use ($recipientId, $workspace) {
                        // recipientId might be prefixed with country code, try to match robustly
                        $q->where('phone', 'like', "%{$recipientId}")
                          ->where('workspace_id', $workspace->id);
                    })
                    ->latest()
                    ->first();

                if ($log) {
                    $updateData = ['status' => $statusName];
                    
                    if ($statusName === 'failed') {
                        \Illuminate\Support\Facades\Log::error("WhatsApp Webhook Failed Payload: " . json_encode($status));
                        
                        if (isset($status['errors'][0])) {
                            $error = $status['errors'][0];
                            $updateData['error_message'] = "Webhook Error: {$error['title']} ({$error['code']}) - " . ($error['message'] ?? $error['error_data']['details'] ?? '');
                        } else {
                            $updateData['error_message'] = "Webhook Error: Unknown structure. Check laravel.log for full payload.";
                        }
                    }
                    
                    $log->update($updateData);
                    \Illuminate\Support\Facades\Log::info("WhatsApp Webhook updated MessageLog {$log->id} to {$statusName}");
                } else {
                    \Illuminate\Support\Facades\Log::warning("WhatsApp Webhook could not find pending MessageLog for {$recipientId}");
                }
            }
        }

        // 2. Look for incoming messages (Customer Replies)
        $messages = data_get($payload, 'entry.0.changes.0.value.messages', []);
        foreach ($messages as $message) {
            $fromPhone = $message['from'] ?? null;
            $text = $message['text']['body'] ?? null;
            
            if ($fromPhone && $text) {
                // Find contact by phone within the specific workspace!
                $contact = \App\Models\Contact::where('workspace_id', $workspace->id)
                    ->where('phone', 'like', "%{$fromPhone}")
                    ->first();

                // Auto-create inbound lead if contact doesn't exist
                if (!$contact) {
                    $contact = \App\Models\Contact::create([
                        'workspace_id' => $workspace->id,
                        'name'         => $message['profile']['name'] ?? 'Unknown WhatsApp User',
                        'phone'        => '+' . ltrim($fromPhone, '+'),
                    ]);
                }

                $conversation = \App\Models\Conversation::updateOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'contact_id'   => $contact->id,
                        'platform'     => 'whatsapp',
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
            }
        }

        return response()->json(['success' => true]);
    }
}
