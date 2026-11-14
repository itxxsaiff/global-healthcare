<?php

namespace Tests\Feature\Admin;

use App\Enums\LedgerType;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Plan;
use App\Models\PractitionerProfile;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketUpdate;
use App\Payments\DTOs\GatewayEvent;
use App\Payments\PaymentService;
use App\Services\Booking\BookingService;
use App\Services\Booking\SlotService;
use App\Services\Commission\CommissionService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackOfficeTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
    }

    private function paidBooking(): Booking
    {
        $doctor = PractitionerProfile::factory()->live()->create(['fee_minor' => 500000]);
        foreach (range(0, 6) as $d) {
            $doctor->availabilityRules()->create(['weekday' => $d, 'start_time' => '08:00', 'end_time' => '22:00']);
        }
        $patient = User::factory()->create();
        $service = app(BookingService::class);
        $booking = $service->accept($service->request($patient, $doctor->fresh(), app(SlotService::class)->slotsUtc($doctor->fresh(), 7)[60], null, 'UTC'), $doctor->user);
        $this->actingAs($patient)->post(route('patient.bookings.pay', $booking));
        app(PaymentService::class)->handle(new GatewayEvent('fake', 'pb'.$booking->id, GatewayEvent::SUCCEEDED, paymentUuid: $booking->payments()->first()->uuid));

        return $booking->fresh();
    }

    public function test_every_admin_screen_renders(): void
    {
        $booking = $this->paidBooking();
        foreach (['admin.dashboard', 'admin.bookings.index', 'admin.wallet.index', 'admin.payments.index', 'admin.plans.index', 'admin.users.index',
            'admin.taxonomy.index', 'admin.settings.edit', 'admin.pages.index', 'admin.audit.index', 'admin.tickets.index'] as $route) {
            $this->actingAs($this->admin)->get(route($route))->assertOk();
        }
        $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking))->assertOk();
        $this->actingAs($booking->practitioner->user)->get(route('practitioner.earnings.index'))->assertOk()->assertSee('Rs 4,000');
    }

    public function test_complaint_ticket_round_trip(): void
    {
        $booking = $this->paidBooking();
        $patient = $booking->patient;

        $this->actingAs($patient)->post(route('tickets.store'), [
            'category' => 'no_show', 'booking' => $booking->reference, 'subject' => 'Doctor did not join',
            'body' => 'I waited 20 minutes on the call and nobody joined.',
        ])->assertRedirect();

        $ticket = Ticket::firstOrFail();
        $this->assertSame($booking->id, $ticket->booking_id);
        Notification::assertSentTo($this->admin, TicketUpdate::class);

        $this->actingAs($this->admin)->post(route('admin.tickets.reply', $ticket), ['body' => 'Internal: check logs', 'internal' => '1', 'status' => 'in_progress']);
        $this->actingAs($this->admin)->post(route('admin.tickets.reply', $ticket), ['body' => 'Sorry about this, we are refunding you.', 'status' => 'resolved']);

        $this->actingAs($patient)->get(route('tickets.show', $ticket))
            ->assertSee('we are refunding you')->assertDontSee('Internal: check logs');
        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);
        Notification::assertSentToTimes($patient, TicketUpdate::class, 1);

        $this->actingAs(User::factory()->create())->get(route('tickets.show', $ticket))->assertNotFound();
    }

    public function test_recording_a_refund_reverses_the_ledger(): void
    {
        $booking = $this->paidBooking();
        $booking = app(BookingService::class)->cancel($booking, $booking->practitioner->user, 'Emergency at clinic');
        $this->assertTrue($booking->refund_due);

        $this->actingAs($this->admin)->post(route('admin.bookings.refunded', $booking), ['note' => 'JC-REF-1'])->assertRedirect();

        $this->assertFalse($booking->fresh()->refund_due);
        $this->assertSame('refunded', $booking->payments()->first()->status->value);
        // Both sides given back exactly: Rs 1,000 commission and Rs 4,000 practitioner share.
        $this->assertSame(-100000, (int) LedgerEntry::where('type', LedgerType::CommissionReversal)->sum('amount_minor'));
        $this->assertSame(-400000, (int) LedgerEntry::where('type', LedgerType::PayableReversal)->sum('amount_minor'));
        $this->assertSame(0, (int) app(CommissionService::class)->balanceFor($booking->practitioner)['PKR']);

        // Recording the refund twice never double-reverses.
        app(CommissionService::class)->reverseForRefund($booking);
        $this->assertSame(2, LedgerEntry::where('booking_id', $booking->id)->whereIn('type', [LedgerType::CommissionReversal, LedgerType::PayableReversal])->count());
    }

    public function test_payouts_cannot_exceed_balance(): void
    {
        $booking = $this->paidBooking(); // practitioner owed Rs 4,000
        $payload = ['practitioner_profile_id' => $booking->practitioner_profile_id, 'currency' => 'PKR', 'method' => 'Bank transfer', 'paid_at' => now()->toDateString()];

        $this->actingAs($this->admin)->post(route('admin.wallet.payouts.store'), $payload + ['amount' => 5000])->assertStatus(422);
        $this->actingAs($this->admin)->post(route('admin.wallet.payouts.store'), $payload + ['amount' => 4000])->assertRedirect();

        $this->assertSame(0, (int) app(CommissionService::class)->balanceFor($booking->practitioner)['PKR']);
    }

    public function test_suspending_a_practitioner_hides_them(): void
    {
        $doctor = PractitionerProfile::factory()->live()->create();
        $this->actingAs($this->admin)->post(route('admin.users.toggle', $doctor->user))->assertRedirect();

        $this->assertFalse($doctor->fresh()->is_public);
    }

    public function test_plan_price_change_does_not_touch_existing_bookings(): void
    {
        $booking = $this->paidBooking();
        $plan = Plan::where('code', 'pk_free')->first();

        $this->actingAs($this->admin)->put(route('admin.plans.update', $plan), ['name' => 'Starter', 'price' => 0, 'commission_percent' => 15, 'is_active' => 1]);

        $this->assertSame(1500, $plan->fresh()->commission_bps);
        $this->assertSame(2000, $booking->fresh()->commission_bps);
    }

    public function test_notification_links_cannot_redirect_off_site(): void
    {
        $user = User::factory()->create();
        $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'x', 'data' => ['title' => 't', 'url' => 'https://evil.example/phish']]);

        $this->actingAs($user)->get(route('notifications.open', $user->notifications()->first()->id))->assertRedirect(route('dashboard'));
    }

    public function test_settings_override_config(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'platform_whatsapp' => '+923001112222', 'support_email' => 'help@example.com', 'request_response_hours' => 12, 'payment_window_hours' => 6,
            'min_notice_hours' => 2, 'booking_horizon_days' => 21, 'max_open_requests_per_patient' => 2, 'free_cancellation_hours' => 12,
            'membership_grace_days' => 5, 'document_max_kb' => 5120,
        ])->assertRedirect();

        $this->assertSame(12, Setting::get('request_response_hours'));
        $this->assertSame('+923001112222', Setting::get('platform_whatsapp'));
    }
}
