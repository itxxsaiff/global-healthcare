<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Condition;
use App\Models\Country;
use App\Models\Language;
use App\Models\Specialty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Idempotent: safe to run on production repeatedly.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->countries();
        $this->languages();
        $this->specialties();
        $this->conditions();
    }

    private function countries(): void
    {
        // Target countries from the client document, in its order.
        $countries = [
            ['PK', 'Pakistan', 'PKR', 'Asia/Karachi', ['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta', 'Hyderabad', 'Sialkot', 'Gujranwala', 'Abbottabad']],
            ['AE', 'United Arab Emirates', 'AED', 'Asia/Dubai', ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman']],
            ['SA', 'Saudi Arabia', 'SAR', 'Asia/Riyadh', ['Riyadh', 'Jeddah', 'Dammam', 'Makkah', 'Madinah']],
            ['GB', 'United Kingdom', 'GBP', 'Europe/London', ['London', 'Birmingham', 'Manchester', 'Bradford', 'Glasgow']],
            ['US', 'United States', 'USD', 'America/New_York', ['New York', 'Houston', 'Chicago', 'Los Angeles', 'Dallas']],
            ['CA', 'Canada', 'CAD', 'America/Toronto', ['Toronto', 'Mississauga', 'Vancouver', 'Calgary', 'Montreal']],
            ['QA', 'Qatar', 'QAR', 'Asia/Qatar', ['Doha']],
            ['OM', 'Oman', 'OMR', 'Asia/Muscat', ['Muscat', 'Salalah']],
            ['KW', 'Kuwait', 'KWD', 'Asia/Kuwait', ['Kuwait City']],
            ['ZZ', 'Other countries', 'USD', 'UTC', []],
        ];

        foreach ($countries as $i => [$iso, $name, $currency, $tz, $cities]) {
            $country = Country::query()->updateOrCreate(['iso2' => $iso], [
                'name' => $name,
                'slug' => Str::slug($name),
                'currency' => $currency,
                'default_timezone' => $tz,
                'is_target' => true,
                'sort' => $i,
            ]);

            foreach ($cities as $city) {
                City::query()->updateOrCreate(
                    ['country_id' => $country->id, 'slug' => Str::slug($city)],
                    ['name' => $city, 'is_active' => true],
                );
            }
        }
    }

    private function languages(): void
    {
        // Preset list from the client document; "Other" is a free-text field on the profile.
        $languages = ['ur' => 'Urdu', 'en' => 'English', 'ar' => 'Arabic', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish', 'hi' => 'Hindi', 'tr' => 'Turkish'];

        $i = 0;
        foreach ($languages as $code => $name) {
            Language::query()->updateOrCreate(['code' => $code], ['name' => $name, 'is_preset' => true, 'sort' => $i++]);
        }
    }

    private function specialties(): void
    {
        // The 14 allowed specialties exactly as listed in the client document.
        // Sub-specialties are a starting set; admin can edit them.
        $tree = [
            ['Dermatologists', 'Dermatologist', 'Skin, hair and nail care, from acne to eczema.', 'droplet',
                ['Acne & Acne Scars', 'Hair & Scalp Disorders', 'Pigmentation & Melasma', 'Eczema & Psoriasis', 'Cosmetic Dermatology']],
            ['Endocrinologists', 'Endocrinologist', 'Hormones, thyroid, diabetes and metabolism.', 'activity',
                ['Diabetes Management', 'Thyroid Disorders', 'Hormonal Imbalance', 'PCOS & Insulin Resistance', 'Obesity & Metabolism']],
            ['Gastroenterologists', 'Gastroenterologist', 'Stomach, gut and liver health.', 'circle-dot',
                ['Acid Reflux & GERD', 'Irritable Bowel Syndrome', 'Liver & Hepatitis', 'Chronic Constipation']],
            ['Psychiatrists', 'Psychiatrist', 'Medical care for mental health, including medication.', 'brain',
                ['Depression', 'Anxiety Disorders', 'Bipolar Disorder', 'OCD', 'ADHD', 'Sleep Disorders', 'Addiction']],
            ['Urologists', 'Urologist', 'Kidney, bladder and male reproductive health.', 'shield',
                ['Kidney Stones', 'Prostate Health', 'Urinary Tract Infections', 'Male Infertility']],
            ['Clinical Psychologists & Counselors', 'Clinical Psychologist', 'Talk therapy and counselling, including sexology, neuro-psychology and family counselling.', 'message-circle',
                ['Cognitive Behavioural Therapy (CBT)', 'Relationship & Family Counselling', 'Neuro-Psychology', 'Sex & Intimacy Counselling', 'Trauma & Grief', 'Child & Adolescent Therapy']],
            ['General Physicians', 'General Physician', 'GPs and internal medicine for everyday health concerns.', 'stethoscope',
                ['Internal Medicine', 'Fever & Infections', 'Blood Pressure', 'Preventive Health Check', 'Medical Second Opinion']],
            ['Gynecologists / Obstetricians', 'Gynecologist', 'Women\'s health, pregnancy and fertility.', 'heart',
                ['Pregnancy Care', 'PCOS Care', 'Menstrual Problems', 'Infertility', 'Menopause']],
            ['Pediatricians', 'Pediatrician', 'Child specialists for babies, children and teens.', 'baby',
                ['Newborn Care', 'Child Nutrition & Growth', 'Vaccination Advice', 'Childhood Infections']],
            ['Neurologists', 'Neurologist', 'Brain, nerves, migraines and seizures.', 'zap',
                ['Migraine & Headache', 'Epilepsy & Seizures', 'Stroke Recovery', 'Nerve Pain & Neuropathy']],
            ['General Surgeons', 'General Surgeon', 'Online pre-op and post-op consultations and second opinions (non-emergency).', 'scissors',
                ['Pre-operative Consultation', 'Post-operative Follow-up', 'Surgical Second Opinion', 'Hernia & Gallbladder']],
            ['Dietitians & Clinical Nutritionists', 'Dietitian', 'Nutrition plans, weight management and lifestyle coaching.', 'leaf',
                ['Weight Management', 'Gut Health', 'PCOS Nutrition', 'Diabetic Diet Planning', 'Lifestyle & Wellness Coaching']],
            ['Sexologists', 'Sexologist', 'Confidential help with sexual health and wellbeing.', 'lock',
                ['Erectile Dysfunction', 'Premature Ejaculation', 'Low Libido', 'Sexual Wellness Counselling']],
            ['Speech & Occupational Therapists', 'Speech Therapist', 'Speech, language and everyday-skills therapy for children and adults.', 'mic',
                ['Speech Delay', 'Stuttering', 'Autism Support', 'Sensory Processing', 'Fine Motor Skills']],
        ];

        foreach ($tree as $i => [$name, $label, $summary, $icon, $children]) {
            $parent = Specialty::query()->updateOrCreate(['slug' => Str::slug(str_replace('/', ' ', $name))], [
                'parent_id' => null,
                'name' => $name,
                'practitioner_label' => $label,
                'summary' => $summary,
                'icon' => $icon,
                'sort' => $i,
                'is_active' => true,
            ]);

            foreach ($children as $j => $child) {
                $slug = Str::slug($child);
                $existing = Specialty::query()->where('slug', $slug)->first();
                if ($existing && $existing->parent_id !== $parent->id) {
                    $slug .= '-'.Str::slug($label);
                }

                Specialty::query()->updateOrCreate(['slug' => $slug], [
                    'parent_id' => $parent->id,
                    'name' => $child,
                    'sort' => $j,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function conditions(): void
    {
        // Symptom / disease tags for patient-centric search. The first block is from the client document.
        // Each maps to the specialties (by slug) that treat it.
        $map = [
            'Acne' => ['dermatologists'],
            'Hair Loss' => ['dermatologists', 'endocrinologists'],
            'Diabetes' => ['endocrinologists', 'general-physicians', 'dietitians-clinical-nutritionists'],
            'Anxiety' => ['psychiatrists', 'clinical-psychologists-counselors'],
            'Depression' => ['psychiatrists', 'clinical-psychologists-counselors'],
            'PCOS' => ['gynecologists-obstetricians', 'endocrinologists', 'dietitians-clinical-nutritionists'],
            'Stomach Pain & Acid Reflux' => ['gastroenterologists', 'general-physicians'],
            'Migraine & Headache' => ['neurologists', 'general-physicians'],
            'Erectile Dysfunction' => ['urologists', 'sexologists'],
            'Speech Delay' => ['speech-occupational-therapists', 'pediatricians'],
            'Fever' => ['general-physicians', 'pediatricians'],
            'Weight Loss & Gain' => ['dietitians-clinical-nutritionists', 'endocrinologists'],

            'Thyroid Problems' => ['endocrinologists'],
            'Eczema' => ['dermatologists'],
            'Pigmentation' => ['dermatologists'],
            'Insomnia' => ['psychiatrists', 'clinical-psychologists-counselors'],
            'OCD' => ['psychiatrists', 'clinical-psychologists-counselors'],
            'Stress & Burnout' => ['clinical-psychologists-counselors'],
            'Relationship Problems' => ['clinical-psychologists-counselors'],
            'ADHD' => ['psychiatrists', 'pediatricians'],
            'Irregular Periods' => ['gynecologists-obstetricians'],
            'Pregnancy' => ['gynecologists-obstetricians'],
            'Infertility' => ['gynecologists-obstetricians', 'urologists'],
            'IBS' => ['gastroenterologists', 'dietitians-clinical-nutritionists'],
            'Fatty Liver' => ['gastroenterologists', 'dietitians-clinical-nutritionists'],
            'Kidney Stones' => ['urologists'],
            'Urine Infection' => ['urologists', 'general-physicians'],
            'High Blood Pressure' => ['general-physicians'],
            'Epilepsy' => ['neurologists'],
            'Low Libido' => ['sexologists'],
            'Autism' => ['speech-occupational-therapists', 'pediatricians'],
            'Stuttering' => ['speech-occupational-therapists'],
            'Hernia' => ['general-surgeons'],
            'Child Growth Concerns' => ['pediatricians', 'dietitians-clinical-nutritionists'],
        ];

        $featured = ['Acne', 'Hair Loss', 'Diabetes', 'Anxiety', 'Depression', 'PCOS', 'Stomach Pain & Acid Reflux', 'Migraine & Headache', 'Erectile Dysfunction', 'Speech Delay', 'Fever', 'Weight Loss & Gain'];

        $specialtyIds = Specialty::query()->whereNull('parent_id')->pluck('id', 'slug');

        foreach ($map as $name => $slugs) {
            $condition = Condition::query()->updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'is_featured' => in_array($name, $featured, true),
                'is_active' => true,
            ]);

            $condition->specialties()->syncWithoutDetaching(
                collect($slugs)->map(fn ($s) => $specialtyIds[$s] ?? throw new \RuntimeException("Unknown specialty {$s}"))->all()
            );
        }
    }
}
