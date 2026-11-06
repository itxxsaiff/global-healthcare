<?php

namespace App\Notifications;

use App\Models\PractitionerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PractitionerRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PractitionerProfile $profile) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A few changes are needed on your profile')
            ->greeting('Hello '.$notifiable->firstName().',')
            ->line("Thanks for applying. We couldn't verify your profile yet, for this reason:")
            ->line('"'.$this->profile->rejection_reason.'"')
            ->line('Please update your profile and submit it again. We review resubmissions quickly.')
            ->action('Update my profile', route('practitioner.onboarding.show'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Changes requested on your profile',
            'url' => route('practitioner.onboarding.show'),
            'icon' => 'alert',
        ];
    }
}
