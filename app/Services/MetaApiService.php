<?php

namespace App\Services;

use App\Models\MessageTemplate;
use App\Models\Workspace;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

class MetaApiService
{
    protected string $baseUrl = 'https://graph.facebook.com/v20.0';
    protected string $accessToken;
    protected string $businessAccountId;
    protected Workspace $workspace;

    public function __construct(Workspace $workspace)
    {
        $this->workspace = $workspace;

        $encryptedToken = $workspace->settings['whatsapp_access_token'] ?? '';
        try {
            $this->accessToken = $encryptedToken ? Crypt::decryptString($encryptedToken) : '';
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            Log::error('MetaApiService: Failed to decrypt WhatsApp access token. This usually means the APP_KEY changed or differs between containers.', [
                'workspace_id' => $workspace->id,
            ]);
            throw new \Exception(
                'Failed to decrypt WhatsApp access token. '
                . 'Please re-save your API credentials in Settings. '
                . '(Cause: APP_KEY may have changed or differs between Docker containers.)'
            );
        }

        $this->businessAccountId = $workspace->settings['whatsapp_business_account_id'] ?? '';
    }

    /**
     * Submit a template to Meta for approval.
     */
    public function createTemplate(MessageTemplate $template)
    {
        if (empty($this->accessToken) || empty($this->businessAccountId)) {
            throw new \Exception("WhatsApp API credentials missing in workspace settings.");
        }

        $endpoint = "{$this->baseUrl}/{$this->businessAccountId}/message_templates";

        /** @var Response $response */
        $response = Http::withToken($this->accessToken)->post($endpoint, [
            'name'       => $template->name,
            'category'   => $template->category,
            'language'   => $template->language,
            'components' => $this->formatComponents($template->content_structure),
        ]);

        if ($response->failed()) {
            Log::error("Meta API Template Creation Failed", [
                'template_id' => $template->id,
                'response'    => $response->json()
            ]);
            throw new \Exception($response->json()['error']['message'] ?? "Unknown error from Meta API");
        }

        return $response->json();
    }

    /**
     * Format our local content_structure to Meta's API format.
     */
    protected function formatComponents(array $structure): array
    {
        $components = [];

        // Header
        if (!empty($structure['header_type']) && $structure['header_type'] !== 'NONE') {
            $header = [
                'type' => 'HEADER',
                'format' => $structure['header_type'] === 'TEXT' ? 'TEXT' : $structure['header_type'],
            ];

            if ($structure['header_type'] === 'TEXT') {
                $header['text'] = $structure['header'] ?? '';
            } else {
                // For MEDIA headers in templates, Meta often requires an example/placeholder
                $header['example'] = [
                    'header_handle' => ['https://example.com/placeholder.png'] 
                ];
            }
            $components[] = $header;
        }

        // Body
        if (!empty($structure['body'])) {
            $components[] = [
                'type' => 'BODY',
                'text' => $structure['body'],
            ];
        }

        // Footer
        if (!empty($structure['footer'])) {
            $components[] = [
                'type' => 'FOOTER',
                'text' => $structure['footer'],
            ];
        }

        // Calling Permission Component
        if (($structure['type'] ?? '') === 'CALLING_PERMISSIONS') {
            $components[] = [
                'type' => 'CALLING_PERMISSION',
            ];
        }

        // Buttons
        $buttons = $structure['buttons'] ?? [];
        if (($structure['type'] ?? '') === 'CATALOGUE') {
            // Auto-add catalog button for catalogue templates
            $buttons[] = [
                'type' => 'CATALOG',
            ];
        }

        if (!empty($buttons)) {
            $components[] = [
                'type' => 'BUTTONS',
                'buttons' => $buttons,
            ];
        }

        return $components;
    }

    /**
     * Sync all templates from Meta for this workspace.
     */
    public function syncTemplates()
    {
        $endpoint = "{$this->baseUrl}/{$this->businessAccountId}/message_templates";
        
        $response = Http::withToken($this->accessToken)->get($endpoint);
        
        if ($response->failed()) {
            throw new \Exception("Failed to fetch templates from Meta: " . $response->body());
        }

        $remoteTemplates = $response->json()['data'] ?? [];
        
        foreach ($remoteTemplates as $remote) {
            $template = MessageTemplate::where('workspace_id', $this->workspace->id)
                ->where('name', $remote['name'])
                ->first();
            
            if ($template) {
                // Map Meta statuses to our local statuses
                $status = $remote['status'];
                if ($status === 'REJECTED') {
                    // Extract rejection reason if available
                    $reason = $remote['template_rejection_reasons'][0]['reason'] ?? 'Policy violation or generic content';
                    $template->update([
                        'status' => 'REJECTED',
                        'reason' => $reason
                    ]);
                } else {
                    $template->update([
                        'status' => $status,
                        'reason' => null
                    ]);
                }
                
                if (empty($template->meta_template_id)) {
                    $template->update(['meta_template_id' => $remote['id']]);
                }
            }
        }
        
        return count($remoteTemplates);
    }

    /**
     * Get the latest status of a single template from Meta.
     */
    public function getTemplateStatus(string $name)
    {
        $endpoint = "{$this->baseUrl}/{$this->businessAccountId}/message_templates";

        /** @var Response $response */
        $response = Http::withToken($this->accessToken)->get($endpoint, [
            'name' => $name
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json()['data'] ?? [];
        return $data[0] ?? null;
    }
}
