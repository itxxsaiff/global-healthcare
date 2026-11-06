<?php

namespace Tests\Feature\Auth;

use App\Models\PractitionerProfile;
use App\Models\User;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    public function test_dashboard_redirects_each_role_to_its_own_area(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect(route('patient.dashboard'));
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertRedirect(route('admin.dashboard'));

        $doctor = PractitionerProfile::factory()->create()->user;
        $this->actingAs($doctor)->get('/dashboard')->assertRedirect(route('practitioner.dashboard'));
    }

    public function test_roles_cannot_enter_each_others_areas(): void
    {
        $patient = User::factory()->create();
        $this->actingAs($patient)->get('/admin')->assertForbidden();
        $this->actingAs($patient)->get('/practitioner')->assertForbidden();

        $doctor = PractitionerProfile::factory()->create()->user;
        $this->actingAs($doctor)->get('/admin')->assertForbidden();
        $this->actingAs($doctor)->get('/patient')->assertForbidden();
    }

    public function test_each_dashboard_renders_for_its_role(): void
    {
        $this->actingAs(User::factory()->create())->get('/patient')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
        $this->actingAs(PractitionerProfile::factory()->create()->user)->get('/practitioner')->assertOk();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/patient')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_unverified_email_cannot_reach_dashboards(): void
    {
        $this->actingAs(User::factory()->unverified()->create())->get('/patient')->assertRedirect(route('verification.notice'));
    }

    public function test_suspended_user_is_logged_out(): void
    {
        $this->actingAs(User::factory()->suspended()->create())->get('/patient')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_authenticated_pages_are_never_cached(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/patient');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
