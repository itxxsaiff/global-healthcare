<?php

namespace Database\Seeders;

use App\Enums\BookingFlow;
use App\Enums\BookingStatus;
use App\Enums\MembershipStatus;
use App\Enums\ModerationStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\Answer;
use App\Models\City;
use App\Models\Condition;
use App\Models\Country;
use App\Models\Language;
use App\Models\Plan;
use App\Models\PractitionerProfile;
use App\Models\Question;
use App\Models\Review;
use App\Models\Specialty;
use App\Models\Tag;
use App\Models\User;
use App\Services\Membership\VisibilityService;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Local/demo data only. Never runs in production (see DatabaseSeeder).
 * All demo accounts use the password "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->user('Site Admin', 'admin@globalhealthcare.test', Role::Admin);
        $patient = $this->user('Ayesha Khan', 'patient@globalhealthcare.test', Role::Patient, 'Asia/Karachi');
        $patient->patientProfile()->firstOrCreate([], ['gender' => 'female', 'date_of_birth' => '1994-03-12', 'country_id' => Country::where('iso2', 'PK')->value('id')]);

        $extraPatients = collect(['Bilal Ahmed', 'Sara Malik', 'Omar Farooq', 'Hina Raza', 'Daniel Brooks', 'Fatima Al Nuaimi'])
            ->map(fn ($name) => $this->user($name, Str::slug($name, '.').'@example.test', Role::Patient))
            ->push($patient);

        $doctors = [
            // name, title, gender, country, city, specialty slug, subs, conditions, languages, fee (major), plan, years, headline
            ['Sana Mirza', 'Dr.', 'female', 'PK', 'lahore', 'dermatologists', ['acne-acne-scars', 'pigmentation-melasma'], ['acne', 'pigmentation', 'hair-loss'], ['ur', 'en'], 3000, 'pk_free', 9, 'Calm, practical skin care for acne-prone and sensitive skin.'],
            ['Hamza Qureshi', 'Dr.', 'male', 'PK', 'karachi', 'psychiatrists', ['depression', 'anxiety-disorders', 'ocd'], ['anxiety', 'depression', 'insomnia', 'ocd'], ['ur', 'en'], 4500, 'pk_pro_1m', 12, 'Psychiatrist who believes medication and conversation go hand in hand.'],
            ['Mehwish Tariq', 'Ms.', 'female', 'PK', 'islamabad', 'clinical-psychologists-counselors', ['cognitive-behavioural-therapy-cbt', 'relationship-family-counselling'], ['anxiety', 'stress-burnout', 'relationship-problems'], ['ur', 'en'], 3500, 'pk_free', 7, 'CBT-trained psychologist helping adults untangle anxiety and burnout.'],
            ['Faisal Rehman', 'Dr.', 'male', 'PK', 'rawalpindi', 'general-physicians', ['internal-medicine', 'blood-pressure'], ['fever', 'high-blood-pressure', 'diabetes'], ['ur', 'en'], 2000, 'pk_free', 15, 'Family physician for the everyday things that still deserve proper attention.'],
            ['Amna Javed', 'Dr.', 'female', 'PK', 'lahore', 'gynecologists-obstetricians', ['pcos-care', 'pregnancy-care', 'menstrual-problems'], ['pcos', 'irregular-periods', 'pregnancy'], ['ur', 'en'], 3500, 'pk_pro_12m', 11, 'Gynaecologist with a special interest in PCOS and first pregnancies.'],
            ['Usman Siddiqui', 'Dr.', 'male', 'PK', 'faisalabad', 'endocrinologists', ['diabetes-management', 'thyroid-disorders'], ['diabetes', 'thyroid-problems', 'weight-loss-gain'], ['ur', 'en'], 4000, 'pk_free', 10, 'Diabetes and thyroid care, explained without the jargon.'],
            ['Rabia Anwar', 'Ms.', 'female', 'PK', 'karachi', 'dietitians-clinical-nutritionists', ['weight-management', 'pcos-nutrition', 'gut-health'], ['weight-loss-gain', 'pcos', 'ibs'], ['ur', 'en'], 2500, 'pk_free', 6, 'Desi-food-friendly meal plans that you can actually stick to.'],
            ['Kamran Baig', 'Dr.', 'male', 'PK', 'peshawar', 'pediatricians', ['child-nutrition-growth', 'childhood-infections'], ['fever', 'child-growth-concerns', 'adhd'], ['ur', 'en'], 2500, 'pk_free', 14, 'Child specialist and father of three. Parents\' questions always welcome.'],
            ['Nida Shah', 'Ms.', 'female', 'PK', 'lahore', 'speech-occupational-therapists', ['speech-delay', 'autism-support'], ['speech-delay', 'autism', 'stuttering'], ['ur', 'en'], 3000, 'pk_free', 8, 'Play-based speech therapy for little ones who are finding their words.'],
            ['Imran Malik', 'Dr.', 'male', 'PK', 'multan', 'urologists', ['kidney-stones', 'prostate-health'], ['kidney-stones', 'urine-infection', 'erectile-dysfunction'], ['ur', 'en'], 3000, 'pk_free', 13, 'Urologist offering discreet, straightforward consultations.'],
            ['Layla Haddad', 'Dr.', 'female', 'AE', 'dubai', 'dermatologists', ['cosmetic-dermatology', 'eczema-psoriasis'], ['eczema', 'acne', 'pigmentation'], ['ar', 'en', 'fr'], 80, 'intl_12m', 10, 'Dubai-based dermatologist treating skin conditions in hot, dry climates.'],
            ['James Whitfield', 'Dr.', 'male', 'GB', 'manchester', 'neurologists', ['migraine-headache', 'epilepsy-seizures'], ['migraine-headache', 'epilepsy'], ['en'], 120, 'intl_1m', 18, 'Consultant neurologist with a long-standing migraine clinic.'],
            ['Zeynep Arslan', 'Ms.', 'female', 'CA', 'toronto', 'clinical-psychologists-counselors', ['trauma-grief', 'relationship-family-counselling'], ['anxiety', 'depression', 'relationship-problems'], ['tr', 'en', 'de'], 90, 'intl_6m', 9, 'Bilingual therapist supporting immigrants and their families.'],
            ['Ahmed Al Harbi', 'Dr.', 'male', 'SA', 'riyadh', 'gastroenterologists', ['acid-reflux-gerd', 'irritable-bowel-syndrome'], ['stomach-pain-acid-reflux', 'ibs', 'fatty-liver'], ['ar', 'en'], 100, 'intl_12m', 16, 'Gut health specialist. Second opinions on long-standing reflux and IBS.'],
            ['Saad Hussain', 'Dr.', 'male', 'PK', 'lahore', 'sexologists', ['erectile-dysfunction', 'sexual-wellness-counselling'], ['erectile-dysfunction', 'low-libido'], ['ur', 'en'], 4000, 'pk_pro_6m', 10, 'Confidential, judgement-free sexual health consultations.'],
            ['Tahir Mahmood', 'Dr.', 'male', 'PK', 'islamabad', 'general-surgeons', ['pre-operative-consultation', 'surgical-second-opinion'], ['hernia'], ['ur', 'en'], 3500, 'pk_free', 20, 'Planning surgery? Get a calm, unhurried second opinion first.'],
        ];

        $visibility = app(VisibilityService::class);
        $languages = Language::pluck('id', 'code');

        $emdr = Tag::firstOrCreate(['slug' => 'emdr'], ['name' => 'EMDR', 'status' => ModerationStatus::Approved]);
        $artTherapy = Tag::firstOrCreate(['slug' => 'art-therapy'], ['name' => 'Art Therapy', 'status' => ModerationStatus::Approved]);

        foreach ($doctors as $i => [$name, $title, $gender, $iso, $citySlug, $specSlug, $subs, $conds, $langs, $fee, $planCode, $years, $headline]) {
            $user = $this->user($name, Str::slug($name, '.').'@doctor.test', Role::Practitioner);
            $country = Country::where('iso2', $iso)->first();
            $city = City::where('country_id', $country->id)->where('slug', $citySlug)->first();
            $specialty = Specialty::where('slug', $specSlug)->first();
            $isPk = $iso === 'PK';

            $profile = PractitionerProfile::query()->where('user_id', $user->id)->first() ?? new PractitionerProfile;
            $profile->fill([
                'slug' => Str::slug($title.' '.$name.' '.$specialty->practitioner_label),
                'title' => $title,
                'gender' => $gender,
                'country_id' => $country->id,
                'city_id' => $city?->id,
                'timezone' => $country->default_timezone,
                'market' => $isPk ? 'pk' : 'intl',
                'primary_specialty_id' => $specialty->id,
                'headline' => $headline,
                'qualifications' => $isPk ? 'MBBS, FCPS ('.$specialty->practitioner_label.')' : 'MD, Board Certified '.$specialty->practitioner_label,
                'license_number' => $isPk ? 'PMDC-'.(40000 + $i * 137) : strtoupper($iso).'-'.(1000 + $i * 71),
                'licensing_body' => $isPk ? 'Pakistan Medical & Dental Council' : 'National medical council',
                'years_experience' => $years,
                'fee_minor' => Money::toMinor($fee),
                'fee_currency' => $isPk ? 'PKR' : 'USD',
                'session_minutes' => $i % 3 === 0 ? 45 : 30,
                'bio' => $this->bio($name, $specialty->practitioner_label, $years),
                'linkedin_url' => 'https://www.linkedin.com/in/'.Str::slug($name),
                'whatsapp_number' => $isPk ? '+92300'.str_pad((string) (1000000 + $i * 7919), 7, '0') : null,
            ]);
            $profile->user()->associate($user);
            $profile->forceFill([
                'verification_status' => VerificationStatus::Approved,
                'submitted_at' => now()->subDays(30),
                'verified_at' => now()->subDays(28),
                'verified_by' => $admin->id,
                'is_featured' => $i < 8,
            ])->save();

            // Demo headshots (royalty-free stock photos, see database/seeders/photos/README.md).
            $photo = database_path('seeders/photos/'.Str::slug($name).'.webp');
            if (is_file($photo)) {
                $path = 'headshots/demo/'.Str::slug($name).'.webp';
                Storage::disk('public')->put($path, file_get_contents($photo), ['visibility' => 'public']);
                $profile->forceFill(['headshot_path' => $path])->save();
            }

            $profile->subSpecialties()->sync(Specialty::whereIn('slug', $subs)->pluck('id'));
            $profile->conditions()->sync(Condition::whereIn('slug', $conds)->pluck('id'));
            $profile->languages()->sync(collect($langs)->map(fn ($c) => $languages[$c]));
            if ($specSlug === 'clinical-psychologists-counselors') {
                $profile->tags()->syncWithoutDetaching([$i % 2 ? $emdr->id : $artTherapy->id]);
            }

            // Weekly availability in the practitioner's own time zone.
            $profile->availabilityRules()->delete();
            foreach ([1, 2, 3, 4, 6] as $day) {
                $profile->availabilityRules()->create(['weekday' => $day, 'start_time' => $i % 2 ? '10:00' : '16:00', 'end_time' => $i % 2 ? '14:00' : '21:00']);
            }

            $plan = Plan::where('code', $planCode)->first();
            $profile->memberships()->delete();
            $profile->memberships()->create([
                'plan_id' => $plan->id,
                'status' => MembershipStatus::Active,
                'starts_at' => now()->subDays(20),
                'ends_at' => $plan->interval->addTo(now()->subDays(20)),
                'auto_renew' => ! $isPk,
                'gateway' => $plan->isFree() ? null : 'fake',
            ]);

            $visibility->refresh($profile);
            $this->reviews($profile, $plan, $extraPatients);
        }

        $this->forum();
    }

    private function user(string $name, string $email, Role $role, string $tz = 'UTC'): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->fill(['name' => $name, 'password' => 'password', 'timezone' => $tz]);
        $user->forceFill(['role' => $role, 'status' => 'active', 'email_verified_at' => now()])->save();

        return $user;
    }

    private function bio(string $name, string $label, int $years): string
    {
        $first = explode(' ', $name)[0];

        return "I'm {$first}, a {$label} with {$years} years of experience. Most of the people I see have already searched the internet, tried a few things and are a little worried. "
            ."My job is to listen properly, explain what is really going on in plain language, and agree a plan that fits your life. "
            ."Online consultations work well for follow-ups, second opinions and most first visits. If I think you need to be seen in person, I'll tell you honestly and help you find the right place.";
    }

    /** A few completed, paid bookings with approved reviews so ratings render. */
    private function reviews(PractitionerProfile $profile, Plan $plan, $patients): void
    {
        if ($profile->reviews()->exists()) {
            return;
        }

        $samples = [
            [5, 'Really listened and didn\'t rush me. Explained everything clearly and the plan is already helping.'],
            [5, 'Punctual, kind and very practical advice. Booking was easy too.'],
            [4, 'Good consultation. The call started a few minutes late but the advice was excellent.'],
            [5, 'I was nervous about talking about this, but felt completely comfortable. Highly recommend.'],
        ];

        foreach ($patients->shuffle()->take(rand(2, 4))->values() as $k => $patient) {
            $start = now()->subDays(10 + $k * 3)->setTime(11, 0)->utc();
            $bookingId = DB::table('bookings')->insertGetId([
                'reference' => 'GH-'.strtoupper(Str::random(6)),
                'patient_id' => $patient->id,
                'practitioner_profile_id' => $profile->id,
                'plan_id' => $plan->id,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($profile->session_minutes),
                'practitioner_timezone' => $profile->timezone,
                'patient_timezone' => $patient->timezone,
                'status' => BookingStatus::Completed->value,
                'flow' => $plan->direct_dealing ? BookingFlow::Direct->value : BookingFlow::PlatformPayment->value,
                'fee_minor' => $profile->fee_minor,
                'currency' => $profile->fee_currency,
                'commission_bps' => $plan->commission_bps,
                'commission_minor' => Money::percentOf($profile->fee_minor, $plan->commission_bps),
                'practitioner_earning_minor' => $profile->fee_minor - Money::percentOf($profile->fee_minor, $plan->commission_bps),
                'confirmed_at' => $start->copy()->subDay(),
                'completed_at' => $start->copy()->addHour(),
                'created_at' => $start->copy()->subDays(2),
                'updated_at' => $start,
            ]);

            [$rating, $body] = $samples[($profile->id + $k) % count($samples)];
            $review = new Review(['rating' => $rating, 'body' => $body]);
            $review->forceFill([
                'booking_id' => $bookingId,
                'patient_id' => $patient->id,
                'practitioner_profile_id' => $profile->id,
                'status' => ModerationStatus::Approved,
                'moderated_at' => now(),
            ])->save();
        }

        $stats = $profile->approvedReviews()->reorder()->selectRaw('AVG(rating) avg, COUNT(*) c')->first();
        $profile->forceFill(['rating_avg' => round((float) $stats->avg, 2), 'reviews_count' => $stats->c])->save();
    }

    private function forum(): void
    {
        $patient = User::where('email', 'patient@globalhealthcare.test')->first();

        $questions = [
            ['dermatologists', 'acne', 'Acne keeps coming back on my jawline after I stop treatment', 'I\'m 26 and have had acne on my jawline and chin for about two years. It clears up with the cream my GP gave me but comes back within weeks of stopping. Is this hormonal? Should I get any tests done?', 'female', 26, 'sana-mirza'],
            ['psychiatrists', 'anxiety', 'Heart racing at night for no reason, is this anxiety?', 'For the past month my heart starts racing when I lie down to sleep. My ECG was normal. I do have a lot of stress at work. Could this be anxiety and what can I do about it?', 'male', 31, 'hamza-qureshi'],
            ['gynecologists-obstetricians', 'pcos', 'Diagnosed with PCOS, do I have to take the pill?', 'I was recently diagnosed with PCOS after an ultrasound. My periods come every 2 to 3 months. The doctor suggested birth control pills but I would prefer to avoid them if possible. What other options are there?', 'female', 23, 'amna-javed'],
            ['pediatricians', 'fever', 'My 2-year-old gets fever every time she starts nursery', 'Every few weeks my daughter gets a fever of around 101°F with a runny nose. It settles in 3 days. Is this normal for children in nursery, and when should I worry?', 'female', 30, 'kamran-baig'],
            ['dietitians-clinical-nutritionists', 'weight-loss-gain', 'Can I lose weight without giving up roti and rice completely?', 'I\'ve tried keto twice and gave up both times. I want something sustainable for a Pakistani household. Is it possible to lose weight while still eating roti and rice?', 'female', 35, 'rabia-anwar'],
            ['speech-occupational-therapists', 'speech-delay', 'My son is 2.5 and only says around 10 words', 'He understands everything we say and follows instructions, but only says about 10 words. Family says boys talk late. Should we wait or see a speech therapist now?', 'female', 29, 'nida-shah'],
        ];

        foreach ($questions as [$specSlug, $condSlug, $title, $body, $gender, $age, $doctorSlugStart]) {
            $q = Question::query()->firstOrNew(['slug' => Str::slug($title)]);
            if ($q->exists) {
                continue;
            }
            $q->fill([
                'specialty_id' => Specialty::where('slug', $specSlug)->value('id'),
                'title' => $title,
                'body' => $body,
                'asker_gender' => $gender,
                'asker_age' => $age,
            ]);
            $q->forceFill([
                'patient_id' => $patient->id,
                'status' => ModerationStatus::Approved,
                'published_at' => now()->subDays(rand(1, 20)),
                'views' => rand(40, 900),
            ])->save();
            $q->conditions()->sync(Condition::where('slug', $condSlug)->pluck('id'));

            $doctor = PractitionerProfile::where('slug', 'like', '%'.$doctorSlugStart.'%')->first();
            if ($doctor) {
                $answer = new Answer(['body' => $this->answerFor($condSlug)]);
                $answer->forceFill(['question_id' => $q->id, 'practitioner_profile_id' => $doctor->id, 'status' => ModerationStatus::Approved])->save();
                $q->forceFill(['answers_count' => 1])->save();
            }
        }
    }

    private function answerFor(string $condition): string
    {
        return match ($condition) {
            'acne' => '<p>Acne along the jawline in your twenties is very often hormonal, and it\'s common for it to return once a topical cream is stopped, because the cream controls acne rather than cures it.</p><p>It is worth checking whether your periods are regular and whether you have extra facial hair. If so, ask about tests for PCOS. A maintenance routine (for example adapalene a few nights a week) usually works better than stopping completely.</p><p>If you\'d like, I can review your routine and history in a consultation.</p>',
            'anxiety' => '<p>A normal ECG is reassuring. A racing heart that starts when you lie down, during a stressful period, is a very common way anxiety shows up. When the day goes quiet, the body finally notices the stress it has been carrying.</p><p>Things that help: a regular wind-down hour without screens, cutting caffeine after 2pm, and slow breathing (in for 4, out for 6) when it starts. If it persists or affects your sleep most nights, short-term therapy or medication can make a big difference.</p>',
            'pcos' => '<p>The pill is one option, not the only one. It regulates bleeding and protects the womb lining, but it doesn\'t treat the underlying insulin resistance.</p><p>Alternatives include lifestyle changes (even 5% weight loss improves cycles), metformin, inositol supplements, and progesterone tablets every few months to ensure a regular bleed. The right choice depends on your goals, for example whether you are planning pregnancy soon.</p>',
            'fever' => '<p>This is very common in the first year of nursery. Young children catch 8 to 12 viral infections a year while their immune system builds up.</p><p>Seek care urgently if she is unusually drowsy, has difficulty breathing, a rash that doesn\'t fade when pressed with a glass, signs of dehydration, or a fever lasting more than 5 days.</p>',
            'weight-loss-gain' => '<p>Absolutely. Restrictive diets fail for most people precisely because they don\'t fit family meals.</p><p>Start with portion swaps: one roti instead of two, half a plate of salad or sabzi, protein at every meal (daal, chicken, eggs, yoghurt), and limit fried snacks and sugary chai. Slow, steady loss of 0.5 to 1 kg a week is the kind that stays off.</p>',
            'speech-delay' => '<p>Good understanding is a positive sign, but by 2 to 2.5 years we usually expect 50 or more words and some two-word phrases.</p><p>I would not wait. An assessment now is low-risk, and early support, often play-based and coaching parents, gives the best results. A hearing test is also a sensible first step.</p>',
            default => '<p>Thank you for your question. This would be best discussed in a consultation where I can take a full history.</p>',
        };
    }
}
