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

        // Ensure string has @ if it's a username, though Telegram usually requires numerical Chat IDs for users.
        if (!is_numeric($chatId) && !str_starts_with($chatId, '@')) {
            $chatId = '@' . $chatId;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $response = Http::post($url, [
            'chat_id' => $chatId,
            'text'    => $message,
        ]);

        if ($response->failed()) {
            throw new \Exception("Telegram API Error: " . $response->body());
        }

        return $response->json();
    }
}
