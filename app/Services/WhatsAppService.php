<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $phoneNumberId;
    private string $accessToken;

    public function __construct(Workspace $workspace)
    {
        $settings = $workspace->settings ?? [];
        $this->phoneNumberId = $settings['whatsapp_phone_number_id'] ?? '';
        $this->accessToken   = $settings['whatsapp_access_token'] ?? '';
    }

    /**
     * Normalize phone number to international format (e.g. 919910494819)
     */
    private function normalizePhone(string $to): string
    {
        $to = preg_replace('/[^0-9]/', '', $to);

        // Auto-add India country code if number is 10 digits
        if (strlen($to) === 10) {
            $to = '91' . $to;
        }

        return $to;
    }

    /**
     * Send a free-form text message (only works within 24h reply window)
     */
    public function sendMessage(string $to, string $message): array
    {
        $this->validateCredentials();
        $to = $this->normalizePhone($to);

        $url = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";

        $response = Http::withToken($this->accessToken)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => [
                    'body' => $message,
                ],
            ]);

        if ($response->failed()) {
            // If free-form fails, try sending as template instead
            Log::warning("WhatsApp free-form failed for {$to}, trying hello_world template...");
            return $this->sendTemplate($to, 'hello_world', 'en_US');
        }

        return $response->json();
    }

    /**
     * Send a Meta-approved template message (works for first contact / cold outreach)
     */
    public function sendTemplate(string $to, string $templateName = 'hello_world', string $languageCode = 'en_US'): array
    {
        $this->validateCredentials();
        $to = $this->normalizePhone($to);

        $url = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";

        $response = Http::withToken($this->accessToken)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'template',
                'template'          => [
                    'name'     => $templateName,
                    'language' => [
                        'code' => $languageCode,
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new \Exception("WhatsApp API Error: " . $response->body());
        }

        return $response->json();
    }

    private function validateCredentials(): void
    {
        if (empty($this->phoneNumberId) || empty($this->accessToken)) {
            throw new \Exception("WhatsApp API credentials are missing for this workspace.");
        }
    }
}
