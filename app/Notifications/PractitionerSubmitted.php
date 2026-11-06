<?php

namespace App\Notifications;

use App\Models\PractitionerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PractitionerSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PractitionerProfile $profile, public bool $reverification = false) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->profile->user->name;

        return (new MailMessage)
            ->subject(($this->reverification ? 'Re-verification needed: ' : 'New practitioner to verify: ').$name)
            ->greeting('Hello,')
            ->line($this->reverification
                ? "{$name} changed verified details on their profile. It has been hidden until you review it again."
                : "{$name} has completed their profile and uploaded their documents.")
            ->action('Review application', route('admin.verifications.show', $this->profile));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => ($this->reverification ? 'Re-verification: ' : 'New application: ').$this->profile->user->name,
            'url' => route('admin.verifications.show', $this->profile),
            'icon' => 'shield-check',
        ];
    }
}
