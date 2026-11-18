<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_the_delete_account_dialog_sits_above_its_scrim_and_is_a_named_dialog(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile')->assertOk();

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $dialog = $xpath->query('//*[@role="dialog"]');

        $this->assertSame(1, $dialog->length);

        $panel = $dialog->item(0);
        $classes = $panel->getAttribute('class');

        // The scrim is `fixed`, so it paints in the positioned layer. A static
        // panel paints below it and every click inside the dialog hits the
        // scrim instead — which closes it. Tailwind 3's `transform` used to
        // supply that stacking context; Tailwind 4's is a no-op.
        $this->assertStringContainsString('relative', $classes);
        $this->assertStringContainsString('z-10', $classes);

        // x-trap only traps Tab; `aria-modal` is what makes the page behind inert.
        $this->assertSame('true', $panel->getAttribute('aria-modal'));

        $labelId = $panel->getAttribute('aria-labelledby');
        $this->assertNotSame('', $labelId);

        $title = $xpath->query('//*[@id="'.$labelId.'"]');
        $this->assertSame(1, $title->length, "aria-labelledby must resolve: {$labelId}");
        $this->assertSame('Yakin ingin menghapus akun Anda?', trim($title->item(0)->textContent));
    }

    public function test_the_modal_refuses_to_render_without_a_name(): void
    {
        // `name` is the dialog's label *and* its event contract: it feeds both
        // `aria-labelledby` and the `open-modal` listener. Omitted, the component
        // dies on a bare "Undefined variable $name"; blank, it renders
        // `aria-labelledby="-title"` — a dialog naming nothing. (That the
        // reference *resolves* is asserted on the real page above.)
        $cases = [
            ['/_test/modal-unnamed', 'testing.modal-unnamed', '<x-modal />'],
            ['/_test/modal-blank', 'testing.modal-blank', '<x-modal name="" />'],
        ];

        $this->withoutExceptionHandling();

        foreach ($cases as [$url, $route, $markup]) {
            Route::get($url, fn () => Blade::render($markup))->name($route);

            $this->assertFailsLoudly($url, "[{$route}]", 'rendered x-modal without a name');
        }
    }

    public function test_the_modal_contract_focuses_in_when_opened_and_restores_on_close(): void
    {
        // Two contracts live in the shared component:
        //  1. `focusable` moves focus INTO the dialog when it opens — the
        //     switch compiles to the focus call its consumers assert below.
        //  2. Every dialog remembers the element that opened it, so dismissal
        //     (confirm, cancel or Escape) hands focus back instead of dropping
        //     it on the page root. Needles avoid quotes: they are matched
        //     against attribute values Blade has HTML-escaped.
        Route::get('/_test/modal-focusable', fn () => Blade::render(
            '<x-modal name="testing.modal-focusable" focusable><p>Isi modal.</p></x-modal>'
        ))->name('testing.modal-focusable');
        Route::get('/_test/modal-plain', fn () => Blade::render(
            '<x-modal name="testing.modal-plain"><p>Isi modal biasa.</p></x-modal>'
        ))->name('testing.modal-plain');

        $focusCall = 'setTimeout(() =&gt; firstFocusable().focus(), 100)';

        $this->get('/_test/modal-focusable')
            ->assertOk()
            ->assertSee($focusCall, false);

        $this->get('/_test/modal-plain')
            ->assertOk()
            ->assertDontSee($focusCall, false)
            ->assertSee('opener = document.activeElement', false)
            ->assertSee('opener?.focus()', false);
    }

    public function test_the_modal_neutralizes_inherited_text_alignment_and_wrapping(): void
    {
        // The shared dialog mounts wherever its consumers live — including an
        // action cell like `<td class="whitespace-nowrap text-right …">` on the
        // admin master tables. `white-space` and `text-align` are inherited
        // properties, so a dialog without its own rule inherits nowrap/right
        // from its host: its copy refuses to wrap and is clipped by the
        // panel's `overflow-hidden`, and its heading renders right-aligned —
        // the dialog reads as displaced even though its geometry is centered.
        // The panel therefore carries the normalization itself, so its text
        // always behaves like a dialog's no matter where it is mounted.
        Route::get('/_test/modal-in-nowrap-cell', fn () => Blade::render(
            '<td class="whitespace-nowrap text-right text-sm font-medium">'
            .'<x-modal name="testing.modal-in-nowrap-cell" maxWidth="md">'
            .'<p>Kategori :name akan dihapus permanen.</p>'
            .'</x-modal></td>'
        ))->name('testing.modal-in-nowrap-cell');

        $this->get('/_test/modal-in-nowrap-cell')
            ->assertOk()
            ->assertSee('whitespace-normal', false)
            ->assertSee('text-left', false);
    }

    public function test_the_account_dropdown_restores_focus_only_when_open(): void
    {
        // The account menu returns focus to its trigger when ESC closes it.
        // Restoring on *every* page-wide Escape would steal focus from a
        // dialog's own opener-restore (x-modal), landing on the header avatar
        // mid-flow — so the restore is guarded by the dropdown's own state.
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile')->assertOk();

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        // Candidates = any wrapper AROUND the account-open button that carries
        // the dropdown's window-level escape handler. `getAttribute` is used
        // because `@keydown…` is not a valid XPath attribute predicate name.
        $guarded = [];
        foreach ((new DOMXPath($dom))->query('//div[.//button[@aria-haspopup="true"]]') as $node) {
            $handler = $node->getAttribute('@keydown.escape.window');
            if ($handler !== '') {
                $guarded[] = $handler;
            }
        }

        $this->assertSame(1, count($guarded), 'the account dropdown must render exactly one escape handler');
        $this->assertStringContainsString('if (open)', $guarded[0], 'the dropdown escape restore must be guarded by its own state');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_deleting_your_own_account_sweeps_your_complaint_files_too(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $complaint = Complaint::factory()->create([
            'user_id' => $user->id,
            'evidence_image' => 'complaints/evidence/mine.jpg',
        ]);
        Storage::disk('public')->put($complaint->evidence_image, 'x');

        $this
            ->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        Storage::disk('public')->assertMissing($complaint->evidence_image);
        $this->assertDatabaseMissing('complaints', ['id' => $complaint->id]);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
