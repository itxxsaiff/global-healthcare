<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Requested = 'requested';
    case AwaitingPayment = 'awaiting_payment';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case ExpiredUnanswered = 'expired_unanswered';
    case PaymentExpired = 'payment_expired';
    case CancelledByPatient = 'cancelled_by_patient';
    case CancelledByPractitioner = 'cancelled_by_practitioner';

    /** Statuses that hold the time slot (mirrors the active_slot generated column). */
    public const HOLDING = ['requested', 'awaiting_payment', 'confirmed'];

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Requested => [self::AwaitingPayment, self::Confirmed, self::Rejected, self::ExpiredUnanswered, self::CancelledByPatient],
            self::AwaitingPayment => [self::Confirmed, self::PaymentExpired, self::CancelledByPatient, self::CancelledByPractitioner],
            self::Confirmed => [self::Completed, self::CancelledByPatient, self::CancelledByPractitioner],
            // Late payment arriving after expiry may still confirm if the slot is free.
            self::PaymentExpired => [self::Confirmed],
            default => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function holdsSlot(): bool
    {
        return in_array($this->value, self::HOLDING, true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::AwaitingPayment => 'Awaiting payment',
            self::Confirmed => 'Confirmed',
            self::Completed => 'Completed',
            self::Rejected => 'Declined',
            self::ExpiredUnanswered => 'Expired (no response)',
            self::PaymentExpired => 'Expired (unpaid)',
            self::CancelledByPatient => 'Cancelled by patient',
            self::CancelledByPractitioner => 'Cancelled by practitioner',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Requested, self::AwaitingPayment => 'warning',
            self::Confirmed => 'info',
            self::Completed => 'success',
            default => 'neutral',
        };
    }
}
