<?php

namespace Tests\Feature\Community;

use App\Enums\ArticleStatus;
use App\Enums\BookingStatus;
use App\Enums\ModerationStatus;
use App\Models\Article;
use App\Models\Booking;
use App\Models\PractitionerProfile;
use App\Models\Question;
use App\Models\Review;
use App\Models\Specialty;
use App\Models\User;
use App\Payments\DTOs\GatewayEvent;
use App\Payments\PaymentService;
use App\Services\Booking\BookingService;
use App\Services\Booking\SlotService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
    }

    private function completedPaidBooking(): Booking
    {
        $doctor = PractitionerProfile::factory()->live()->create();
        $doctor->availabilityRules()->create(['weekday' => now()->addDay()->dayOfWeek, 'start_time' => '00:00', 'end_time' => '23:30']);
        $doctor->availabilityRules()->create(['weekday' => now()->addDays(2)->dayOfWeek, 'start_time' => '00:00', 'end_time' => '23:30']);
        $patient = User::factory()->create();
        $service = app(BookingService::class);

        $booking = $service->accept($service->request($patient, $doctor->fresh(), app(SlotService::class)->slotsUtc($doctor->fresh(), 3)[0], null, 'UTC'), $doctor->user);
        $this->actingAs($patient)->post(route('patient.bookings.pay', $booking));
        app(PaymentService::class)->handle(new GatewayEvent('fake', 'p'.$booking->id, GatewayEvent::SUCCEEDED, paymentUuid: $booking->payments()->first()->uuid));
        $booking->refresh()->forceFill(['starts_at' => now()->subHours(3), 'ends_at' => now()->subHours(2)])->save();
        $service->complete($booking->fresh());

        return $booking->fresh();
    }

    public function test_only_the_patient_of_a_completed_paid_booking_can_review_once_and_admin_approval_updates_rating(): void
    {
        $booking = $this->completedPaidBooking();
        $this->assertSame(BookingStatus::Completed, $booking->status);

        $this->actingAs(User::factory()->create())->get(route('patient.reviews.create', $booking))->assertNotFound();

        $this->actingAs($booking->patient)->post(route('patient.reviews.store', $booking), ['rating' => 4, 'body' => 'Very helpful.'])->assertRedirect();
        $review = Review::firstOrFail();
        $this->assertSame(ModerationStatus::Pending, $review->status);
        $this->assertSame(0, $booking->practitioner->fresh()->reviews_count, 'Not counted until approved');

        $this->actingAs($booking->patient)->post(route('patient.reviews.store', $booking), ['rating' => 5])->assertSessionHasErrors('rating');

        $this->actingAs($this->admin)->patch(route('admin.reviews.update', $review), ['status' => 'approved'])->assertRedirect();
        $doctor = $booking->practitioner->fresh();
        $this->assertSame(1, $doctor->reviews_count);
        $this->assertEquals(4.0, $doctor->rating_avg);
        $this->get('/doctor/'.$doctor->slug)->assertSee('Very helpful.');
    }

    public function test_unpaid_or_unfinished_bookings_cannot_be_reviewed(): void
    {
        $doctor = PractitionerProfile::factory()->live()->create();
        $doctor->availabilityRules()->create(['weekday' => now()->addDay()->dayOfWeek, 'start_time' => '00:00', 'end_time' => '23:30']);
        $patient = User::factory()->create();
        $booking = app(BookingService::class)->request($patient, $doctor->fresh(), app(SlotService::class)->slotsUtc($doctor->fresh(), 2)[0], null, 'UTC');

        $this->actingAs($patient)->get(route('patient.reviews.create', $booking))->assertNotFound();
    }

    public function test_anonymous_question_is_moderated_then_answered_publicly(): void
    {
        $patient = User::factory()->create(['name' => 'Very Secret Name']);
        $patient->patientProfile()->create(['gender' => 'female', 'date_of_birth' => now()->subYears(30)]);
        $derm = Specialty::where('slug', 'dermatologists')->first();

        $this->actingAs($patient)->post(route('patient.questions.store'), [
            'specialty_id' => $derm->id,
            'title' => 'Why does my acne get worse before my period?',
            'body' => 'Every month about a week before my period I get painful spots on my chin and jaw. What can I do?',
            'share_profile' => '1',
        ])->assertRedirect();

        $question = Question::firstOrFail();
        $this->get(route('questions.show', $question))->assertNotFound();

        $this->actingAs($this->admin)->patch(route('admin.questions.update', $question), ['status' => 'approved'])->assertRedirect();
        $this->get(route('questions.show', $question))->assertOk()->assertSee('Female, 30 yrs')->assertDontSee('Very Secret Name');

        $doctor = PractitionerProfile::factory()->live()->create();
        $this->actingAs($doctor->user)->post(route('practitioner.answers.store', $question), [
            'body' => 'This pattern is very typical of hormonal acne. <script>alert(1)</script> A gentle routine plus a consultation to discuss options usually helps a lot.',
        ])->assertRedirect();

        $this->assertSame(1, $question->fresh()->answers_count);
        $this->get(route('questions.show', $question))
            ->assertSee('typical of hormonal acne')
            ->assertSee($doctor->displayName())
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_hidden_practitioners_cannot_answer(): void
    {
        $question = Question::factory()->published()->create();
        $draft = PractitionerProfile::factory()->create();

        $this->actingAs($draft->user)->post(route('practitioner.answers.store', $question), ['body' => str_repeat('advice ', 30)])->assertForbidden();
    }

    public function test_article_word_limits_image_compression_and_approval(): void
    {
        $doctor = PractitionerProfile::factory()->live()->create();
        $image = UploadedFile::fake()->image('cover.jpg', 2400, 1400)->size(1800);

        // Too short to submit as a standard article (600–1,000 words).
        $this->actingAs($doctor->user)->post(route('practitioner.articles.store'), [
            'type' => 'standard', 'title' => 'Living well with eczema', 'body' => '<p>'.str_repeat('word ', 300).'</p>',
            'action' => 'submit', 'featured_image' => $image,
        ])->assertSessionHasErrors('body');

        // Draft saving has no word minimum.
        $this->actingAs($doctor->user)->post(route('practitioner.articles.store'), [
            'type' => 'standard', 'title' => 'Living well with eczema', 'body' => '<p>'.str_repeat('word ', 300).'</p>', 'action' => 'draft',
        ])->assertSessionHasNoErrors();
        $article = Article::firstOrFail();
        $this->assertSame(ArticleStatus::Draft, $article->status);

        $this->actingAs($doctor->user)->put(route('practitioner.articles.update', $article), [
            'type' => 'standard', 'title' => 'Living well with eczema', 'body' => '<h2>Intro</h2><p>'.str_repeat('skin ', 700).'</p><script>x</script>',
            'action' => 'submit', 'featured_image' => $image,
        ])->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertSame(ArticleStatus::Pending, $article->status);
        $this->assertStringNotContainsString('<script>', $article->body);
        $this->assertStringEndsWith('.webp', $article->featured_image_path);
        $this->assertLessThanOrEqual(200 * 1024, Storage::disk('public')->size($article->featured_image_path));
        $this->get(route('articles.show', $article))->assertNotFound();

        $this->actingAs($this->admin)->patch(route('admin.articles.update', $article), ['decision' => 'approve'])->assertRedirect();
        $this->get(route('articles.show', $article))->assertOk()->assertSee($doctor->displayName());
        $this->get(route('articles.index'))->assertSee('Living well with eczema');
    }

    public function test_practitioners_cannot_edit_each_others_articles(): void
    {
        $article = Article::factory()->create();
        $other = PractitionerProfile::factory()->live()->create();

        $this->actingAs($other->user)->get(route('practitioner.articles.edit', $article))->assertNotFound();
    }

    public function test_approved_custom_tag_becomes_a_directory_filter(): void
    {
        $doctor = PractitionerProfile::factory()->live()->create();
        $this->actingAs($doctor->user)->put(route('practitioner.onboarding.focus'), [
            'languages' => [\App\Models\Language::first()->id], 'custom_tags' => 'Hypnotherapy',
        ]);
        $tag = \App\Models\Tag::where('slug', 'hypnotherapy')->firstOrFail();
        $this->get('/find-a-doctor')->assertDontSee('Hypnotherapy');

        $this->actingAs($this->admin)->patch(route('admin.tags.update', $tag), ['status' => 'approved']);
        auth()->logout();
        $this->get('/find-a-doctor?tags[]=hypnotherapy')->assertSee('Hypnotherapy')->assertSee($doctor->user->name);
    }

    public function test_moderation_and_authoring_screens_render(): void
    {
        $doctor = PractitionerProfile::factory()->live()->create();
        $article = Article::factory()->create(['practitioner_profile_id' => $doctor->id, 'author_id' => $doctor->user_id]);

        foreach (['admin.reviews.index', 'admin.questions.index', 'admin.articles.index', 'admin.tags.index'] as $r) {
            $this->actingAs($this->admin)->get(route($r))->assertOk();
        }
        $this->actingAs($this->admin)->get(route('admin.articles.show', $article))->assertOk();
        $this->actingAs($doctor->user)->get(route('practitioner.articles.index'))->assertOk();
        $this->actingAs($doctor->user)->get(route('practitioner.articles.create'))->assertOk();
        $this->actingAs($doctor->user)->get(route('practitioner.articles.edit', $article))->assertOk();
        $this->actingAs($doctor->user)->get(route('practitioner.questions.index'))->assertOk();
        $patient = User::factory()->create();
        $this->actingAs($patient)->get(route('patient.questions.create'))->assertOk();
        $this->actingAs($patient)->get(route('patient.questions.index'))->assertOk();
    }
}
