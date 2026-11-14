<?php

namespace Tests\Feature\Auth;

use App\Enums\Market;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\Country;
use App\Models\User;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
            'gender' => 'female',
            'country_id' => Country::where('iso2', 'PK')->value('id'),
            'timezone' => 'Asia/Karachi',
            'terms' => '1',
        ], $overrides);
    }

    public function test_patient_can_register_and_gets_a_patient_profile(): void
    {
        $this->post('/register', $this->payload([
            'role' => 'patient', 'date_of_birth' => '1995-05-01', 'telehealth_consent' => '1',
        ]))->assertRedirect();

        $user = User::where('email', 'ayesha@example.com')->firstOrFail();
        $this->assertSame(Role::Patient, $user->role);
        $this->assertNotNull($user->patientProfile);
        $this->assertNull($user->practitionerProfile);
        $this->assertAuthenticatedAs($user);
    }

    public function test_pakistani_practitioner_gets_draft_profile_in_pk_market(): void
    {
        $this->post('/register', $this->payload(['role' => 'practitioner', 'email' => 'doc@example.com']))->assertRedirect();

        $profile = User::where('email', 'doc@example.com')->firstOrFail()->practitionerProfile;
        $this->assertSame(VerificationStatus::Draft, $profile->verification_status);
        $this->assertSame(Market::Pk, $profile->market);
        $this->assertSame('PKR', $profile->fee_currency);
        $this->assertFalse($profile->is_public);
    }

    public function test_international_practitioner_is_placed_in_usd_market(): void
    {
        $this->post('/register', $this->payload([
            'role' => 'practitioner', 'email' => 'uk@example.com', 'country_id' => Country::where('iso2', 'GB')->value('id'),
        ]))->assertRedirect();

        $profile = User::where('email', 'uk@example.com')->firstOrFail()->practitionerProfile;
        $this->assertSame(Market::Intl, $profile->market);
        $this->assertSame('USD', $profile->fee_currency);
    }

    public function test_nobody_can_register_as_admin(): void
    {
        $this->post('/register', $this->payload(['role' => 'admin']))->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'ayesha@example.com']);
    }

    public function test_patient_must_accept_terms_and_emergency_notice(): void
    {
        $this->post('/register', $this->payload(['role' => 'patient', 'date_of_birth' => '1995-05-01', 'terms' => null]))
            ->assertSessionHasErrors(['terms', 'telehealth_consent']);
    }

    public function test_eight_character_passwords_are_accepted_for_patients_and_practitioners(): void
    {
        $this->post('/register', $this->payload([
            'email' => 'pat8@example.com', 'password' => 'abcd1234', 'password_confirmation' => 'abcd1234',
            'role' => 'patient', 'date_of_birth' => '1995-05-01', 'telehealth_consent' => '1',
        ]))->assertSessionHasNoErrors();
        $this->assertTrue(User::where('email', 'pat8@example.com')->exists());

        auth()->logout();

        $this->post('/register', $this->payload([
            'email' => 'doc8@example.com', 'password' => 'abcd1234', 'password_confirmation' => 'abcd1234', 'role' => 'practitioner',
        ]))->assertSessionHasNoErrors();
        $this->assertTrue(User::where('email', 'doc8@example.com')->exists());
    }

    public function test_short_or_weak_passwords_are_rejected(): void
    {
        foreach (['abc1234', 'abcdefgh', '12345678'] as $weak) {
            $this->post('/register', $this->payload([
                'password' => $weak, 'password_confirmation' => $weak,
                'role' => 'patient', 'date_of_birth' => '1995-05-01', 'telehealth_consent' => '1',
            ]))->assertSessionHasErrors('password');
        }
        $this->assertFalse(User::where('email', 'ayesha@example.com')->exists());
    }

    public function test_register_page_explains_the_password_rule(): void
    {
        $this->get(route('register.patient'))->assertOk()
            ->assertSee('At least 8 characters, with letters and numbers.')
            ->assertSee('minlength="8"', false);
    }

    public function test_patients_and_practitioners_can_choose_transgender(): void
    {
        $this->get(route('register.patient'))->assertSee('Transgender');
        $this->get(route('register.practitioner'))->assertSee('Transgender');

        $this->post('/register', $this->payload([
            'role' => 'patient', 'gender' => 'transgender', 'date_of_birth' => '1995-05-01', 'telehealth_consent' => '1',
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('transgender', User::where('email', 'ayesha@example.com')->first()->patientProfile->gender->value);

        $this->post('/logout');
        $this->post('/register', $this->payload(['role' => 'practitioner', 'gender' => 'transgender', 'email' => 'dr.trans@example.com']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('transgender', User::where('email', 'dr.trans@example.com')->first()->practitionerProfile->gender->value);
    }
}
