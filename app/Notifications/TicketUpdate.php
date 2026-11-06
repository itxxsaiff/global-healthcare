<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket, public string $kind) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->ticket;

        return match ($this->kind) {
            'staff_replied' => (new MailMessage)->subject("Re: {$t->subject} [{$t->reference}]")
                ->greeting('Hello '.$notifiable->firstName().',')
                ->line('Our support team has replied to your request.')
                ->action('Read the reply', route('tickets.show', $t)),
            default => (new MailMessage)->subject(($this->kind === 'opened' ? 'New ' : 'Reply on ').$t->category->label()." ticket [{$t->reference}]")
                ->line($t->user->name.': '.$t->subject)
                ->action('Open ticket', route('admin.tickets.show', $t)),
        };
    }

    public function toArray(object $notifiable): array
    {
        $staff = $this->kind === 'staff_replied';

        return [
            'title' => ($staff ? 'Support replied: ' : $this->ticket->category->label().': ').$this->ticket->subject,
            'url' => $staff ? route('tickets.show', $this->ticket) : route('admin.tickets.show', $this->ticket),
            'icon' => 'life-buoy',
        ];
    }
}
