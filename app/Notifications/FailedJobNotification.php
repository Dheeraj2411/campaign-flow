<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class FailedJobNotification extends Notification
{
    use Queueable;

    public $jobName;
    public $exceptionMessage;
    public $failedAt;
    public $queueName;

    public function __construct(string $jobName, string $exceptionMessage, string $queueName, $failedAt)
    {
        $this->jobName = $jobName;
        $this->exceptionMessage = $exceptionMessage;
        $this->queueName = $queueName;
        $this->failedAt = $failedAt;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('PingOS — Failed Job Alert: ' . $this->jobName)
            ->error()
            ->greeting('Hello,')
            ->line("A job has failed on the [{$this->queueName}] queue.")
            ->line("Job: {$this->jobName}")
            ->line("Failed at: {$this->failedAt}")
            ->line("Error Message:")
            ->line(str($this->exceptionMessage)->limit(500))
            ->action('View Horizon Dashboard', url('/horizon'));
    }

    public function toArray($notifiable)
    {
        return [
            'job_name' => $this->jobName,
            'exception' => $this->exceptionMessage,
            'queue' => $this->queueName,
            'failed_at' => $this->failedAt,
            'type' => 'failed_job'
        ];
    }
}
