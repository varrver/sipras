<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_screen_is_the_landing_page(): void
    {
        // There is no separate login view any more: the form lives on `/`, and
        // GET /login is a bookmark redirect to it. Pinned here as well as in
        // LandingTest because this is the route the redirect's name points at.
        $this->get('/login')->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('name="password"', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();

        // The card must read as Indonesian copy, not the framework key
        // "auth.failed" that printed before lang/id/auth.php existed.
        $response->assertSessionHasErrors([
            'email' => 'Email atau kata sandi tidak cocok dengan data kami.',
        ]);
    }

    public function test_the_sign_in_fields_speak_indonesian(): void
    {
        $this->from('/')->post('/login', ['email' => '', 'password' => ''])->assertSessionHasErrors([
            'email' => 'Email wajib diisi.',
            'password' => 'Kata sandi wajib diisi.',
        ]);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
