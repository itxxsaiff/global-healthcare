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
}
