<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingFlow;
use App\Enums\BookingStatus;
use App\Enums\LedgerType;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\PractitionerProfile;
use App\Models\User;
use App\Notifications\BookingUpdate;
use App\Payments\DTOs\GatewayEvent;
use App\Payments\PaymentService;
use App\Services\Booking\BookingService;
use App\Services\Booking\SlotService;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    private PractitionerProfile $doctor;

    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->doctor = $this->practitioner('pk_free');
        $this->patient = User::factory()->create(['timezone' => 'Asia/Dubai']);
        User::factory()->admin()->create();
    }

    private function practitioner(string $plan): PractitionerProfile
    {
        $p = PractitionerProfile::factory()->live($plan)->create(['fee_minor' => 300000, 'session_minutes' => 30]);
        foreach (range(0, 6) as $d) {
            $p->availabilityRules()->create(['weekday' => $d, 'start_time' => '09:00', 'end_time' => '21:00']);
        }

        return $p->fresh();
    }

    private function slot(PractitionerProfile $p, int $index = 0): CarbonImmutable
    {
        return app(SlotService::class)->slotsUtc($p, 7)[$index];
    }

    private function request(?PractitionerProfile $p = null, ?User $patient = null, int $slot = 0): Booking
    {
        $p ??= $this->doctor;

        return app(BookingService::class)->request($patient ?? $this->patient, $p, $this->slot($p, $slot), 'Itchy rash for 2 weeks', 'Asia/Dubai');
    }

    private function payWithFakeGateway(Booking $booking, string $eventId = 'evt-1'): Payment
    {
        $this->actingAs($this->patient)->post(route('patient.bookings.pay', $booking))->assertRedirect();
        $payment = $booking->payments()->latest('id')->firstOrFail();
        app(PaymentService::class)->handle(new GatewayEvent('fake', $eventId, GatewayEvent::SUCCEEDED, paymentUuid: $payment->uuid));

        return $payment->fresh();
    }

    public function test_patient_requests_slot_through_http_and_practitioner_is_notified(): void
    {
        $slot = $this->slot($this->doctor);

        $this->actingAs($this->patient)
            ->post(route('booking.store', $this->doctor->slug), ['slot' => $slot->toIso8601String(), 'note' => 'Hello', 'consent' => '1'])
            ->assertRedirect();

        $booking = Booking::firstOrFail();
        $this->assertSame(BookingStatus::Requested, $booking->status);
        $this->assertSame(BookingFlow::PlatformPayment, $booking->flow);
        $this->assertSame(2000, $booking->commission_bps);
        $this->assertSame(60000, $booking->commission_minor);       // 20% of Rs 3,000
        $this->assertSame(240000, $booking->practitioner_earning_minor);
        $this->assertSame('Hello', $booking->patient_note);
        $this->assertNotSame('Hello', \DB::table('bookings')->value('patient_note')); // encrypted at rest
        Notification::assertSentTo($this->doctor->user, BookingUpdate::class, fn ($n) => $n->kind === 'requested_practitioner');
    }

    public function test_full_two_step_flow_request_accept_pay_confirm_and_commission(): void
    {
        $booking = $this->request();
        $booking = app(BookingService::class)->accept($booking, $this->doctor->user, 'Google Meet link to follow');

        $this->assertSame(BookingStatus::AwaitingPayment, $booking->status);
        $this->assertNotNull($booking->payment_due_at);
        Notification::assertSentTo($this->patient, BookingUpdate::class, fn ($n) => $n->kind === 'accepted_patient');

        $this->payWithFakeGateway($booking);
        $booking->refresh();

        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(60000, (int) LedgerEntry::where('type', LedgerType::CommissionEarned)->sum('amount_minor'));
        $this->assertSame(240000, (int) LedgerEntry::where('type', LedgerType::PractitionerPayable)->sum('amount_minor'));

        // Client doc: alerts to practitioner, admin and patient.
        Notification::assertSentTo($this->patient, BookingUpdate::class, fn ($n) => $n->kind === 'confirmed_patient');
        Notification::assertSentTo($this->doctor->user, BookingUpdate::class, fn ($n) => $n->kind === 'paid_practitioner');
        Notification::assertSentTo(User::where('role', 'admin')->first(), BookingUpdate::class, fn ($n) => $n->kind === 'paid_admin');
        $this->assertSame(
            [null, 'requested', 'awaiting_payment', 'confirmed'],
            [null, ...$booking->history->pluck('to_status')->map->value->all()],
        );
    }

    public function test_replayed_payment_event_is_ignored(): void
    {
        $booking = app(BookingService::class)->accept($this->request(), $this->doctor->user);
        $payment = $this->payWithFakeGateway($booking, 'evt-dup');

        app(PaymentService::class)->handle(new GatewayEvent('fake', 'evt-dup', GatewayEvent::SUCCEEDED, paymentUuid: $payment->uuid));
        app(PaymentService::class)->handle(new GatewayEvent('fake', 'evt-other', GatewayEvent::SUCCEEDED, paymentUuid: $payment->uuid));

        $this->assertSame(2, LedgerEntry::count());
        $this->assertSame(1, $booking->history()->where('to_status', 'confirmed')->count());
    }

    public function test_amount_mismatch_never_confirms(): void
    {
        $booking = app(BookingService::class)->accept($this->request(), $this->doctor->user);
        $this->actingAs($this->patient)->post(route('patient.bookings.pay', $booking));
        $payment = $booking->payments()->first();

        app(PaymentService::class)->handle(new GatewayEvent('fake', 'evt-x', GatewayEvent::SUCCEEDED, paymentUuid: $payment->uuid, amountMinor: 100, currency: 'PKR'));

        $this->assertSame(BookingStatus::AwaitingPayment, $booking->fresh()->status);
        $this->assertSame(0, LedgerEntry::count());
    }

    public function test_paid_plan_practitioner_is_direct_booking_with_zero_commission(): void
    {
        $doctor = $this->practitioner('pk_pro_1m');
        $booking = app(BookingService::class)->accept($this->request($doctor), $doctor->user);

        $this->assertSame(BookingFlow::Direct, $booking->flow);
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(0, $booking->commission_minor);
    }

    public function test_same_slot_cannot_be_booked_twice(): void
    {
        $first = $this->request();

        $this->expectException(ValidationException::class);
        app(BookingService::class)->request(User::factory()->create(), $this->doctor, $first->starts_at->toImmutable(), null, 'UTC');
    }

    public function test_database_unique_index_blocks_a_racing_double_booking(): void
    {
        $first = $this->request();
        $clone = $first->replicate(['active_slot']);
        $clone->reference = 'GH-RACE01';

        $this->expectException(UniqueConstraintViolationException::class);
        $clone->save();
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $booking = app(BookingService::class)->accept($this->request(), $this->doctor->user);

        $this->expectException(ValidationException::class);
        app(BookingService::class)->reject($booking, $this->doctor->user); // can't decline after accepting
    }

    public function test_unpaid_booking_expires_and_frees_the_slot_then_late_payment_still_confirms(): void
    {
        $booking = app(BookingService::class)->accept($this->request(), $this->doctor->user);
        $this->actingAs($this->patient)->post(route('patient.bookings.pay', $booking));
        $payment = $booking->payments()->first();

        $booking->forceFill(['payment_due_at' => now()->subMinute()])->save();
        $this->assertSame(1, app(BookingService::class)->expireUnpaid());
        $this->assertSame(BookingStatus::PaymentExpired, $booking->fresh()->status);
        $this->assertTrue(app(SlotService::class)->isBookable($this->doctor, $booking->starts_at->toImmutable()));

        // Money arrives late but nobody took the slot: honour it.
        app(PaymentService::class)->handle(new GatewayEvent('fake', 'late', GatewayEvent::SUCCEEDED, paymentUuid: $payment->uuid));
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_late_payment_for_a_taken_slot_is_flagged_for_refund(): void
    {
        $booking = app(BookingService::class)->accept($this->request(), $this->doctor->user);
        $this->actingAs($this->patient)->post(route('patient.bookings.pay', $booking));
        $payment = $booking->payments()->first();
        $booking->forceFill(['payment_due_at' => now()->subMinute()])->save();
        app(BookingService::class)->expireUnpaid();

        app(BookingService::class)->request(User::factory()->create(), $this->doctor, $booking->starts_at->toImmutable(), null, 'UTC');
        app(PaymentService::class)->handle(new GatewayEvent('fake', 'late2', GatewayEvent::SUCCEEDED, paymentUuid: $payment->uuid));

        $booking->refresh();
        $this->assertSame(BookingStatus::PaymentExpired, $booking->status);
        $this->assertTrue($booking->refund_due);
    }

    public function test_unanswered_request_expires(): void
    {
        $booking = $this->request();
        $booking->forceFill(['respond_by' => now()->subMinute()])->save();

        app(BookingService::class)->expireUnanswered();

        $this->assertSame(BookingStatus::ExpiredUnanswered, $booking->fresh()->status);
    }

    public function test_patient_cancellation_refund_rules(): void
    {
        // > 24h before: refund due
        $early = app(BookingService::class)->accept($this->request(slot: 100), $this->doctor->user);
        $this->payWithFakeGateway($early, 'e1');
        $early = app(BookingService::class)->cancel($early->fresh(), $this->patient);
        $this->assertSame(BookingStatus::CancelledByPatient, $early->status);
        $this->assertTrue($early->refund_due);

        // < 24h before: no refund
        $late = app(BookingService::class)->accept($this->request(slot: 0), $this->doctor->user);
        $this->payWithFakeGateway($late, 'e2');
        $late->forceFill(['starts_at' => now()->addHours(5), 'ends_at' => now()->addHours(5)->addMinutes(30)])->save();
        $late = app(BookingService::class)->cancel($late->fresh(), $this->patient);
        $this->assertFalse($late->refund_due);
    }

    public function test_patient_open_request_limit(): void
    {
        foreach (range(0, 2) as $i) {
            $this->request(slot: $i * 5);
        }

        $this->expectException(ValidationException::class);
        $this->request(slot: 40);
    }

    public function test_people_cannot_see_other_peoples_bookings(): void
    {
        $booking = $this->request();
        $stranger = User::factory()->create();
        $otherDoctor = $this->practitioner('pk_free');

        $this->actingAs($stranger)->get(route('patient.bookings.show', $booking))->assertNotFound();
        $this->actingAs($otherDoctor->user)->get(route('practitioner.bookings.show', $booking))->assertNotFound();
        $this->actingAs($this->patient)->get(route('patient.bookings.show', $booking))->assertOk();
        $this->actingAs($this->doctor->user)->get(route('practitioner.bookings.show', $booking))->assertOk()->assertSee('Itchy rash');
    }

    public function test_slots_respect_practitioner_timezone(): void
    {
        $doctor = PractitionerProfile::factory()->live()->create(['timezone' => 'Asia/Karachi', 'session_minutes' => 60]);
        $doctor->availabilityRules()->create(['weekday' => now('Asia/Karachi')->addDays(2)->dayOfWeek, 'start_time' => '10:00', 'end_time' => '11:00']);

        $slots = app(SlotService::class)->slotsUtc($doctor->fresh(), 3);

        $this->assertCount(1, $slots);
        $this->assertSame('05:00', $slots[0]->format('H:i')); // 10:00 PKT = 05:00 UTC
        $forDubai = app(SlotService::class)->forPatient($doctor->fresh(), 'Asia/Dubai', 3);
        $this->assertSame('9:00 AM', array_values($forDubai)[0][0]['label']); // 05:00 UTC = 09:00 GST
    }

    public function test_booking_pages_render(): void
    {
        $booking = $this->request();

        $this->get(route('booking.create', $this->doctor->slug))->assertOk()->assertSee('Choose a time');
        $this->actingAs($this->patient)->get(route('patient.bookings.index'))->assertOk();
        $this->actingAs($this->doctor->user)->get(route('practitioner.bookings.index'))->assertOk();
        $this->actingAs($this->doctor->user)->get(route('practitioner.availability.edit'))->assertOk();
        $this->actingAs($this->doctor->user)->getJson(route('practitioner.bookings.calendar', ['start' => now()->subDay()->toDateString(), 'end' => now()->addWeeks(2)->toDateString()]))
            ->assertOk()->assertJsonCount(1);
    }
}
