<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WhatsAppTokenStatusNotification extends Notification
{
    use Queueable;

    protected string $type; // 'missing' or 'expired'

    /**
     * Create a new notification instance.
     */
    public function __construct(string $type = 'expired')
    {
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        if ($this->type === 'missing') {
            return [
                'title' => 'WhatsApp Credentials Missing',
                'message' => 'Your WhatsApp API credentials are not configured. Please add them to send messages.',
                'icon' => 'key',
                'color' => 'warning',
                'url' => route('settings'),
            ];
        }

        return [
            'title' => 'WhatsApp Token Expired',
            'message' => 'Your Meta Access Token has expired or is invalid. Please update it in settings.',
            'icon' => 'gpp_maybe',
            'color' => 'danger',
            'url' => route('settings'),
        ];
    }
}
