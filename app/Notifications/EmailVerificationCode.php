<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/** The 6-digit sign-up code. Deliberately not queued: the person is waiting for it. */
class EmailVerificationCode extends Notification
{
    public function __construct(public string $code, public int $minutes) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = config('app.name');
        $digits = e(chunk_split($this->code, 3, ' '));

        return (new MailMessage)
            ->subject("{$this->code} is your {$app} verification code")
            ->greeting('Hi '.$notifiable->firstName().',')
            ->line("Here's your code to confirm your email address:")
            ->line(new HtmlString('<p style="font-size:32px;font-weight:700;letter-spacing:6px;text-align:center;color:#0E8474;margin:8px 0 16px;font-family:Menlo,Consolas,monospace">'.trim($digits).'</p>'))
            ->line("Type it on the verification screen. It expires in {$this->minutes} minutes.")
            ->line("If you didn't create an account with {$app}, you can ignore this email. Nobody can use your address without this code.")
            ->salutation(new HtmlString("Thanks,<br>The {$app} team"));
    }
}
