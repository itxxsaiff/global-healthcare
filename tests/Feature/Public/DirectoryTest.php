<?php

namespace Tests\Feature\Public;

use App\Models\Language;
use App\Models\PractitionerProfile;
use App\Models\Specialty;
use App\Models\User;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    public function test_public_pages_render(): void
    {
        PractitionerProfile::factory()->live()->create();

        foreach (['/', '/find-a-doctor', '/dermatologists', '/dermatologists/lahore', '/symptom/acne', '/ask-a-doctor', '/blog', '/for-practitioners', '/legal/terms'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_only_public_practitioners_are_listed(): void
    {
        $live = PractitionerProfile::factory()->live()->create();
        $draft = PractitionerProfile::factory()->create();
        $approvedNoPlan = PractitionerProfile::factory()->approved()->create();

        $this->get('/find-a-doctor')
            ->assertSee($live->user->name)
            ->assertDontSee($draft->user->name)
            ->assertDontSee($approvedNoPlan->user->name);
    }

    public function test_hidden_profile_404s_for_public_but_previews_for_owner(): void
    {
        $draft = PractitionerProfile::factory()->create();

        $this->get('/doctor/'.$draft->slug)->assertNotFound();
        $this->actingAs($draft->user)->get('/doctor/'.$draft->slug)->assertOk()->assertSee('Preview');
        $this->actingAs(User::factory()->admin()->create())->get('/doctor/'.$draft->slug)->assertOk();
    }

    public function test_gender_and_language_filters(): void
    {
        $urdu = Language::where('code', 'ur')->first();
        $arabic = Language::where('code', 'ar')->first();

        $female = PractitionerProfile::factory()->live()->create(['gender' => 'female']);
        $female->languages()->attach($urdu);
        $male = PractitionerProfile::factory()->live()->create(['gender' => 'male']);
        $male->languages()->attach($arabic);

        $this->get('/find-a-doctor?gender=female')->assertSee($female->user->name)->assertDontSee($male->user->name);
        $this->get('/find-a-doctor?languages[]=ar')->assertSee($male->user->name)->assertDontSee($female->user->name);
    }

    public function test_symptom_search_finds_specialists_who_treat_it(): void
    {
        $derm = Specialty::where('slug', 'dermatologists')->first();
        $neuro = Specialty::where('slug', 'neurologists')->first();
        $dermatologist = PractitionerProfile::factory()->live()->create(['primary_specialty_id' => $derm->id]);
        $neurologist = PractitionerProfile::factory()->live()->create(['primary_specialty_id' => $neuro->id]);

        $this->get('/symptom/acne')->assertSee($dermatologist->user->name)->assertDontSee($neurologist->user->name);
    }

    public function test_fee_filter_applies_within_currency(): void
    {
        $cheap = PractitionerProfile::factory()->live()->create(['fee_minor' => 150000]);
        $pricey = PractitionerProfile::factory()->live()->create(['fee_minor' => 900000]);

        $this->get('/find-a-doctor?currency=PKR&fee_max=2000')->assertSee($cheap->user->name)->assertDontSee($pricey->user->name);
    }

    public function test_suggest_endpoint_returns_symptoms_and_specialties(): void
    {
        $this->getJson('/search/suggest?q=acn')->assertOk()->assertJsonPath('groups.0.items.0.label', 'Acne');
    }

    public function test_linkedin_hidden_on_free_plan_and_shown_on_paid(): void
    {
        $free = PractitionerProfile::factory()->live('pk_free')->create(['linkedin_url' => 'https://linkedin.com/in/free-doc']);
        $paid = PractitionerProfile::factory()->live('pk_pro_1m')->create(['linkedin_url' => 'https://linkedin.com/in/paid-doc']);

        $this->get('/doctor/'.$free->slug)->assertDontSee('linkedin.com/in/free-doc');
        $this->get('/doctor/'.$paid->slug)->assertSee('linkedin.com/in/paid-doc');
    }
}
