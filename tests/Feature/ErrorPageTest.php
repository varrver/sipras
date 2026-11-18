<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_forbidden_page_is_localised_and_points_at_the_visitors_own_area(): void
    {
        $siswa = User::factory()->create();

        $this->actingAs($siswa)
            ->get('/admin/dashboard')
            ->assertForbidden()
            ->assertSee('Tidak punya akses')
            ->assertSee(route('siswa.dashboard'))
            // the stock English Laravel page must be gone
            ->assertDontSee('Forbidden');
    }

    public function test_every_error_page_names_itself_in_the_title(): void
    {
        // WCAG 2.4.2: the guest shell used to hardcode the app name, so all four
        // error pages (and the auth pages) shared one uninformative title.
        $siswa = User::factory()->create();

        Route::get('/_test/expired', fn () => abort(419));

        config(['app.debug' => false]);
        Route::get('/_test/boom', fn () => throw new \RuntimeException('boom'));

        $pages = [
            ['/admin/dashboard', 'Tidak punya akses', 403, $siswa],
            ['/halaman-yang-tidak-ada', 'Halaman tidak ditemukan', 404, null],
            ['/_test/expired', 'Sesi Anda berakhir', 419, null],
            ['/_test/boom', 'Ada yang tidak beres di sisi kami', 500, null],
        ];

        foreach ($pages as [$url, $title, $status, $user]) {
            $request = $user ? $this->actingAs($user) : $this;

            $request->get($url)
                ->assertStatus($status)
                ->assertSee("<title>{$title} — SIPRAS</title>", false);
        }
    }

    public function test_the_error_card_refuses_to_render_a_page_that_does_not_name_itself(): void
    {
        // An omitted and an explicitly blank prop are the same defect: an error page
        // whose heading says nothing. `@props` leaves an unpassed prop undefined, so
        // without the guard the omission surfaces as a bare "Undefined variable
        // $title" — and the blank one renders an empty heading in silence.
        $cases = [
            ['/_test/card-untitled', 'testing.card-untitled', '<x-error-card code="404" />', 'rendered x-error-card without title and message'],
            ['/_test/card-blank', 'testing.card-blank', '<x-error-card code="404" title="" message="Ada masalah." />', 'rendered x-error-card without title'],
        ];

        $this->withoutExceptionHandling();

        foreach ($cases as [$url, $route, $markup, $fragment]) {
            Route::get($url, fn () => Blade::render($markup))->name($route);

            $this->assertFailsLoudly($url, "[{$route}]", $fragment);
        }
    }

    public function test_guests_are_sent_to_the_sign_in_form_instead_of_the_error_page(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_not_found_page_is_localised(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('SIPRAS')
            ->assertDontSee('Not Found');
    }

    public function test_expired_session_page_is_localised(): void
    {
        Route::get('/_test/expired', fn () => abort(419));

        $this->get('/_test/expired')
            ->assertStatus(419)
            ->assertSee('Sesi Anda berakhir');
    }

    public function test_server_error_page_names_a_recovery_without_leaking_internals(): void
    {
        // Production behaviour: debug off, so a 500 renders our page, not a stack trace.
        config(['app.debug' => false]);

        Route::get('/_test/boom', function () {
            throw new \RuntimeException('internal detail that must not leak');
        });

        // A guest can land on a 500 on a public page, so the guest recovery
        // branch (sign in, not "back to dashboard") is covered here.
        $this->get('/_test/boom')
            ->assertStatus(500)
            ->assertSee('Ada yang tidak beres di sisi kami')
            ->assertSee('admin sekolah')
            ->assertSee(route('login'))
            ->assertDontSee('internal detail that must not leak');
    }

    /**
     * Unmatched addresses have no route, so their 404 used to fall out of the
     * router into the exception handler, which renders the card OUTSIDE the web
     * middleware group: no session ever started, and every signed-in visitor was
     * offered "Masuk ke SIPRAS" instead of their own dashboard. The fallback
     * route in routes/web.php claims the address from inside the web group, so
     * x-error-card's recovery CTA is role-aware.
     *
     * In-process requests share the auth guard, so `actingAs` pins the user for
     * the whole test and the CTA assertion alone cannot distinguish "session
     * started" from "guard happened to be logged in" — exactly why the earlier
     * admin-only variant passed while the bug shipped live. The session cookie
     * is what actually proves StartSession ran for the 404: only the web
     * middleware stack sets it, and without the fallback no middleware ran for
     * unmatched addresses at all.
     */
    public function test_the_fallback_404_is_session_aware_for_every_role(): void
    {
        $cases = [
            [null, 'Masuk ke SIPRAS', route('login')],
            [User::factory()->create(), 'Ke Dashboard', route('siswa.dashboard')],
            [User::factory()->admin()->create(), 'Ke Dashboard', route('admin.dashboard')],
            // Teknisi has no dashboard: its home is the worklist, and the student
            // dashboard would refuse it with a second 403.
            [User::factory()->create(['role' => 'teknisi']), 'Ke Antrean', route('teknisi.pengaduan.index')],
        ];

        foreach ($cases as [$user, $cta, $href]) {
            $request = $user ? $this->actingAs($user) : $this;

            $request->get('/alamat-tidak-ada')
                ->assertNotFound()
                ->assertSee($cta)
                ->assertSee($href)
                ->assertDontSee($user ? 'Masuk ke SIPRAS' : 'Ke Dashboard')
                ->assertCookie(config('session.cookie'));
        }
    }

    public function test_json_clients_keep_the_framework_json_404_from_the_fallback(): void
    {
        // shouldRenderJsonWhen (bootstrap/app.php) hands JSON clients a JSON
        // body; the fallback has to keep that contract instead of serving the
        // HTML card, so an API-style caller stays machine-readable.
        $this->getJson('/alamat-tidak-ada')
            ->assertNotFound()
            ->assertJson(['message' => 'The route alamat-tidak-ada could not be found.']);
    }

    public function test_state_changing_requests_to_unknown_addresses_stay_404(): void
    {
        // RegistrationTest posts to /register — a URL that deliberately does not
        // exist — and asserts a 404, so the fallback has to answer unknown
        // addresses for state-changing verbs too, not just navigation: a
        // GET-only fallback would turn them into a 405 via the router's
        // alternate-verb check, and letting CSRF run on them would report a 419
        // for a session problem that does not exist. The fallback route itself
        // is what carries both guarantees, so it is pinned here as well.
        $fallback = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route) => $route->isFallback);

        $this->assertNotNull($fallback, 'No fallback route is registered.');
        $this->assertContains(PreventRequestForgery::class, $fallback->excludedMiddleware());

        $this->post('/alamat-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan');
    }
}
