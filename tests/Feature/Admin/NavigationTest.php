<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Extract the numeric z-index from a class list (`z-40` → 40).
     */
    private function zIndex(string $classes): ?int
    {
        foreach (preg_split('/\s+/', $classes) as $class) {
            if (preg_match('/^z-(\d+)$/', $class, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    public function test_the_mobile_drawer_is_a_named_dialog_that_stacks_above_its_scrim(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $drawer = $xpath->query('//*[@id="admin-drawer"]');
        $this->assertSame(1, $drawer->length);

        $drawer = $drawer->item(0);

        // A `<div>`, not an `<aside>`: the implicit `complementary` role of an
        // aside conflicts with `dialog` (axe: aria-allowed-role).
        $this->assertSame('dialog', $drawer->getAttribute('role'));
        $this->assertSame('true', $drawer->getAttribute('aria-modal'));
        $this->assertNotSame('', trim($drawer->getAttribute('aria-label')));

        // The drawer must out-rank its scrim by its *own* declared z-index. The
        // delete-account dialog relied on Tailwind 3's `.transform` for that
        // stacking context; in Tailwind 4 `.transform` is a no-op, and the panel
        // painted below the fixed scrim, so every click inside it hit the scrim.
        // Any element: the scrim is a <label> for the drawer's checkbox so the
        // tap-to-dismiss path survives without Alpine (the no-JS drawer leg).
        $scrim = $xpath->query('//*[contains(@class, "bg-scrim")]');
        $this->assertSame(1, $scrim->length);

        $drawerZ = $this->zIndex($drawer->getAttribute('class'));
        $scrimZ = $this->zIndex($scrim->item(0)->getAttribute('class'));

        $this->assertNotNull($drawerZ, 'the drawer must declare a z-index');
        $this->assertNotNull($scrimZ, 'the scrim must declare a z-index');
        $this->assertGreaterThan($scrimZ, $drawerZ);
    }

    public function test_the_mobile_drawer_can_be_opened_and_closed_without_script(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // One hidden checkbox is the scriptless drawer switch: it must be a real
        // checkbox (Space toggles it), present only below `lg` where the drawer
        // lives, and it must control the drawer by id.
        $switch = $xpath->query('//*[@id="admin-nav-check"]');
        $this->assertSame(1, $switch->length);
        $switch = $switch->item(0);

        $this->assertSame('checkbox', $switch->getAttribute('type'));
        $this->assertStringContainsString('sr-only', $switch->getAttribute('class'));
        $this->assertStringContainsString('lg:hidden', $switch->getAttribute('class'));
        $this->assertSame('admin-drawer', $switch->getAttribute('aria-controls'));

        // All three controls are labels for it — the top-bar hamburger, the
        // scrim and the drawer's close — so clicking any of them toggles the
        // drawer through CSS with no Alpine running (`app.css` carries the
        // `:checked` rules). A button here would have silently dropped the
        // scriptless leg.
        $labels = $xpath->query('//label[@for="admin-nav-check"]');
        $this->assertSame(3, $labels->length);
    }

    public function test_the_mobile_drawer_restores_focus_only_when_it_was_open(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $drawer = $xpath->query('//*[@id="admin-drawer"]')->item(0);
        $escape = $drawer->getAttribute('@keydown.escape.window');

        // The hamburger-focus restore is a nicety for closing the drawer with
        // Escape. It must not fire for every page-wide Escape: when a dialog
        // (x-modal) is open, restoring to the toggle would steal focus from
        // the modal's own opener-restore. Guard the whole restore on the
        // drawer's own state.
        $this->assertStringContainsString('if (open)', $escape, 'the drawer escape restore must be guarded by its own state');
    }
}
