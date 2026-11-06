<?php

namespace App\Notifications;

use App\Models\PractitionerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Client doc step 3: "system unlocks Membership Plans Page via automated email". */
class PractitionerApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PractitionerProfile $profile) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $wasLive = $this->profile->is_public;

        return (new MailMessage)
            ->subject($wasLive ? 'Your profile has been re-verified' : "You're verified. Choose a plan to go live")
            ->greeting('Congratulations, '.$notifiable->firstName().'!')
            ->line('Our team has checked your documents and your profile is verified.')
            ->when(! $wasLive, fn ($m) => $m
                ->line('Your membership plans are now unlocked. Pick the plan that suits your practice and your profile goes public straight away.')
                ->action('Choose your plan', route('practitioner.membership.show')))
            ->when($wasLive, fn ($m) => $m->action('View your profile', route('practitioners.show', $this->profile)));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'You are verified. Choose a plan to go live.',
            'url' => route('practitioner.membership.show'),
            'icon' => 'badge-check',
        ];
    }
}
