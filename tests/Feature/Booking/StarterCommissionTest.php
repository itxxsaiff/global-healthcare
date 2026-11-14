<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingFlow;
use App\Enums\LedgerType;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Plan;
use App\Models\PractitionerProfile;
use App\Models\User;
use App\Payments\DTOs\GatewayEvent;
use App\Payments\PaymentManager;
use App\Payments\PaymentService;
use App\Services\Booking\BookingService;
use App\Services\Booking\SlotService;
use App\Services\Commission\CommissionService;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pakistan Starter (free) plan: no membership fee; the platform records 20% of every
 * successfully paid consultation and the practitioner is owed the other 80%.
 */
class StarterCommissionTest extends TestCase
{
    private User $patient;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->patient = User::factory()->create(['timezone' => 'Asia/Karachi']);
        $this->admin = User::factory()->admin()->create();
    }

    private function doctor(string $plan = 'pk_free', int $feeMinor = 300000): PractitionerProfile
    {
        $p = PractitionerProfile::factory()->live($plan)->create(['fee_minor' => $feeMinor, 'session_minutes' => 30]);
        foreach (range(0, 6) as $d) {
            $p->availabilityRules()->create(['weekday' => $d, 'start_time' => '09:00', 'end_time' => '21:00']);
        }

        return $p->fresh();
    }

    private function book(PractitionerProfile $p, int $slot = 0): Booking
    {
        $start = app(SlotService::class)->slotsUtc($p, 7)[$slot];

        return app(BookingService::class)->request($this->patient, $p, $start, null, 'Asia/Karachi');
    }

    private function acceptAndPay(Booking $booking, string $event): Booking
    {
        $booking = app(BookingService::class)->accept($booking, $booking->practitioner->user);
        $this->actingAs($this->patient)->post(route('patient.bookings.pay', $booking))->assertRedirect();
        $payment = $booking->payments()->latest('id')->firstOrFail();
        app(PaymentService::class)->handle(new GatewayEvent('fake', $event, GatewayEvent::SUCCEEDED, paymentUuid: $payment->uuid));

        return $booking->fresh();
    }

    private function ledger(Booking $b, LedgerType $type): ?int
    {
        $v = LedgerEntry::where('booking_id', $b->id)->where('type', $type)->value('amount_minor');

        return $v === null ? null : (int) $v;
    }

    public function test_starter_plan_is_free_and_charges_20_percent_commission(): void
    {
        $starter = Plan::where('code', 'pk_free')->firstOrFail();
        $this->assertSame(0, (int) $starter->price_minor);
        $this->assertSame(2000, (int) $starter->commission_bps);

        $doctor = $this->doctor();
        $booking = $this->acceptAndPay($this->book($doctor), 'e1');

        $this->assertSame(BookingFlow::PlatformPayment, $booking->flow);
        $this->assertSame(60000, $this->ledger($booking, LedgerType::CommissionEarned));     // Rs 600 = 20%
        $this->assertSame(240000, $this->ledger($booking, LedgerType::PractitionerPayable));  // Rs 2,400 = 80%
        $this->assertSame(240000, app(CommissionService::class)->balanceFor($doctor)['PKR']);
    }

    public function test_split_always_adds_up_to_the_fee_even_with_odd_amounts(): void
    {
        $booking = $this->acceptAndPay($this->book($this->doctor(feeMinor: 199900)), 'e1'); // Rs 1,999

        $commission = $this->ledger($booking, LedgerType::CommissionEarned);
        $share = $this->ledger($booking, LedgerType::PractitionerPayable);
        $this->assertSame(39980, $commission);
        $this->assertSame(199900, $commission + $share);
    }

    public function test_nothing_is_recorded_until_the_consultation_is_actually_paid(): void
    {
        $doctor = $this->doctor();
        $requested = $this->book($doctor, 0);
        $accepted = app(BookingService::class)->accept($this->book($doctor, 1), $doctor->user); // awaiting payment
        app(BookingService::class)->cancel($this->book($doctor, 2), $this->patient, 'Changed my mind');

        $this->assertSame(0, LedgerEntry::count());
        $this->assertNotNull($requested->id);
        $this->assertNotNull($accepted->id);
    }

    public function test_a_replayed_payment_never_records_commission_twice(): void
    {
        $booking = $this->acceptAndPay($this->book($this->doctor()), 'dup');
        $payment = $booking->payments()->first();
        app(PaymentService::class)->handle(new GatewayEvent('fake', 'dup', GatewayEvent::SUCCEEDED, paymentUuid: $payment->uuid));
        app(CommissionService::class)->recordPaidBooking($booking);

        $this->assertSame(2, LedgerEntry::where('booking_id', $booking->id)->count());
    }

    public function test_paid_plans_have_no_commission(): void
    {
        $booking = $this->book($this->doctor('pk_pro_1m'));

        $this->assertSame(BookingFlow::Direct, $booking->flow);
        $this->assertSame(0, (int) $booking->commission_minor);
        $this->assertSame(0, LedgerEntry::count());
    }

    public function test_admin_rate_change_applies_to_new_bookings_only(): void
    {
        $doctor = $this->doctor();
        $old = $this->book($doctor, 0);
        Plan::where('code', 'pk_free')->update(['commission_bps' => 1500]);
        $new = $this->book($doctor->fresh(), 1);

        $this->assertSame(60000, $this->ledger($this->acceptAndPay($old, 'a'), LedgerType::CommissionEarned));
        $this->assertSame(45000, $this->ledger($this->acceptAndPay($new, 'b'), LedgerType::CommissionEarned));
    }

    public function test_refund_gives_back_both_sides_and_every_screen_agrees(): void
    {
        $doctor = $this->doctor();
        $kept = $this->acceptAndPay($this->book($doctor, 0), 'k');
        $refunded = $this->acceptAndPay($this->book($doctor, 1), 'r');

        $refunded = app(BookingService::class)->cancel($refunded, $doctor->user, 'Clinic emergency');
        $this->actingAs($this->admin)->post(route('admin.bookings.refunded', $refunded))->assertRedirect();

        $this->assertSame(-60000, $this->ledger($refunded, LedgerType::CommissionReversal));
        $this->assertSame(-240000, $this->ledger($refunded, LedgerType::PayableReversal));

        $summary = app(CommissionService::class)->summary()['PKR'];
        $this->assertSame(['fees' => 300000, 'commission' => 60000, 'share' => 240000, 'refunded' => 300000, 'paid_out' => 0], $summary);
        $this->assertSame(240000, app(CommissionService::class)->balanceFor($doctor)['PKR']);

        // Admin wallet: net figures and the right amount owed.
        $this->actingAs($this->admin)->get(route('admin.wallet.index'))->assertOk()
            ->assertSee('Rs 600')->assertSee('Rs 2,400')->assertSee('refunded to patients');

        // Doctor sees their share reversed (Rs 2,400), never the full fee as a minus.
        $this->actingAs($doctor->user)->get(route('practitioner.earnings.index'))->assertOk()
            ->assertSee('Your share (80%)')->assertSee('Refunded to patient')
            ->assertSee('-Rs 2,400')->assertDontSee('-Rs 3,000');

        // Admin dashboard commission is net of the refund.
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Rs 600');
        $this->assertNotNull($kept->id);
    }

    public function test_old_single_row_refunds_are_converted(): void
    {
        $booking = $this->acceptAndPay($this->book($this->doctor()), 'old');
        LedgerEntry::query()->create([
            'practitioner_profile_id' => $booking->practitioner_profile_id, 'booking_id' => $booking->id,
            'type' => LedgerType::RefundReversal, 'amount_minor' => -300000, 'currency' => 'PKR', 'note' => 'Refund',
        ]);

        (require database_path('migrations/2026_11_15_100000_split_refund_reversals_in_ledger.php'))->up();

        $this->assertSame(0, LedgerEntry::where('type', LedgerType::RefundReversal)->count());
        $this->assertSame(-60000, $this->ledger($booking, LedgerType::CommissionReversal));
        $this->assertSame(-240000, $this->ledger($booking, LedgerType::PayableReversal));
        $this->assertSame(0, app(CommissionService::class)->balanceFor($booking->practitioner)['PKR']);
    }

    public function test_admin_can_record_a_manual_payment_and_commission_is_recorded(): void
    {
        $doctor = $this->doctor();
        $booking = app(BookingService::class)->accept($this->book($doctor), $doctor->user);

        $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking))->assertOk()->assertSee('Record a manual payment');
        $this->actingAs($this->admin)->post(route('admin.bookings.manual-payment', $booking), ['method' => 'JazzCash', 'reference' => 'JC-123'])
            ->assertSessionHasErrors('confirm_amount');
        $this->actingAs($this->admin)->post(route('admin.bookings.manual-payment', $booking), ['method' => 'JazzCash', 'reference' => 'JC-123', 'confirm_amount' => '1'])
            ->assertRedirect()->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status->value);
        $this->assertSame(60000, $this->ledger($booking, LedgerType::CommissionEarned));
        $this->assertSame(240000, $this->ledger($booking, LedgerType::PractitionerPayable));
        $this->assertSame('manual', $booking->payments()->first()->gateway);
    }

    public function test_manual_payment_only_for_bookings_waiting_for_payment(): void
    {
        $booking = $this->book($this->doctor()); // still "requested"

        $this->actingAs($this->admin)->post(route('admin.bookings.manual-payment', $booking), ['method' => 'Cash', 'confirm_amount' => '1'])->assertStatus(422);
        $this->assertSame(0, LedgerEntry::count());
    }

    public function test_live_site_without_a_gateway_shows_a_friendly_message_instead_of_an_error(): void
    {
        // The test gateway is only "unavailable" on the live (production) site.
        $this->app['env'] = 'production';
        $this->assertFalse(app(PaymentManager::class)->isAvailable('fake'));
        $this->assertTrue(app(PaymentManager::class)->isAvailable('stripe'));
        $this->app['env'] = 'testing';

        $doctor = $this->doctor();
        $booking = app(BookingService::class)->accept($this->book($doctor), $doctor->user);
        $this->mock(PaymentManager::class)->shouldReceive('isAvailable')->andReturnFalse();

        $this->actingAs($this->patient)->from(route('patient.bookings.show', $booking))
            ->post(route('patient.bookings.pay', $booking))
            ->assertRedirect(route('patient.bookings.show', $booking))->assertSessionHas('info');
        $this->assertSame(0, $booking->payments()->count());
    }
}
