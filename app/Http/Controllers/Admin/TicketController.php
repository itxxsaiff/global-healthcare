<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TicketController as UserTickets;
use App\Models\Ticket;
use App\Notifications\TicketUpdate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = TicketStatus::tryFrom((string) $request->query('status'));

        return view('admin.tickets.index', [
            'status' => $status,
            'tickets' => Ticket::query()->with(['user:id,name,role', 'booking:id,reference'])
                ->when($status, fn ($q) => $q->where('status', $status), fn ($q) => $q->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress]))
                ->when($request->query('category'), fn ($q, $c) => $q->where('category', $c))
                ->orderByRaw("priority = 'high' desc")->latest('last_reply_at')->paginate(25)->withQueryString(),
        ]);
    }

    public function show(Ticket $ticket): View
    {
        return view('admin.tickets.show', ['ticket' => $ticket->load(['messages.user:id,name,role', 'user', 'booking.practitioner.user', 'booking.patient'])]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'internal' => ['nullable', 'boolean'],
            'status' => ['required', Rule::enum(TicketStatus::class)],
        ]);
        $internal = (bool) ($data['internal'] ?? false);

        UserTickets::addMessage($ticket, $request->user(), $data['body'], $request, $internal);
        $ticket->forceFill(['status' => $data['status'], 'assigned_to' => $ticket->assigned_to ?? $request->user()->id, 'last_reply_at' => now()])->save();

        if (! $internal) {
            $ticket->user->notify(new TicketUpdate($ticket, 'staff_replied'));
        }

        return back()->with('success', $internal ? 'Internal note added.' : 'Reply sent to '.$ticket->user->firstName().'.');
    }
}
