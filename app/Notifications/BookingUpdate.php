<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Every booking email + dashboard alert. Times are always shown in the
 * recipient's own time zone.
 */
class BookingUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking, public string $kind) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $b = $this->booking->loadMissing(['patient', 'practitioner.user']);
        $doctor = $b->practitioner->displayName();
        $patient = $b->patient->name;
        $when = $this->when($notifiable);
        $mail = (new MailMessage)->greeting('Hello '.$notifiable->firstName().',');

        return match ($this->kind) {
            'requested_practitioner' => $mail
                ->subject("New booking request from {$patient}")
                ->line("{$patient} would like a consultation on **{$when}**.")
                ->line('Please accept or decline by '.$b->respond_by?->setTimezone($notifiable->timezone)->format('D j M, g:i A').'. Unanswered requests expire automatically.')
                ->action('Review request', route('practitioner.bookings.show', $b)),

            'accepted_patient' => $mail
                ->subject("{$doctor} accepted your request. Complete payment to confirm")
                ->line("Good news: {$doctor} can see you on **{$when}**.")
                ->line('To confirm your booking, please pay **'.$b->feeLabel().'** by '.$b->payment_due_at?->setTimezone($notifiable->timezone)->format('D j M, g:i A').'.')
                ->action('Pay securely', route('patient.bookings.show', $b))
                ->line('If payment isn\'t completed in time, the slot is released and nothing is charged.'),

            'confirmed_patient' => $mail
                ->subject("Confirmed: your consultation with {$doctor}")
                ->line("Your consultation is confirmed for **{$when}**.")
                ->line('Booking reference: '.$b->reference)
                ->when($b->meeting_details, fn ($m) => $m->line('**How to join:** '.$b->meeting_details))
                ->when(! $b->meeting_details, fn ($m) => $m->line("{$doctor} will share joining details in your dashboard before the session."))
                ->line('Tips: find a quiet, private spot, test your connection, and keep any reports or medicines handy.')
                ->action('View booking', route('patient.bookings.show', $b)),

            'declined_patient' => $mail
                ->subject("{$doctor} can't take this booking")
                ->line("{$doctor} isn't able to see you on {$when}.".($b->cancellation_reason ? ' Note: '.$b->cancellation_reason : ''))
                ->line('Nothing has been charged. You can choose another time or another practitioner.')
                ->action('Find another time', route('practitioners.show', $b->practitioner).'#book'),

            // Client document wording: "Payment received for Patient [Name] scheduled at [Time]"
            'paid_practitioner' => $mail
                ->subject("Payment received for {$patient}")
                ->line("Payment received for Patient {$patient} scheduled at {$when}.")
                ->line('Your share: '.Money::format($b->practitioner_earning_minor, $b->currency).' (after '.($b->commission_bps / 100).'% platform commission).')
                ->action('Open booking', route('practitioner.bookings.show', $b)),

            'paid_admin' => $mail
                ->subject("Booking paid · {$b->reference}")
                ->line("Patient: {$patient}")
                ->line("Practitioner: {$doctor}")
                ->line('Session: '.$b->starts_at->format('D j M Y, H:i').' UTC')
                ->line('Fee: '.$b->feeLabel().' · Commission: '.Money::format($b->commission_minor, $b->currency).' ('.($b->commission_bps / 100).'%)')
                ->action('Open wallet', url('/admin')),

            'cancelled_other' => $mail
                ->subject("Booking cancelled · {$when}")
                ->line("The consultation on {$when} ({$b->reference}) has been cancelled.".($b->cancellation_reason ? ' Reason: '.$b->cancellation_reason : ''))
                ->when($b->refund_due && $notifiable->id === $b->patient_id, fn ($m) => $m->line('You\'re due a full refund. Our team will process it to your original payment method.')),

            'expired_unanswered_patient' => $mail
                ->subject('Your booking request expired')
                ->line("{$doctor} didn't respond in time to your request for {$when}, so it has expired. Nothing was charged.")
                ->action('Choose another practitioner', route('directory')),

            'payment_expired_patient' => $mail
                ->subject('Your booking was released')
                ->line("Payment for your consultation on {$when} wasn't completed in time, so the slot has been released. Nothing was charged.")
                ->action('Book again', route('practitioners.show', $b->practitioner).'#book'),

            'refund_needed_admin' => $mail
                ->subject("Refund needed · {$b->reference}")
                ->line("Booking {$b->reference} ({$patient} with {$doctor}) needs a refund of ".$b->feeLabel().'.')
                ->action('Open bookings', url('/admin')),

            default => $mail->subject('Booking update')->line('There is an update on booking '.$b->reference.'.'),
        };
    }

    public function toArray(object $notifiable): array
    {
        $b = $this->booking;
        $titles = [
            'requested_practitioner' => 'New request from '.$b->patient?->name,
            'accepted_patient' => 'Request accepted. Payment needed to confirm.',
            'confirmed_patient' => 'Booking confirmed · '.$b->reference,
            'declined_patient' => 'Booking request declined',
            'paid_practitioner' => 'Payment received for '.$b->patient?->name,
            'paid_admin' => 'Booking paid · '.$b->reference,
            'cancelled_other' => 'Booking cancelled · '.$b->reference,
            'expired_unanswered_patient' => 'Request expired (no response)',
            'payment_expired_patient' => 'Booking released (unpaid)',
            'refund_needed_admin' => 'Refund needed · '.$b->reference,
        ];
        $url = match (true) {
            $notifiable->isPatient() => route('patient.bookings.show', $b),
            $notifiable->isPractitioner() => route('practitioner.bookings.show', $b),
            default => url('/admin'),
        };

        return ['title' => $titles[$this->kind] ?? 'Booking update', 'url' => $url, 'icon' => 'calendar'];
    }

    private function when(object $notifiable): string
    {
        return $this->booking->starts_at->copy()->setTimezone($notifiable->timezone ?: 'UTC')->format('l j F, g:i A').' ('.str_replace('_', ' ', $notifiable->timezone ?: 'UTC').')';
    }
}
