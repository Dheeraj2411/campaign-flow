<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Models\Workspace;
use App\Notifications\FailedJobNotification;

class SendFailedJobNotification
{
    public function handle(JobFailed $event)
    {
        $jobClass = $event->job->resolveName();
        $cacheKey = "failed_job_alert:" . md5($jobClass);

        if (Cache::has($cacheKey)) {
            return; // Throttle to 1 per hour per job class
        }

        Cache::put($cacheKey, true, now()->addHour());

        $payload = $event->job->payload();
        $workspaceId = null;

        // Try to guess workspace from command properties if it's a serialized model
        if (isset($payload['data']['command'])) {
            try {
                $commandObj = unserialize($payload['data']['command']);
                if (isset($commandObj->workspaceId)) {
                    $workspaceId = $commandObj->workspaceId;
                } elseif (isset($commandObj->workspace) && isset($commandObj->workspace->id)) {
                    $workspaceId = $commandObj->workspace->id;
                } elseif (isset($commandObj->campaign) && isset($commandObj->campaign->workspace_id)) {
                    $workspaceId = $commandObj->campaign->workspace_id;
                }
            } catch (\Exception $e) {
                // Ignore unserialize errors
            }
        }

        $notifiable = null;

        if ($workspaceId) {
            $workspace = Workspace::find($workspaceId);
            if ($workspace && $workspace->ownerInstance) {
                $notifiable = $workspace->ownerInstance;
            } elseif ($workspace && $workspace->owner) {
                $notifiable = $workspace->owner;
            }
        }

        if (!$notifiable) {
            // Fallback to admin email from config
            $adminEmail = config('horizon.notifications.email') ?: 'admin@pingos.com';
            $notifiable = new \Illuminate\Notifications\AnonymousNotifiable;
            $notifiable->route('mail', $adminEmail);
        }

        Notification::send(
            $notifiable, 
            new FailedJobNotification(
                $jobClass,
                $event->exception->getMessage(),
                $event->job->getQueue(),
                now()->toDateTimeString()
            )
        );
    }
}
