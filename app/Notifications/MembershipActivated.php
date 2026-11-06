<?php

namespace App\Notifications;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Membership $membership) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->membership->plan;

        return (new MailMessage)
            ->subject('Your profile is live on '.config('app.name'))
            ->greeting('You\'re live, '.$notifiable->firstName().'!')
            ->line("Your {$plan->name} plan is active and patients can now find and book you.")
            ->when($this->membership->ends_at, fn ($m) => $m->line('Your plan runs until '.$this->membership->ends_at->format('j F Y').'.'))
            ->line('Next step: set your weekly availability so patients can request times.')
            ->action('Set availability', route('practitioner.dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->membership->plan->name.' plan active. Your profile is live.', 'url' => route('practitioner.membership.show'), 'icon' => 'check-circle'];
    }
}
