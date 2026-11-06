<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells an author their article/question was published or needs changes. */
class ContentDecision extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $kind, public string $title, public bool $approved, public string $url, public ?string $reason = null) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Hello '.$notifiable->firstName().',');

        return $this->approved
            ? $mail->subject("Published: {$this->title}")->line("Your {$this->kind} \"{$this->title}\" is now live.")->action('View it', $this->url)
            : $mail->subject("Changes needed: {$this->title}")->line("Your {$this->kind} \"{$this->title}\" wasn't published yet.")
                ->when($this->reason, fn ($m) => $m->line('Note from our team: '.$this->reason))->action('Open', $this->url);
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => ($this->approved ? 'Published: ' : 'Changes needed: ').$this->title, 'url' => $this->url, 'icon' => $this->approved ? 'check-circle' : 'alert'];
    }
}
