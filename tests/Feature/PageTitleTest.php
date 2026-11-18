<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PageTitleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every page has to name itself twice, in the same words: the <title> is the
     * page's own heading followed by the app-name suffix. WCAG 2.4.2 asks for a
     * title that describes the page, but a title is invisible to `axe`'s
     * `document-title` rule (it passes on any non-empty string), so the pair is
     * pinned here instead. A page that drops `@section('title')` silently falls
     * back to a bare "SIPRAS" in the app shell; this test names the offender.
     *
     * The four error pages are covered by ErrorPageTest.
     */
    public function test_every_page_names_itself_in_both_the_title_and_the_h1(): void
    {
        $admin = User::factory()->admin()->create();
        $siswa = User::factory()->create();
        // The prompt redirects a verified user to their dashboard, so the only
        // way to see this page is as an account that has not confirmed yet.
        $unverified = User::factory()->unverified()->create();

        $complaint = Complaint::factory()->create([
            'user_id' => $siswa->id,
            'category_id' => Category::factory()->create()->id,
            'room_id' => Room::factory()->create()->id,
        ]);

        $category = Category::factory()->create();
        $room = Room::factory()->create();

        // Guest rows first: `actingAs` persists for the rest of the test, and a
        // signed-in visitor gets redirected away from /login.
        $pages = [
            ['/', null, 'Laporkan kerusakan sarana sekolah, pantau sampai selesai.'],

            // Reachable by any signed-in user, whichever role they hold.
            [route('profile.edit'), $siswa, 'Profil'],
            [route('verification.notice'), $unverified, 'Verifikasi Email'],
            [route('password.confirm'), $siswa, 'Konfirmasi Kata Sandi'],

            [route('admin.dashboard'), $admin, 'Dashboard Admin'],
            [route('admin.pengaduan.index'), $admin, 'Manajemen Pengaduan'],
            [route('admin.pengaduan.show', $complaint), $admin, $complaint->title],
            [route('admin.pengaduan.report'), $admin, 'Rekapitulasi Pengaduan Sarana'],
            [route('admin.master.categories.index'), $admin, 'Manajemen Kategori'],
            [route('admin.master.categories.create'), $admin, 'Tambah Kategori'],
            [route('admin.master.categories.edit', $category), $admin, 'Edit Kategori'],
            [route('admin.master.rooms.index'), $admin, 'Manajemen Ruangan'],
            [route('admin.master.rooms.create'), $admin, 'Tambah Ruangan'],
            [route('admin.master.rooms.edit', $room), $admin, 'Edit Ruangan'],
            [route('admin.master.users.index'), $admin, 'Manajemen Pengguna'],
            [route('admin.master.users.create'), $admin, 'Tambah Pengguna'],
            [route('admin.master.users.edit', $siswa), $admin, 'Edit Pengguna'],

            [route('siswa.dashboard'), $siswa, 'Dashboard Siswa'],
            [route('siswa.pengaduan.index'), $siswa, 'Pengaduan Saya'],
            [route('siswa.pengaduan.create'), $siswa, 'Buat Pengaduan'],
            [route('siswa.pengaduan.show', $complaint), $siswa, $complaint->title],
        ];

        foreach ($pages as [$url, $user, $heading]) {
            $request = $user ? $this->actingAs($user) : $this;
            $response = $request->get($url)->assertOk();

            $this->assertPageNamesItself($response, $heading, $url);
        }
    }

    /**
     * The sweep above only covers the pages that exist today. These two tests are
     * the guard for the page that is added tomorrow: neither shell renders a page
     * that does not name itself, rather than quietly serving a tab that says
     * "SIPRAS". (`assertFailsLoudly`, shared with the error card, lives in
     * `Tests\TestCase`.)
     */
    public function test_the_app_shell_refuses_to_render_a_page_that_does_not_name_itself(): void
    {
        // The app shell reads the page's `@section('title')`, which only exists
        // once the child view has rendered — so its guard lives in the layout and
        // fires mid-render.
        $this->actingAs(User::factory()->admin()->create());

        Route::get('/_test/untitled', fn () => Blade::render('<x-app-layout><p>Halaman tanpa judul.</p></x-app-layout>'))
            ->name('testing.untitled');

        $this->withoutExceptionHandling();

        $this->assertFailsLoudly('/_test/untitled', '[testing.untitled]', 'rendered x-app-layout without a title');
    }

    public function test_the_guest_shell_refuses_to_render_a_page_that_does_not_name_itself(): void
    {
        // The guest shell takes its title as an attribute, so `GuestLayout` can
        // refuse it in the constructor — before a single byte of the page exists.
        Route::get('/_test/untitled-guest', fn () => Blade::render('<x-guest-layout><p>Halaman tanpa judul.</p></x-guest-layout>'))
            ->name('testing.untitled-guest');

        $this->withoutExceptionHandling();

        $this->assertFailsLoudly('/_test/untitled-guest', '[testing.untitled-guest]', 'rendered x-guest-layout without a title');
    }

    private function assertPageNamesItself(TestResponse $response, string $heading, string $url): void
    {
        $html = $response->getContent();

        // One top-level heading per page: a second one competes with the page's
        // real name and makes the pairing below ambiguous.
        $this->assertSame(
            1,
            substr_count($html, '<h1'),
            "{$url} does not have exactly one <h1>."
        );

        $this->assertSame(
            1,
            preg_match('/<h1\b[^>]*>(.*?)<\/h1>/s', $html, $matches),
            "{$url} has no <h1> to compare its <title> against."
        );

        // Pinning the heading as well as the title: a title can match the *wrong*
        // page's heading, and an audit that silently measured another page is how
        // a "the page is fine" conclusion turns out to be fiction. The h1 source
        // may wrap across blade lines (multi-sentence heroes), so collapse any
        // inner whitespace — the tab and the heading must match by MEANING.
        $this->assertSame(
            $heading,
            trim(preg_replace('/\s+/', ' ', strip_tags($matches[1]))),
            "{$url} does not render the expected <h1>."
        );

        $this->assertStringContainsString(
            "<title>{$heading} — SIPRAS</title>",
            $html,
            "{$url} does not name itself in the <title>."
        );
    }
}
