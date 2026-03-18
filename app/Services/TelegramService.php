<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\Http;

class TelegramService
{
    private string $botToken;

    public function __construct(Workspace $workspace)
    {
        $settings = $workspace->settings ?? [];
        $this->botToken = $settings['telegram_bot_token'] ?? '';
    }

    public function sendMessage(string $chatId, string $message): array
    {
        if (empty($this->botToken)) {
            throw new \Exception("Telegram Bot Token is missing for this workspace.");
        }

        if (!is_numeric($chatId) && !str_starts_with($chatId, '@')) {
            $chatId = '@' . $chatId;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::post($url, [
            'chat_id' => $chatId,
            'text'    => $message,
        ]);

        if ($response->failed()) {
            throw new \Exception("Telegram API Error: " . $response->body());
        }

        $data = $response->json();
        return [
            'success'             => true,
            'platform_message_id' => $data['result']['message_id'] ?? null,
            'raw'                 => $data,
        ];
    }

    /**
     * Send Media to Telegram
     */
    public function sendMedia(string $chatId, string $type, string $url, ?string $caption = null): array
    {
        if (empty($this->botToken)) {
            throw new \Exception("Telegram Bot Token is missing for this workspace.");
        }

        $method = match($type) {
            'image'    => 'sendPhoto',
            'video'    => 'sendVideo',
            'document' => 'sendDocument',
            default    => 'sendDocument',
        };

        $urlEndpoint = "https://api.telegram.org/bot{$this->botToken}/{$method}";

        $payload = [
            'chat_id' => $chatId,
            $type === 'image' ? 'photo' : $type => $url,
        ];

        if ($caption) {
            $payload['caption'] = $caption;
        }

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::post($urlEndpoint, $payload);

        if ($response->failed()) {
            throw new \Exception("Telegram API Error: " . $response->body());
        }

        $data = $response->json();
        return [
            'success'             => true,
            'platform_message_id' => $data['result']['message_id'] ?? null,
            'raw'                 => $data,
        ];
    }
}
