<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.payments.index', [
            'status' => PaymentStatus::tryFrom((string) $request->query('status')),
            'payments' => Payment::query()->with(['user:id,name', 'payable'])
                ->when(PaymentStatus::tryFrom((string) $request->query('status')), fn ($q, $s) => $q->where('status', $s))
                ->latest()->paginate(30)->withQueryString(),
            'failedEvents' => PaymentEvent::query()->whereNotNull('error')->latest()->limit(10)->get(),
        ]);
    }
}
