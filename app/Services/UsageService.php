<?php

namespace App\Services;

use App\Models\Workspace;
use App\Models\Contact;
use App\Models\MessageLog;
use Illuminate\Support\Facades\Cache;

class UsageService
{
    /**
     * Check if the workspace can perform an action based on its plan limits.
     */
    public function canPerformAction(Workspace $workspace, string $action): bool
    {
        $planSlug = $workspace->plan ?? 'free';
        /** @var \App\Models\Plan $limits */
        $limits = Cache::remember("plan_limits:{$planSlug}", 3600, function() use ($planSlug) {
            return \App\Models\Plan::where('slug', $planSlug)->first();
        });

        if (!$limits) return false;

        switch ($action) {
            case 'add_contact':
                if ($limits->max_contacts === -1) return true;
                return Contact::where('workspace_id', $workspace->id)->count() < $limits->max_contacts;

            case 'send_message':
                if ($limits->max_messages_per_month === -1) return true;
                
                $monthKey = "usage:{$workspace->id}:messages:" . date('Y-m');
                $usage = Cache::remember($monthKey, 60, function() use ($workspace) {
                    return MessageLog::join('campaigns', 'message_logs.campaign_id', '=', 'campaigns.id')
                        ->where('campaigns.workspace_id', $workspace->id)
                        ->whereBetween('message_logs.created_at', [
                            now()->startOfMonth(),
                            now()->endOfMonth()
                        ])
                        ->count();
                });

                return $usage < $limits->max_messages_per_month;

            case 'create_campaign':
                 if ($limits->max_campaigns === -1) return true;
                 return \App\Models\Campaign::where('workspace_id', $workspace->id)->count() < $limits->max_campaigns;
        }

        return false;
    }

    public function canAddContact(Workspace $workspace): bool
    {
        return $this->canPerformAction($workspace, 'add_contact');
    }

    public function canCreateCampaign(Workspace $workspace): bool
    {
        return $this->canPerformAction($workspace, 'create_campaign');
    }

    public function canSendMessage(Workspace $workspace, int $count = 1): bool
    {
        $planSlug = $workspace->plan ?? 'free';
        $limits = Cache::remember("plan_limits:{$planSlug}", 3600, function() use ($planSlug) {
            return \App\Models\Plan::where('slug', $planSlug)->first();
        });

        if (!$limits) return false;
        if ($limits->max_messages_per_month === -1) return true;

        $monthKey = "usage:{$workspace->id}:messages:" . date('Y-m');
        $usage = Cache::remember($monthKey, 60, function() use ($workspace) {
            return MessageLog::join('campaigns', 'message_logs.campaign_id', '=', 'campaigns.id')
                ->where('campaigns.workspace_id', $workspace->id)
                ->whereBetween('message_logs.created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ])
                ->count();
        });

        return ($usage + $count) <= $limits->max_messages_per_month;
    }

    /**
     * Get current usage statistics for a workspace.
     */
    public function getUsageStats(Workspace $workspace): array
    {
        $workspaceId = $workspace->id;
        $planSlug = $workspace->plan ?? 'free';

        return Cache::remember("usage_stats:{$workspaceId}", 60, function() use ($workspace, $planSlug) {
            /** @var \App\Models\Plan $limits */
            $limits = Cache::remember("plan_limits:{$planSlug}", 3600, function() use ($planSlug) {
                return \App\Models\Plan::where('slug', $planSlug)->first();
            });

            if (!$limits || !isset($limits->max_contacts)) {
                return [
                    'plan_name' => ucfirst($planSlug),
                    'contacts' => ['current' => 0, 'limit' => 0, 'percent' => 0],
                    'messages' => ['current' => 0, 'limit' => 0, 'percent' => 0],
                    'campaigns' => ['current' => 0, 'limit' => 0, 'percent' => 0],
                ];
            }

            $contactsCount = Contact::where('workspace_id', $workspace->id)->count();
            $campaignsCount = \App\Models\Campaign::where('workspace_id', $workspace->id)->count();
            
            $messagesCount = MessageLog::join('campaigns', 'message_logs.campaign_id', '=', 'campaigns.id')
                ->where('campaigns.workspace_id', $workspace->id)
                ->whereBetween('message_logs.created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ])
                ->count();

            return [
                'plan_name' => $limits->name ?? ucfirst($planSlug),
                'contacts' => [
                    'current' => $contactsCount,
                    'limit'   => $limits->max_contacts,
                    'percent' => $limits->max_contacts > 0 ? round(($contactsCount / $limits->max_contacts) * 100) : 0,
                ],
                'messages' => [
                    'current' => $messagesCount,
                    'limit'   => $limits->max_messages_per_month,
                    'percent' => $limits->max_messages_per_month > 0 ? round(($messagesCount / $limits->max_messages_per_month) * 100) : 0,
                ],
                'campaigns' => [
                    'current' => $campaignsCount,
                    'limit'   => $limits->max_campaigns,
                    'percent' => $limits->max_campaigns > 0 ? round(($campaignsCount / $limits->max_campaigns) * 100) : 0,
                ]
            ];
        });
    }
}
