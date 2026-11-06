<?php

namespace App\Actions\Fortify;

use App\Enums\Gender;
use App\Enums\Market;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Country;
use App\Models\PractitionerProfile;
use App\Models\User;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Public registration for patients and practitioners. Admins are never
 * created here; use `php artisan app:create-admin`.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        $role = $input['role'] ?? null;
        $isPractitioner = $role === Role::Practitioner->value;
        $genders = collect($isPractitioner ? Gender::forPractitioners() : Gender::forPatients())->map->value->all();

        $data = Validator::make($input, [
            'role' => ['required', Rule::in([Role::Patient->value, Role::Practitioner->value])],
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'gender' => ['required', Rule::in($genders)],
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')],
            'timezone' => ['nullable', 'timezone:all'],
            'date_of_birth' => [Rule::requiredIf(! $isPractitioner), 'nullable', 'date', 'before:-13 years', 'after:-120 years'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9\s()-]{7,30}$/'],
            'terms' => ['accepted'],
        ] + ($isPractitioner ? [] : [
            // Patients only: acknowledge this is not an emergency service.
            'telehealth_consent' => ['accepted'],
        ]), [
            'date_of_birth.before' => 'You need to be at least 13 years old to create an account.',
            'terms.accepted' => 'Please accept the Terms and Privacy Policy to continue.',
            'telehealth_consent.accepted' => 'Please confirm you understand this service is not for emergencies.',
        ])->validate();

        $country = Country::query()->findOrFail($data['country_id']);
        $timezone = $data['timezone'] ?? $country->default_timezone;

        return DB::transaction(function () use ($data, $isPractitioner, $country, $timezone) {
            $user = new User([
                'name' => trim($data['name']),
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],
                'timezone' => $timezone,
            ]);
            $user->forceFill([
                'role' => $isPractitioner ? Role::Practitioner : Role::Patient,
                'status' => UserStatus::Active,
            ])->save();

            if ($isPractitioner) {
                $market = $country->isPakistan() ? Market::Pk : Market::Intl;
                $profile = new PractitionerProfile([
                    'slug' => Slug::unique(PractitionerProfile::class, $data['name']),
                    'gender' => $data['gender'],
                    'country_id' => $country->id,
                    'timezone' => $timezone,
                    'market' => $market,
                    'fee_currency' => $market->currency(),
                    'whatsapp_number' => $data['phone'] ?? null,
                ]);
                $profile->user()->associate($user);
                $profile->forceFill(['verification_status' => VerificationStatus::Draft])->save();
            } else {
                $user->patientProfile()->create([
                    'gender' => $data['gender'],
                    'date_of_birth' => $data['date_of_birth'],
                    'phone' => $data['phone'] ?? null,
                    'country_id' => $country->id,
                ]);
            }

            return $user;
        });
    }
}
