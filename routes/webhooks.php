<?php

use App\Http\Controllers\Webhooks\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

// Prefix /webhooks, 'api' middleware (no session/CSRF), throttled. Signatures verified per gateway.
Route::post('/payments/{gateway}', PaymentWebhookController::class)->name('payments');
