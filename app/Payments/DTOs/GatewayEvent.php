<?php

namespace App\Payments\DTOs;

/** A provider callback, normalised. */
final readonly class GatewayEvent
{
    public const SUCCEEDED = 'succeeded';
    public const FAILED = 'failed';
    public const PENDING = 'pending';
    // Subscription lifecycle (recurring gateways only)
    public const RENEWED = 'renewed';
    public const RENEWAL_FAILED = 'renewal_failed';
    public const CANCELLED = 'cancelled';
    public const IGNORED = 'ignored';

    public function __construct(
        public string $gateway,
        public string $eventId,
        public string $outcome,
        public ?string $paymentUuid = null,
        public ?string $gatewayReference = null,
        public ?int $amountMinor = null,
        public ?string $currency = null,
        public ?string $subscriptionId = null,
        public ?string $customerId = null,
        public ?int $periodEnd = null,
        public string $type = '',
        public array $payload = [],
    ) {}
}
