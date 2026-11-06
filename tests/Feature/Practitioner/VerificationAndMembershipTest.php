<?php

namespace Tests\Feature\Practitioner;

use App\Enums\MembershipStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Language;
use App\Models\Membership;
use App\Models\Plan;
use App\Models\PractitionerProfile;
use App\Models\Specialty;
use App\Models\User;
use App\Notifications\PractitionerApproved;
use App\Notifications\PractitionerSubmitted;
use App\Payments\DTOs\GatewayEvent;
use App\Payments\PaymentService;
use App\Services\Membership\MembershipService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationAndMembershipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('public');
        Storage::fake('local');
    }

    private function completeOnboarding(PractitionerProfile $p): void
    {
        $u = $p->user;
        $derm = Specialty::where('slug', 'dermatologists')->first();

        $this->actingAs($u)->put(route('practitioner.onboarding.details'), [
            'title' => 'Dr.', 'gender' => 'female', 'primary_specialty_id' => $derm->id, 'qualifications' => 'MBBS, FCPS',
            'license_number' => 'PMDC-1', 'licensing_body' => 'PMDC', 'years_experience' => 5, 'timezone' => 'Asia/Karachi',
            'fee' => 2500, 'session_minutes' => 30,
        ])->assertSessionHasNoErrors();

        $this->actingAs($u)->put(route('practitioner.onboarding.focus'), [
            'sub_specialties' => [$derm->children()->first()->id],
            'languages' => [Language::where('code', 'ur')->value('id')],
            'custom_tags' => 'EMDR, Art Therapy',
        ])->assertSessionHasNoErrors();

        $this->actingAs($u)->post(route('practitioner.onboarding.about'), [
            'headline' => 'Gentle skin care for sensitive skin and adult acne.',
            'bio' => str_repeat('I help patients understand their skin and build routines that work. ', 6),
            'headshot' => UploadedFile::fake()->image('me.jpg', 800, 900)->size(500),
        ])->assertSessionHasNoErrors();

        $this->actingAs($u)->post(route('practitioner.onboarding.documents'), [
            'government_id' => UploadedFile::fake()->create('cnic.pdf', 400, 'application/pdf'),
            'degree' => UploadedFile::fake()->image('degree.jpg')->size(800),
            'license' => UploadedFile::fake()->create('pmdc.pdf', 900, 'application/pdf'),
        ])->assertSessionHasNoErrors();
    }

    public function test_full_journey_register_onboard_submit_approve_choose_free_plan_go_live(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = PractitionerProfile::factory()->create(['verification_status' => VerificationStatus::Draft]);

        $this->completeOnboarding($profile);
        $profile->refresh();

        // Headshot converted to WebP on the public disk; documents private.
        $this->assertStringEndsWith('.webp', $profile->headshot_path);
        Storage::disk('public')->assertExists($profile->headshot_path);
        $this->assertCount(3, $profile->documents);
        Storage::disk('local')->assertExists($profile->documents->first()->path);
        $this->assertSame(2, $profile->tags()->where('status', 'pending')->count());

        $this->actingAs($profile->user)->post(route('practitioner.onboarding.submit'))->assertRedirect();
        $this->assertSame(VerificationStatus::Pending, $profile->fresh()->verification_status);
        Notification::assertSentTo($admin, PractitionerSubmitted::class);

        $this->actingAs($admin)->post(route('admin.verifications.approve', $profile))->assertRedirect();
        $profile->refresh();
        $this->assertSame(VerificationStatus::Approved, $profile->verification_status);
        $this->assertFalse($profile->is_public, 'Hidden until a plan is chosen');
        Notification::assertSentTo($profile->user, PractitionerApproved::class);

        $this->actingAs($profile->user)->post(route('practitioner.membership.choose', 'pk_free'))->assertRedirect(route('practitioner.membership.show'));
        $this->assertTrue($profile->fresh()->is_public);
        $this->get('/doctor/'.$profile->slug)->assertOk();
    }

    public function test_incomplete_profile_cannot_be_submitted(): void
    {
        $profile = PractitionerProfile::factory()->create(['bio' => null]);

        $this->actingAs($profile->user)->post(route('practitioner.onboarding.submit'))->assertSessionHasErrors('profile');
        $this->assertSame(VerificationStatus::Draft, $profile->fresh()->verification_status);
    }

    public function test_bio_is_capped_at_200_words(): void
    {
        $profile = PractitionerProfile::factory()->create();

        $this->actingAs($profile->user)->post(route('practitioner.onboarding.about'), [
            'headline' => 'A perfectly reasonable headline for the card.',
            'bio' => str_repeat('word ', 201),
            'headshot' => UploadedFile::fake()->image('me.jpg', 600, 600),
        ])->assertSessionHasErrors('bio');
    }

    public function test_oversized_document_gets_the_client_error_message(): void
    {
        $profile = PractitionerProfile::factory()->create();

        $this->actingAs($profile->user)->post(route('practitioner.onboarding.documents'), [
            'license' => UploadedFile::fake()->create('big.pdf', 3500, 'application/pdf'),
        ])->assertSessionHasErrors(['license' => 'File size exceeds 3MB. Please upload a compressed document to ensure fast processing']);
    }

    public function test_oversized_headshot_is_rejected(): void
    {
        $profile = PractitionerProfile::factory()->create();

        $this->actingAs($profile->user)->post(route('practitioner.onboarding.about'), [
            'headline' => 'A perfectly reasonable headline for the card.',
            'bio' => str_repeat('word ', 60),
            'headshot' => UploadedFile::fake()->image('big.jpg', 800, 800)->size(2500),
        ])->assertSessionHasErrors('headshot');
    }

    public function test_documents_are_only_visible_to_owner_and_admin_and_admin_views_are_audited(): void
    {
        $profile = PractitionerProfile::factory()->create();
        $this->completeOnboarding($profile);
        $doc = $profile->documents()->first();

        auth()->logout();
        $this->get(route('documents.show', $doc))->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get(route('documents.show', $doc))->assertForbidden();
        $this->actingAs(PractitionerProfile::factory()->create()->user)->get(route('documents.show', $doc))->assertForbidden();
        $this->actingAs($profile->user)->get(route('documents.show', $doc))->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        $this->actingAs(User::factory()->admin()->create())->get(route('documents.show', $doc))->assertOk();
        $this->assertTrue(AuditLog::where('action', 'document.viewed')->exists());
    }

    public function test_changing_licence_after_approval_hides_profile_until_reverified(): void
    {
        $profile = PractitionerProfile::factory()->live()->create();
        $this->assertTrue($profile->fresh()->is_public);

        $this->actingAs($profile->user)->put(route('practitioner.onboarding.details'), [
            'gender' => 'male', 'primary_specialty_id' => $profile->primary_specialty_id, 'qualifications' => 'MBBS',
            'license_number' => 'NEW-999', 'licensing_body' => 'PMDC', 'years_experience' => 5, 'timezone' => 'Asia/Karachi',
            'fee' => 3000, 'session_minutes' => 30,
        ])->assertSessionHasNoErrors();

        $profile->refresh();
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);
        $this->assertFalse($profile->is_public);
    }

    public function test_plans_are_locked_until_verified_and_limited_to_own_market(): void
    {
        $draft = PractitionerProfile::factory()->create();
        $this->actingAs($draft->user)->post(route('practitioner.membership.choose', 'pk_free'))->assertSessionHasErrors('plan');

        $uk = PractitionerProfile::factory()->international()->approved()->create();
        $this->actingAs($uk->user)->post(route('practitioner.membership.choose', 'pk_free'))->assertSessionHasErrors('plan');
    }

    public function test_paid_plan_activates_after_payment(): void
    {
        $profile = PractitionerProfile::factory()->approved()->create();

        $this->actingAs($profile->user)->post(route('practitioner.membership.choose', 'pk_pro_1m'))->assertRedirect();
        $membership = Membership::where('practitioner_profile_id', $profile->id)->latest('id')->first();
        $this->assertSame(MembershipStatus::PendingPayment, $membership->status);
        $this->assertFalse($profile->fresh()->is_public);

        $payment = $membership->payments()->first();
        $this->assertSame(500000, $payment->amount_minor);
        app(PaymentService::class)->handle(new GatewayEvent('fake', 'm1', GatewayEvent::SUCCEEDED, paymentUuid: $payment->uuid));

        $membership->refresh();
        $this->assertSame(MembershipStatus::Active, $membership->status);
        $this->assertTrue($membership->ends_at->between(now()->addDays(27), now()->addDays(32)));
        $this->assertTrue($profile->fresh()->is_public);
    }

    public function test_expired_pk_paid_plan_falls_back_to_free_and_stays_listed(): void
    {
        $profile = PractitionerProfile::factory()->live('pk_pro_1m')->create();
        $profile->memberships()->update(['ends_at' => now()->subHour()]);

        app(MembershipService::class)->expireDue();

        $profile->refresh();
        $this->assertSame('pk_free', $profile->activeMembership->plan->code);
        $this->assertTrue($profile->is_public);
    }

    public function test_lapsed_international_subscription_hides_profile_after_grace(): void
    {
        $profile = PractitionerProfile::factory()->international()->live('intl_1m')->create();
        $profile->memberships()->update(['ends_at' => now()->subDays(5), 'auto_renew' => true]);

        app(MembershipService::class)->expireDue();

        $this->assertFalse($profile->fresh()->is_public);
        $this->assertNull($profile->fresh()->activeMembership);
    }

    public function test_stripe_renewal_failure_sets_past_due_and_cancellation_hides(): void
    {
        $profile = PractitionerProfile::factory()->international()->live('intl_1m')->create();
        $profile->memberships()->update(['gateway' => 'stripe', 'gateway_subscription_id' => 'sub_123']);
        $service = app(MembershipService::class);

        $service->subscriptionEvent(new GatewayEvent('stripe', 'e1', GatewayEvent::RENEWAL_FAILED, subscriptionId: 'sub_123'));
        $this->assertSame(MembershipStatus::PastDue, $profile->memberships()->first()->status);
        $this->assertTrue($profile->fresh()->is_public, 'still listed during the grace period');

        $service->subscriptionEvent(new GatewayEvent('stripe', 'e2', GatewayEvent::CANCELLED, subscriptionId: 'sub_123'));
        $this->assertFalse($profile->fresh()->is_public);
    }

    public function test_webhooks_reject_bad_signatures(): void
    {
        config(['services.jazzcash.integrity_salt' => 'salt', 'services.jazzcash.merchant_id' => 'MC1', 'services.stripe.webhook_secret' => 'whsec_test', 'services.stripe.secret' => 'sk_test_x']);

        $this->postJson('/webhooks/payments/jazzcash', ['pp_ResponseCode' => '000', 'pp_SecureHash' => 'forged'])->assertForbidden();
        $this->postJson('/webhooks/payments/stripe', ['type' => 'checkout.session.completed'], ['Stripe-Signature' => 't=1,v1=bad'])->assertStatus(400);
        $this->postJson('/webhooks/payments/unknown')->assertNotFound();
    }

    public function test_admin_screens_render(): void
    {
        $admin = User::factory()->admin()->create();
        $pending = PractitionerProfile::factory()->create(['verification_status' => VerificationStatus::Pending, 'submitted_at' => now()]);

        $this->actingAs($admin)->get(route('admin.verifications.index'))->assertOk()->assertSee($pending->user->name);
        $this->actingAs($admin)->get(route('admin.verifications.show', $pending))->assertOk();
        $this->actingAs($admin)->get(route('admin.practitioners.index'))->assertOk();
        $this->actingAs($pending->user)->get(route('practitioner.onboarding.show'))->assertOk();
        $this->actingAs($pending->user)->get(route('practitioner.membership.show'))->assertOk();
    }
}
