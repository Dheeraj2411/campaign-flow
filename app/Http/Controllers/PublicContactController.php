<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class PublicContactController extends Controller
{
    public function show(Workspace $workspace)
    {
        $settings = $workspace->settings ?? [];
        $botToken = $settings['telegram_bot_token'] ?? null;
        $botUsername = null;

        if ($botToken) {
            try {
                // Cache this for better performance in a real app
                $response = Http::get("https://api.telegram.org/bot{$botToken}/getMe");
                if ($response->successful()) {
                    $botUsername = $response->json('result.username');
                }
            } catch (\Exception $e) {
                // Fallback or log error
            }
        }

        return Inertia::render('Public/Connect', [
            'workspace' => [
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            'botUsername' => $botUsername,
        ]);
    }
}
