<?php

namespace Tests\Feature\Practitioner;

use App\Models\AuditLog;
use App\Models\PractitionerProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntroVideoTest extends TestCase
{
    private PractitionerProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->profile = PractitionerProfile::factory()->live()->create();
    }

    private function fixture(string $name, string $as = 'intro.mp4'): UploadedFile
    {
        return new UploadedFile(base_path("tests/fixtures/{$name}"), $as, 'video/mp4', null, true);
    }

    public function test_profile_works_without_a_video(): void
    {
        $this->get(route('practitioners.show', $this->profile))->assertOk()->assertDontSee('A short introduction');
        $this->actingAs($this->profile->user)->get(route('practitioner.onboarding.show', ['step' => 'about']))
            ->assertOk()->assertSee('Introduction video')->assertSee('Optional');
    }

    public function test_youtube_link_is_saved_and_shown_as_click_to_play(): void
    {
        $this->actingAs($this->profile->user)->post(route('practitioner.video.update'), [
            'source' => 'link', 'intro_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=3s',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->get(route('practitioners.show', $this->profile))->assertOk()
            ->assertSee('A short introduction')
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', false)
            ->assertDontSee('<iframe src=', false); // iframe only appears after a click
    }

    public function test_non_video_links_are_rejected(): void
    {
        $this->actingAs($this->profile->user)->post(route('practitioner.video.update'), [
            'source' => 'link', 'intro_video_url' => 'https://example.com/video',
        ])->assertSessionHasErrors('intro_video_url');

        $this->assertNull($this->profile->fresh()->intro_video_url);
    }

    public function test_short_upload_is_stored_and_replaces_the_link(): void
    {
        $this->profile->forceFill(['intro_video_url' => 'https://vimeo.com/123456'])->save();

        $this->actingAs($this->profile->user)->postJson(route('practitioner.video.update'), [
            'source' => 'upload', 'video' => $this->fixture('intro-5s.mp4'),
        ])->assertOk()->assertJsonStructure(['redirect']);

        $p = $this->profile->fresh();
        $this->assertNull($p->intro_video_url);
        $this->assertStringStartsWith('intro-videos/', $p->intro_video_path);
        $this->assertSame(5, $p->intro_video_seconds);
        Storage::disk('public')->assertExists($p->intro_video_path);

        $this->get(route('practitioners.show', $p))->assertOk()->assertSee('0:05')->assertSee($p->intro_video_path);
    }

    public function test_long_uploads_are_rejected(): void
    {
        $this->actingAs($this->profile->user)->postJson(route('practitioner.video.update'), [
            'source' => 'upload', 'video' => $this->fixture('intro-130s.mp4'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['video' => 'under 2 minutes']);

        $this->assertNull($this->profile->fresh()->intro_video_path);
    }

    public function test_non_video_files_are_rejected(): void
    {
        $this->actingAs($this->profile->user)->postJson(route('practitioner.video.update'), [
            'source' => 'upload', 'video' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('video');
    }

    public function test_practitioner_can_remove_and_old_file_is_deleted(): void
    {
        $this->actingAs($this->profile->user)->postJson(route('practitioner.video.update'), [
            'source' => 'upload', 'video' => $this->fixture('intro-5s.mp4'),
        ])->assertOk();
        $path = $this->profile->fresh()->intro_video_path;

        $this->actingAs($this->profile->user)->delete(route('practitioner.video.destroy'))->assertRedirect();

        Storage::disk('public')->assertMissing($path);
        $this->assertFalse($this->profile->fresh()->hasIntroVideo());
    }

    public function test_admin_can_take_a_video_down(): void
    {
        $this->profile->forceFill(['intro_video_url' => 'https://youtu.be/dQw4w9WgXcQ'])->save();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.verifications.show', $this->profile))->assertOk()->assertSee('YouTube link');
        $this->actingAs($admin)->delete(route('admin.verifications.video.destroy', $this->profile))->assertRedirect();

        $this->assertNull($this->profile->fresh()->intro_video_url);
        $this->assertTrue(AuditLog::where('action', 'practitioner.video_removed')->exists());
    }

    public function test_saving_professional_details_keeps_the_video(): void
    {
        $this->profile->forceFill(['intro_video_url' => 'https://youtu.be/dQw4w9WgXcQ'])->save();
        $p = $this->profile;

        $this->actingAs($p->user)->put(route('practitioner.onboarding.details'), [
            'title' => 'Dr.', 'gender' => 'female', 'primary_specialty_id' => $p->primary_specialty_id, 'qualifications' => 'MBBS',
            'license_number' => $p->license_number, 'licensing_body' => 'PMDC', 'years_experience' => 5, 'timezone' => 'Asia/Karachi',
            'fee' => 2500, 'session_minutes' => 30,
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $p->fresh()->intro_video_url);
    }
}
