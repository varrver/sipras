<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_the_landing_to_guests(): void
    {
        // The public entry is now the introduction, not a redirect: this is
        // the teacher's revision point, replacing the old login bounce.
        $this->get('/')->assertOk();
    }

    public function test_home_redirects_signed_in_users_to_their_dashboard(): void
    {
        $user = User::factory()->create();

        // The shared dashboard controller routes by role after the bounce —
        // the Location is /dashboard itself, not the role URL.
        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_dashboard_redirects_admin_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin/dashboard');
    }

    public function test_dashboard_redirects_siswa_to_siswa_dashboard(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->get('/dashboard')->assertRedirect('/siswa/dashboard');
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_siswa_cannot_access_admin_dashboard(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_siswa_can_access_siswa_dashboard(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)->get('/siswa/dashboard')->assertOk();
    }

    public function test_admin_cannot_access_siswa_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/siswa/dashboard')->assertForbidden();
    }

    public function test_guest_cannot_access_protected_dashboards(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }
}
