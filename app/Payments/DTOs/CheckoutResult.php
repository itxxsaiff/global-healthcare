<?php

namespace App\Payments\DTOs;

final readonly class CheckoutResult
{
    /** @param array<string, string> $fields */
    private function __construct(
        public ?string $redirectUrl = null,
        public ?string $formAction = null,
        public array $fields = [],
        public ?string $reference = null,
    ) {}

    public static function redirect(string $url, ?string $reference = null): self
    {
        return new self(redirectUrl: $url, reference: $reference);
    }

    /** Some gateways (e.g. JazzCash) need a signed POST form submitted from the browser. */
    public static function form(string $action, array $fields, ?string $reference = null): self
    {
        return new self(formAction: $action, fields: $fields, reference: $reference);
    }
}
