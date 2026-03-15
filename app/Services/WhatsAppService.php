<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\Http;

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

    public function sendMessage(string $to, string $message): array
    {
        if (empty($this->phoneNumberId) || empty($this->accessToken)) {
            throw new \Exception("WhatsApp API credentials are missing for this workspace.");
        }

        // Clean phone number (Meta expects international format without '+' or spaces)
        $to = preg_replace('/[^0-9]/', '', $to);

        $url = "https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages";

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
            throw new \Exception("WhatsApp API Error: " . $response->body());
        }

        return $response->json();
    }
}
