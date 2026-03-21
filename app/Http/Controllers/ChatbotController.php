<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ChatbotConfig;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function show(Request $request)
    {
        $workspace = $request->user()->activeWorkspace;
        $config = ChatbotConfig::firstOrCreate(
            ['workspace_id' => $workspace->id],
            [
                'is_enabled' => false,
                'model' => 'gpt-4o-mini',
                'system_prompt' => '',
                'trigger_keywords' => [],
                'off_hours_only' => false,
                'escalate_keyword' => 'human'
            ]
        );

        return response()->json($config);
    }

    public function update(Request $request)
    {
        $workspace = $request->user()->activeWorkspace;
        $config = ChatbotConfig::getCachedForWorkspace($workspace->id);
        if (!$config) abort(404);

        $data = $request->validate([
            'is_enabled' => 'boolean',
            'openai_api_key' => 'nullable|string',
            'model' => 'required|string',
            'system_prompt' => 'nullable|string',
            'trigger_keywords' => 'nullable|array',
            'off_hours_only' => 'boolean',
            'escalate_keyword' => 'nullable|string'
        ]);

        if (array_key_exists('openai_api_key', $data) && empty($data['openai_api_key'])) {
            unset($data['openai_api_key']);
        }

        $config->update($data);

        return response()->json(['success' => true]);
    }

    public function toggleConversationBot(Conversation $conversation)
    {
        $conversation->update([
            'bot_active' => !$conversation->bot_active
        ]);

        return response()->json(['bot_active' => $conversation->bot_active]);
    }
}
