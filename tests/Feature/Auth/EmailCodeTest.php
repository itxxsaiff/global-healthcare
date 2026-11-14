<?php

namespace Tests\Feature\Auth;

use App\Models\Country;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationCode as CodeEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailCodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function register(string $role = 'patient', string $email = 'ayesha@example.com'): User
    {
        $this->post('/register', [
            'role' => $role, 'name' => 'Ayesha Khan', 'email' => $email,
            'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
            'gender' => 'female', 'country_id' => Country::where('iso2', 'PK')->value('id'), 'timezone' => 'Asia/Karachi',
            'terms' => '1', 'date_of_birth' => '1995-05-01', 'telehealth_consent' => '1',
        ])->assertRedirect();

        return User::where('email', $email)->firstOrFail();
    }

    private function lastCode(User $user): string
    {
        $code = null;
        Notification::assertSentTo($user, CodeEmail::class, function (CodeEmail $n) use (&$code) {
            $code = $n->code;

            return true;
        });

        return $code;
    }

    public function test_patient_gets_a_6_digit_code_and_verifies_with_it(): void
    {
        $user = $this->register();
        $code = $this->lastCode($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, EmailVerificationCode::first()->code_hash); // only a hash is stored
        $this->get('/patient')->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk()->assertSee('6-digit code')->assertSee('ayesha@example.com');

        $this->post(route('verification.code'), ['code' => $code])->assertRedirect('/patient'); // back to where they were heading

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame(0, EmailVerificationCode::count()); // used up
        $this->get('/patient')->assertOk();
    }

    public function test_practitioner_verifies_with_a_code_too(): void
    {
        $user = $this->register('practitioner', 'dr.sana@example.com');
        $this->post(route('verification.code'), ['code' => $this->lastCode($user)])->assertRedirect();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNotNull($user->fresh()->practitionerProfile);
    }

    public function test_code_with_a_space_in_the_middle_is_accepted(): void
    {
        $user = $this->register();
        $code = $this->lastCode($user);

        $this->post(route('verification.code'), ['code' => substr($code, 0, 3).' '.substr($code, 3)]);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_wrong_code_is_rejected_and_locks_after_five_tries(): void
    {
        $user = $this->register();
        $code = $this->lastCode($user);
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->post(route('verification.code'), ['code' => $wrong])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());

        foreach (range(1, 4) as $i) {
            $this->post(route('verification.code'), ['code' => $wrong]);
        }
        // Even the right code is refused once locked; a new code is needed.
        $this->post(route('verification.code'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_code_expires_after_ten_minutes(): void
    {
        $user = $this->register();
        $code = $this->lastCode($user);

        $this->travel(11)->minutes();
        $this->post(route('verification.code'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_waits_a_minute_then_sends_a_new_code_and_old_one_stops_working(): void
    {
        $user = $this->register();
        $old = $this->lastCode($user);

        $this->from(route('verification.notice'))->post(route('verification.code.resend'))->assertSessionHas('warning');
        Notification::assertSentToTimes($user, CodeEmail::class, 1);

        $this->travel(61)->seconds();
        $this->from(route('verification.notice'))->post(route('verification.code.resend'))->assertSessionHas('success');
        Notification::assertSentToTimes($user, CodeEmail::class, 2);

        $new = null;
        Notification::assertSentTo($user, CodeEmail::class, function (CodeEmail $n) use (&$new) {
            $new = $n->code; // last one wins

            return true;
        });
        if ($new !== $old) {
            $this->post(route('verification.code'), ['code' => $old])->assertSessionHasErrors('code');
        }
        $this->post(route('verification.code'), ['code' => $new]);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_one_users_code_cannot_verify_another_user(): void
    {
        $a = $this->register('patient', 'a@example.com');
        $codeA = $this->lastCode($a);
        $this->post('/logout');
        $b = $this->register('patient', 'b@example.com');

        $this->actingAs($b)->post(route('verification.code'), ['code' => $codeA]);
        if ($codeA !== $this->lastCode($b)) {
            $this->assertFalse($b->fresh()->hasVerifiedEmail());
        }
        $this->assertFalse($a->fresh()->hasVerifiedEmail());
    }

    public function test_user_without_a_code_yet_can_request_one(): void
    {
        // e.g. registered before codes existed, or the mail server was down.
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('verification.notice'))->assertOk()->assertSee('Send me a code');
        $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.code.resend'))->assertSessionHas('success');
        $this->actingAs($user)->post(route('verification.code'), ['code' => $this->lastCode($user)]);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_links_sent_before_the_change_still_work(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->get($url)->assertRedirect();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_the_email_shows_the_code(): void
    {
        $user = User::factory()->unverified()->create(['name' => 'Ayesha Khan']);
        $mail = (new CodeEmail('482913', 10))->toMail($user);

        $this->assertStringContainsString('482913', $mail->subject);
        $this->assertStringContainsString('482 913', (string) $mail->render());
        $this->assertStringContainsString('10 minutes', (string) $mail->render());
    }
}
