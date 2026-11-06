<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\DTOs\GatewayEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * JazzCash hosted checkout ("Page Redirection", API v1.1), PKR one-off payments.
 * Signing: HMAC-SHA256 over the integrity salt + all non-empty pp_* fields
 * sorted by key, joined with "&".
 *
 * MUST be tested end-to-end in the JazzCash sandbox with the client's merchant
 * credentials before going live; field names follow JazzCash's merchant docs.
 */
class JazzCashGateway implements PaymentGateway
{
    public function __construct(private readonly array $config) {}

    public function key(): string
    {
        return 'jazzcash';
    }

    public function label(): string
    {
        return 'JazzCash (wallet or card)';
    }

    public function supports(Payment $payment): bool
    {
        return $payment->currency === 'PKR' && $this->configured();
    }

    public function createCheckout(Payment $payment): CheckoutResult
    {
        $this->ensureConfigured();
        $now = now('Asia/Karachi');
        $txnRef = 'T'.$now->format('YmdHis').substr(str_replace('-', '', $payment->uuid), 0, 6);

        $fields = [
            'pp_Version' => '1.1',
            'pp_TxnType' => '',
            'pp_Language' => 'EN',
            'pp_MerchantID' => $this->config['merchant_id'],
            'pp_SubMerchantID' => '',
            'pp_Password' => $this->config['password'],
            'pp_BankID' => '',
            'pp_ProductID' => '',
            'pp_TxnRefNo' => $txnRef,
            'pp_Amount' => (string) $payment->amount_minor, // paisa
            'pp_TxnCurrency' => 'PKR',
            'pp_TxnDateTime' => $now->format('YmdHis'),
            'pp_BillReference' => substr($payment->uuid, 0, 20),
            'pp_Description' => 'Consultation payment',
            'pp_TxnExpiryDateTime' => ($payment->expires_at ?? $now->copy()->addDay())->setTimezone('Asia/Karachi')->format('YmdHis'),
            'pp_ReturnURL' => route('payments.return', ['payment' => $payment, 'gateway' => 'jazzcash']),
            'ppmpf_1' => $payment->uuid,
            'ppmpf_2' => '', 'ppmpf_3' => '', 'ppmpf_4' => '', 'ppmpf_5' => '',
        ];
        $fields['pp_SecureHash'] = $this->hash($fields);

        return CheckoutResult::form($this->config['checkout_url'], $fields, $txnRef);
    }

    public function parseCallback(Request $request): GatewayEvent
    {
        $data = collect($request->all())->filter(fn ($v, $k) => str_starts_with($k, 'pp') || str_starts_with($k, 'ppmpf'))->all();
        $given = (string) ($data['pp_SecureHash'] ?? '');
        unset($data['pp_SecureHash']);

        abort_unless($given !== '' && hash_equals(strtoupper($this->hash($data)), strtoupper($given)), 403, 'Invalid JazzCash signature.');

        $code = $data['pp_ResponseCode'] ?? '';

        return new GatewayEvent(
            gateway: 'jazzcash',
            eventId: 'jc-'.($data['pp_TxnRefNo'] ?? '').'-'.$code,
            outcome: match ($code) {
                '000', '121' => GatewayEvent::SUCCEEDED,
                '124', '157' => GatewayEvent::PENDING, // voucher / awaiting confirmation
                default => GatewayEvent::FAILED,
            },
            paymentUuid: $data['ppmpf_1'] ?? null,
            gatewayReference: $data['pp_TxnRefNo'] ?? null,
            amountMinor: isset($data['pp_Amount']) ? (int) $data['pp_Amount'] : null,
            currency: $data['pp_TxnCurrency'] ?? 'PKR',
            type: 'jazzcash.'.$code,
            payload: collect($data)->except(['pp_Password'])->all(),
        );
    }

    /** Server-to-server status inquiry, so a forged or replayed redirect can't mark a payment paid. */
    public function verify(Payment $payment, GatewayEvent $event): bool
    {
        $fields = [
            'pp_TxnRefNo' => $event->gatewayReference,
            'pp_MerchantID' => $this->config['merchant_id'],
            'pp_Password' => $this->config['password'],
        ];
        $fields['pp_SecureHash'] = $this->hash($fields);

        $response = Http::timeout(15)->acceptJson()->post($this->config['inquiry_url'], $fields);

        return $response->ok()
            && in_array($response->json('pp_ResponseCode'), ['000', '121'], true)
            && (string) $response->json('pp_Amount', $payment->amount_minor) === (string) $payment->amount_minor;
    }

    private function hash(array $fields): string
    {
        ksort($fields);
        $values = array_filter($fields, fn ($v) => $v !== '' && $v !== null);
        $message = $this->config['integrity_salt'].'&'.implode('&', $values);

        return strtoupper(hash_hmac('sha256', $message, $this->config['integrity_salt']));
    }

    private function configured(): bool
    {
        return filled($this->config['merchant_id'] ?? null) && filled($this->config['integrity_salt'] ?? null);
    }

    private function ensureConfigured(): void
    {
        if (! $this->configured()) {
            throw new RuntimeException('JazzCash is not configured. Set JAZZCASH_* in .env.');
        }
    }
}
