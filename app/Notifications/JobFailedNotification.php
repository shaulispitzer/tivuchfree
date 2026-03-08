<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Events\JobFailed;

class JobFailedNotification extends Notification
{
    public function __construct(private readonly JobFailed $event) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $jobName = class_basename($this->event->job->resolveName());
        $errorMessage = str($this->event->exception->getMessage())->limit(300)->toString();

        return (new MailMessage)
            ->subject("Failed Job: {$jobName}")
            ->greeting('A queue job has failed.')
            ->line("**Job:** {$this->event->job->resolveName()}")
            ->line("**Queue:** {$this->event->job->getQueue()}")
            ->line("**Error:** {$errorMessage}")
            ->action('View Failed Jobs', route('admin.failed-jobs.index'));
    }
}
