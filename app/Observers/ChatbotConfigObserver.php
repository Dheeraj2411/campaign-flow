<?php

namespace App\Observers;

use App\Models\ChatbotConfig;
use Illuminate\Support\Facades\Cache;

class ChatbotConfigObserver
{
    public function saved(ChatbotConfig $chatbotConfig): void
    {
        Cache::forget("workspace:{$chatbotConfig->workspace_id}:chatbot_config");
    }

    public function deleted(ChatbotConfig $chatbotConfig): void
    {
        Cache::forget("workspace:{$chatbotConfig->workspace_id}:chatbot_config");
    }
}
