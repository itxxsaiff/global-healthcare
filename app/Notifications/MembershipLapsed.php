<?php

namespace App\Notifications;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipLapsed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Membership $membership) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $public = $this->membership->practitioner->fresh()->is_public;

        return (new MailMessage)
            ->subject($public ? 'Your plan has changed' : 'Your profile is hidden: renew to go live again')
            ->greeting('Hello '.$notifiable->firstName().',')
            ->line("Your {$this->membership->plan->name} plan is no longer active"
                .($this->membership->status->value === 'past_due' ? ' because the last payment didn\'t go through.' : '.'))
            ->line($public
                ? 'You\'ve been moved to the free Starter plan, so your profile is still listed (20% commission on paid bookings).'
                : 'Your profile has been hidden from the directory until a plan is active again.')
            ->action('Choose a plan', route('practitioner.membership.show'));
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Your '.$this->membership->plan->name.' plan has ended', 'url' => route('practitioner.membership.show'), 'icon' => 'alert'];
    }
}
