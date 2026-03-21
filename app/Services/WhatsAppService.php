<?php

namespace App\Services;

use App\Models\Workspace;
use App\Notifications\WhatsAppTokenStatusNotification;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class WhatsAppService
{
    private string $phoneNumberId;
    private string $accessToken;
    private Workspace $workspace;

    public function __construct(Workspace $workspace)
    {
        $this->workspace = $workspace;
        $settings = $workspace->getCachedSettings() ?? [];
        $this->phoneNumberId = $settings['whatsapp_phone_number_id'] ?? '';

        $encryptedToken = $settings['whatsapp_access_token'] ?? '';
        try {
            $this->accessToken = $encryptedToken ? Crypt::decryptString($encryptedToken) : '';
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Fallback: token may not be encrypted yet (pre-migration data)
            $this->accessToken = $encryptedToken;
        }
    }

    /**
     * Verify if the token is valid by calling Meta /me endpoint.
     */
    public function verifyToken(): bool
    {
        if (empty($this->phoneNumberId) || empty($this->accessToken)) {
            $this->notifyUsers('missing');
            return false;
        }

        $cacheKey = "whatsapp_token_valid:{$this->workspace->id}";

        // Only return cached result if token was previously valid
        if (Cache::get($cacheKey) === true) {
            return true;
        }

        try {
            /** @var Response $response */
            $response = Http::withToken($this->accessToken)
                ->get("https://graph.facebook.com/v22.0/me");

            if ($response->status() === 401 || $response->status() === 403) {
                $this->notifyUsers('expired');
                return false;
            }

            if ($response->successful()) {
                Cache::put($cacheKey, true, 600);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp Token Verification Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trigger a notification for all workspace owners/members.
     */
    private function notifyUsers(string $type): void
    {
        try {
            $users = $this->workspace->users;
            Notification::send($users, new WhatsAppTokenStatusNotification($type));
        } catch (\Exception $e) {
            Log::warning("Failed to send token status notification: " . $e->getMessage());
        }
    }

    /**
     * Normalize phone number to digits-only international format (e.g. 919910494819).
     * Strips all non-digit characters but preserves the country code as-is.
     */
    private function normalizePhone(string $to): string
    {
        return preg_replace('/[^0-9]/', '', $to);
    }

    /**
     * Send a free-form text message (only works within 24h reply window)
     */
    public function sendMessage(string $to, string $message): array
    {
        $this->validateCredentials();
        $to = $this->normalizePhone($to);

        $url = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";

        /** @var Response $response */
        $response = Http::withToken($this->accessToken)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);

        return $this->handleResponse($response);
    }

    /**
     * Send media (image, video, document, audio)
     */
    public function sendMedia(string $to, string $type, string $url, ?string $caption = null): array
    {
        $this->validateCredentials();
        $to = $this->normalizePhone($to);

        $apiUrl = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => $type,
            $type               => ['link' => $url],
        ];

        if ($caption && in_array($type, ['image', 'video', 'document'])) {
            $payload[$type]['caption'] = $caption;
        }

        /** @var Response $response */
        $response = Http::withToken($this->accessToken)->post($apiUrl, $payload);

        return $this->handleResponse($response);
    }

    /**
     * Send a Meta-approved template message
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode = 'en_US', array $parameters = []): array
    {
        $this->validateCredentials();
        $to = $this->normalizePhone($to);

        $url = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";

        $components = [];
        if (!empty($parameters)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(fn($val) => ['type' => 'text', 'text' => $val], $parameters),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => ['code' => $languageCode],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        /** @var Response $response */
        $response = Http::withToken($this->accessToken)->post($url, $payload);

        return $this->handleResponse($response);
    }

    public function sendInteractiveButtons(string $to, string $bodyText, array $buttons): array
    {
        $this->validateCredentials();
        $to = $this->normalizePhone($to);
        $url = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";

        $formattedButtons = array_map(function($btn) {
            return [
                'type' => 'reply',
                'reply' => [
                    'id' => (string) ($btn['id'] ?? uniqid()),
                    'title' => substr($btn['title'] ?? 'Button', 0, 20)
                ]
            ];
        }, array_slice($buttons, 0, 3));

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'button',
                'body' => ['text' => substr($bodyText, 0, 1024)],
                'action' => ['buttons' => $formattedButtons]
            ]
        ];

        /** @var Response $response */
        $response = Http::withToken($this->accessToken)->post($url, $payload);
        return $this->handleResponse($response);
    }

    public function sendInteractiveList(string $to, string $headerText, string $bodyText, string $buttonLabel, array $sections): array
    {
        $this->validateCredentials();
        $to = $this->normalizePhone($to);
        $url = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'list',
                'header' => ['type' => 'text', 'text' => substr($headerText, 0, 60)],
                'body' => ['text' => substr($bodyText, 0, 1024)],
                'action' => [
                    'button' => substr($buttonLabel, 0, 20),
                    'sections' => $sections
                ]
            ]
        ];

        /** @var Response $response */
        $response = Http::withToken($this->accessToken)->post($url, $payload);
        return $this->handleResponse($response);
    }

    public function sendQuickReplies(string $to, string $bodyText, array $replies): array
    {
        // shorthand for a single section list if there are > 3 replies
        return $this->sendInteractiveList($to, 'Options', $bodyText, 'Select Option', [
            [
                'title' => 'Available Options',
                'rows' => $replies
            ]
        ]);
    }

    private function handleResponse(Response $response): array
    {
        if ($response->status() === 401 || $response->status() === 403) {
            $this->notifyUsers('expired');
            Cache::put("whatsapp_token_valid:{$this->workspace->id}", false, 600);
            throw new \Exception("WhatsApp Token Expired. Notification sent to workspace users.");
        }

        if ($response->failed()) {
            throw new \Exception("WhatsApp API Error: " . $response->body());
        }

        $data = $response->json();
        return [
            'success'             => true,
            'platform_message_id' => $data['messages'][0]['id'] ?? null,
            'raw'                 => $data,
        ];
    }

    private function validateCredentials(): void
    {
        if (empty($this->phoneNumberId) || empty($this->accessToken)) {
            $this->notifyUsers('missing');
            throw new \Exception("WhatsApp API credentials are missing for this workspace.");
        }
    }
}
