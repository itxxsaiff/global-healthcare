<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketUpdate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** "Report Issue / Complaint Box" for patients and practitioners (client doc §7). */
class TicketController extends Controller
{
    public function index(Request $request): View
    {
        return view('tickets.index', [
            'tickets' => $request->user()->tickets()->latest('updated_at')->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        return view('tickets.create', [
            'bookings' => $this->bookingsFor($request->user())->latest('starts_at')->limit(20)->get(),
            'selected' => $request->query('booking'),
            'categories' => TicketCategory::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'booking' => ['nullable', 'string', 'max:20'],
            'subject' => ['required', 'string', 'min:5', 'max:180'],
            'body' => ['required', 'string', 'min:20', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:3072'],
        ]);

        $booking = filled($data['booking'] ?? null) ? $this->bookingsFor($user)->where('reference', $data['booking'])->first() : null;

        $ticket = DB::transaction(function () use ($user, $data, $booking, $request) {
            $ticket = new Ticket(['category' => $data['category'], 'subject' => $data['subject'], 'booking_id' => $booking?->id,
                'priority' => in_array($data['category'], ['payment_dispute', 'unprofessional_behavior'], true) ? 'high' : 'normal']);
            $ticket->forceFill(['reference' => 'T-'.Str::upper(Str::random(7)), 'user_id' => $user->id, 'status' => TicketStatus::Open, 'last_reply_at' => now()])->save();
            $this->addMessage($ticket, $user, $data['body'], $request);

            return $ticket;
        });

        Notification::send(User::query()->where('role', Role::Admin)->get(), new TicketUpdate($ticket, 'opened'));

        return redirect()->route('tickets.show', $ticket)->with('success', 'Thanks for letting us know. Our team usually replies within one working day.');
    }

    public function show(Request $request, Ticket $ticket): View
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        return view('tickets.show', ['ticket' => $ticket->load(['messages' => fn ($q) => $q->where('is_internal', false)->with('user:id,name,role'), 'booking'])]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000'], 'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:3072']]);

        $this->addMessage($ticket, $request->user(), $data['body'], $request);
        $ticket->forceFill(['status' => TicketStatus::Open, 'last_reply_at' => now()])->save();
        Notification::send(User::query()->where('role', Role::Admin)->get(), new TicketUpdate($ticket, 'user_replied'));

        return back()->with('success', 'Reply sent.');
    }

    public function attachment(Request $request, \App\Models\TicketMessage $message)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($message->ticket->user_id === $user->id && ! $message->is_internal), 403);
        abort_unless($message->attachment_path, 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download($message->attachment_path, $message->attachment_name);
    }

    public static function addMessage(Ticket $ticket, User $user, string $body, Request $request, bool $internal = false): void
    {
        $file = $request->file('attachment');
        $ticket->messages()->create([
            'user_id' => $user->id,
            'body' => $body,
            'is_internal' => $internal,
            'attachment_path' => $file?->store("tickets/{$ticket->id}", 'local'),
            'attachment_name' => $file ? Str::limit($file->getClientOriginalName(), 180, '') : null,
        ]);
    }

    private function bookingsFor(User $user)
    {
        return $user->isPractitioner()
            ? Booking::query()->where('practitioner_profile_id', $user->practitionerProfile?->id)
            : Booking::query()->where('patient_id', $user->id);
    }
}
