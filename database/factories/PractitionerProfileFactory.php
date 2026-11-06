<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Enums\VerificationStatus;
use App\Models\Country;
use App\Models\Plan;
use App\Models\PractitionerProfile;
use App\Models\Specialty;
use App\Models\User;
use App\Services\Membership\VisibilityService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PractitionerProfile> */
class PractitionerProfileFactory extends Factory
{
    protected $model = PractitionerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->practitioner(),
            'slug' => Str::slug(fake()->unique()->name()).'-'.Str::lower(Str::random(4)),
            'title' => 'Dr.',
            'gender' => fake()->randomElement(['male', 'female']),
            'country_id' => fn () => Country::query()->where('iso2', 'PK')->value('id'),
            'timezone' => 'Asia/Karachi',
            'market' => 'pk',
            'primary_specialty_id' => fn () => Specialty::query()->whereNull('parent_id')->value('id'),
            'headline' => fake()->sentence(),
            'qualifications' => 'MBBS, FCPS',
            'license_number' => 'PMDC-'.fake()->numerify('#####'),
            'licensing_body' => 'PMDC',
            'years_experience' => 8,
            'fee_minor' => 300000,
            'fee_currency' => 'PKR',
            'session_minutes' => 30,
            'bio' => fake()->paragraph(),
            'verification_status' => VerificationStatus::Draft,
        ];
    }

    public function international(): static
    {
        return $this->state(fn () => [
            'country_id' => Country::query()->where('iso2', 'GB')->value('id'),
            'timezone' => 'Europe/London',
            'market' => 'intl',
            'fee_minor' => 8000,
            'fee_currency' => 'USD',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['verification_status' => VerificationStatus::Approved, 'verified_at' => now()]);
    }

    /** Approved + active membership on the given plan + public. */
    public function live(string $planCode = 'pk_free'): static
    {
        return $this->approved()->afterCreating(function (PractitionerProfile $profile) use ($planCode) {
            $plan = Plan::query()->where('code', $planCode)->firstOrFail();
            $profile->memberships()->create([
                'plan_id' => $plan->id,
                'status' => MembershipStatus::Active,
                'starts_at' => now()->subDay(),
                'ends_at' => $plan->interval->addTo(now()->subDay()),
            ]);
            app(VisibilityService::class)->refresh($profile->fresh());
        });
    }
}
