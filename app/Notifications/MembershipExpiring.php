<?php

namespace App\Notifications;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Membership $membership, public int $days) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your {$this->membership->plan->name} plan ends in {$this->days} ".str('day')->plural($this->days))
            ->greeting('Hello '.$notifiable->firstName().',')
            ->line('Your plan ends on '.$this->membership->ends_at->format('j F Y').'. Renew now to keep 0% commission and direct patient dealing.')
            ->line('If you don\'t renew, your profile moves to the free Starter plan (20% commission on paid bookings).')
            ->action('Renew my plan', route('practitioner.membership.show'));
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => "Plan ends in {$this->days} ".str('day')->plural($this->days), 'url' => route('practitioner.membership.show'), 'icon' => 'clock'];
    }
}
