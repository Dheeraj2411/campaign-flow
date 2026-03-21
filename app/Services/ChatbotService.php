<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Workspace;
use App\Models\ChatbotConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    public function shouldRespond(Conversation $conversation, string $message): bool
    {
        $workspace = $conversation->workspace;
        $config = ChatbotConfig::getCachedForWorkspace($workspace->id);

        if (!$config || !$config->is_enabled || !$conversation->bot_active) {
            return false;
        }

        if ($config->off_hours_only) {
            $hour = now($workspace->timezone ?? 'UTC')->hour;
            $isWeekend = now($workspace->timezone ?? 'UTC')->isWeekend();
            if (!$isWeekend && $hour >= 8 && $hour < 18) {
                return false;
            }
        }

        if (!empty($config->trigger_keywords)) {
            $messageLower = strtolower($message);
            $found = false;
            foreach ($config->trigger_keywords as $keyword) {
                if (str_contains($messageLower, strtolower($keyword))) {
                    $found = true;
                    break;
                }
            }
            if (!$found) return false;
        }

        return true;
    }

    public function generateReply(Workspace $workspace, Conversation $conversation, string $userMessage): string
    {
        $config = ChatbotConfig::getCachedForWorkspace($workspace->id);
        if (!$config || !$config->openai_api_key) {
            return "Sorry, the chatbot is not properly configured.";
        }

        $systemPrompt = ($config->system_prompt ?? "You are a helpful assistant.") . "\nYou are a helpful assistant for {$workspace->name}. Keep replies concise and friendly.";

        $history = $conversation->messages()
            ->latest()
            ->take(10)
            ->get()
            ->reverse();

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        foreach ($history as $msg) {
            if ($msg->type === 'text') {
                $messages[] = [
                    'role' => $msg->direction === 'inbound' ? 'user' : 'assistant',
                    'content' => $msg->body ?? ''
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = Http::withToken($config->openai_api_key)
                ->timeout(15)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $config->model ?? 'gpt-4o-mini',
                    'messages' => $messages,
                    'max_tokens' => 250,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? 'I am not sure how to respond.';
            }

            Log::error("OpenAI API error: " . $response->body());
            return "I am currently experiencing technical difficulties.";
        } catch (\Exception $e) {
            Log::error("ChatbotService exception: " . $e->getMessage());
            return "I am offline right now.";
        }
    }

    public function handleEscalation(Conversation $conversation): void
    {
        $conversation->update([
            'bot_active' => false,
            'bot_escalated_at' => now()
        ]);

        try {
            $aws = new \App\Services\AutomationWorkflowService();
            $aws->trigger('bot_escalated', [
                'workspace_id' => $conversation->workspace_id,
                'contact_id' => $conversation->contact_id,
                'conversation_id' => $conversation->id,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to trigger bot_escalated automation: " . $e->getMessage());
        }
    }
}
