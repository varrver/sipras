<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public entry: guests read the combined intro + login screen at `/`;
 * GET /login redirects to `/`; a signed-in visitor bounces to their dashboard;
 * registration stays closed (no Daftar anywhere on the page).
 */
class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_introduction(): void
    {
        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('SIPRAS adalah sistem pengaduan sarana SMK Negeri 5 Surakarta', $content);
        $this->assertStringContainsString('Cara kerjanya', $content);
        $this->assertStringContainsString('SMK Negeri 5 Surakarta', $content);
    }

    public function test_guests_see_the_login_form_on_the_landing(): void
    {
        $content = $this->get('/')->assertOk()->getContent();

        // The form posts to the login route.
        $this->assertStringContainsString('action="'.route('login').'"', $content);

        // The email and password fields are present.
        $this->assertStringContainsString('name="email"', $content);
        $this->assertStringContainsString('name="password"', $content);

        // Masuk submit button.
        $this->assertStringContainsString('Masuk', $content);

        // Closed entry rules: no Daftar, no Lupa sandi hint.
        $this->assertStringNotContainsString('Daftar', $content);
        $this->assertStringNotContainsString('/register', $content);
        $this->assertStringNotContainsString('Lupa', $content);
    }

    public function test_get_login_redirects_to_the_landing(): void
    {
        // GET /login is a bookmark redirect — the merged page is at /.
        $this->get(route('login'))->assertRedirect('/');
    }

    public function test_signed_in_users_bounce_to_their_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));

        $this->actingAs(User::factory()->create(['role' => 'siswa']))
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }
}
