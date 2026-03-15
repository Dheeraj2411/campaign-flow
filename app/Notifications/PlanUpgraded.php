<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlanUpgraded extends Notification
{
    use Queueable;

    public $plan;

    /**
     * Create a new notification instance.
     */
    public function __construct($plan)
    {
        $this->plan = $plan;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Workspace Plan Upgraded',
            'message' => 'Your workspace plan has been upgraded to ' . ucfirst($this->plan) . '.',
            'icon' => 'workspace_premium',
            'color' => 'success'
        ];
    }
}
