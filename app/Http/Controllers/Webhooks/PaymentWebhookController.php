<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Payments\PaymentManager;
use App\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Server-to-server notifications. Signature checked by each gateway's parseCallback(). */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentManager $gateways, PaymentService $payments): JsonResponse
    {
        abort_unless(in_array($gateway, ['stripe', 'jazzcash'], true), 404);

        $payments->handle($gateways->driver($gateway)->parseCallback($request));

        return response()->json(['received' => true]);
    }
}
