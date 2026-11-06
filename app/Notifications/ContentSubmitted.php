<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Admin alert: something is waiting in a moderation queue. */
class ContentSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $kind, public string $title, public string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New '.$this->kind.' to review: '.$this->title)
            ->line('A new '.$this->kind.' is waiting in the moderation queue.')
            ->action('Review', $this->url);
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'New '.$this->kind.': '.$this->title, 'url' => $this->url, 'icon' => 'file-text'];
    }
}
