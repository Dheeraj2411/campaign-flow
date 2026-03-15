<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    private function activeWorkspace()
    {
        return \App\Models\Workspace::find(auth()->user()->active_workspace_id);
    }

    public function index()
    {
        $workspace = $this->activeWorkspace();
        abort_if(!$workspace, 404, 'No active workspace found. Please contact the administrator.');

        return Inertia::render('Settings/Index', [
            'workspace' => $workspace,
            'app_url'   => config('app.url'),
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $workspace = $this->activeWorkspace();
        abort_if(!$workspace, 404, 'No active workspace found.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace->update($data);

        return back()->with('success', 'Workspace name updated.');
    }

    public function updateApi(Request $request)
    {
        $workspace = $this->activeWorkspace();
        abort_if(!$workspace, 404, 'No active workspace found.');

        $data = $request->validate([
            'whatsapp_phone_number_id' => 'nullable|string|max:255',
            'whatsapp_access_token'    => 'nullable|string|max:500',
            'telegram_bot_token'       => 'nullable|string|max:255',
        ]);

        $settings = $workspace->settings ?? [];

        // Check if Telegram Bot Token is being changed
        $oldTelegramToken = $settings['telegram_bot_token'] ?? null;
        $newTelegramToken = $data['telegram_bot_token'] ?? null;

        if ($newTelegramToken !== $oldTelegramToken) {
            try {
                if ($newTelegramToken) {
                    // Register Webhook with Telegram
                    $webhookUrl = config('app.url') . "/telegram/webhook/{$workspace->slug}";
                    
                    // We must call json() or similar on the Response, or explicitly get the HttpResponse object
                    $httpResponse = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$newTelegramToken}/setWebhook", [
                        'url' => $webhookUrl,
                    ]);

                    /** @var \Illuminate\Http\Client\Response $httpResponse */
                    if (!$httpResponse->successful()) {
                        $errorDescription = $httpResponse->json('description') ?? 'Unknown error';
                        return back()->with('error', 'Failed to register Telegram Webhook: ' . $errorDescription);
                    }
                } elseif ($oldTelegramToken && !$newTelegramToken) {
                    // Delete Webhook from Telegram
                    \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$oldTelegramToken}/deleteWebhook");
                }
            } catch (\Exception $e) {
                return back()->with('error', 'Error communicating with Telegram API: ' . $e->getMessage());
            }
        }

        $settings = array_merge($settings, $data);
        $workspace->update(['settings' => $settings]);

        return back()->with('success', 'API credentials saved successfully.');
    }
}
