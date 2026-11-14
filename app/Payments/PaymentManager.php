<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\FakeGateway;
use App\Payments\Gateways\JazzCashGateway;
use App\Payments\Gateways\StripeGateway;
use Illuminate\Support\Manager;

/**
 * Resolves gateway drivers by key: app(PaymentManager::class)->driver('jazzcash').
 * To add a provider (PayFast, EasyPaisa, Safepay…): implement PaymentGateway,
 * add a create{Name}Driver() method, add its config to config/services.php.
 *
 * @method PaymentGateway driver(?string $driver = null)
 */
class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('platform.gateways.pk', 'fake');
    }

    public function forMarket(string $market): PaymentGateway
    {
        return $this->driver(config("platform.gateways.{$market}", 'fake'));
    }

    /** False while the market still points at the test gateway on the live site (gateway not connected yet). */
    public function isAvailable(string $key): bool
    {
        return ! ($key === 'fake' && $this->container->isProduction());
    }

    protected function createFakeDriver(): PaymentGateway
    {
        abort_if($this->container->isProduction(), 500, 'The fake payment gateway is disabled in production.');

        return new FakeGateway;
    }

    protected function createStripeDriver(): PaymentGateway
    {
        return new StripeGateway(config('services.stripe'));
    }

    protected function createJazzcashDriver(): PaymentGateway
    {
        return new JazzCashGateway(config('services.jazzcash'));
    }
}
