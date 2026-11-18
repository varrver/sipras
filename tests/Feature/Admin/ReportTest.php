<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function complaint(array $attributes = []): Complaint
    {
        return Complaint::factory()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'room_id' => Room::factory()->create()->id,
        ], $attributes));
    }

    public function test_admin_can_view_report_page(): void
    {
        $this->complaint(['title' => 'AC Ruang Kelas Rusak', 'status' => 'pending']);

        $this->actingAs($this->admin())
            ->get(route('admin.pengaduan.report'))
            ->assertOk()
            ->assertSee('Rekapitulasi Pengaduan')
            ->assertSee('AC Ruang Kelas Rusak');
    }

    public function test_the_document_title_is_a_heading(): void
    {
        $this->complaint();

        $this->actingAs($this->admin())
            ->get(route('admin.pengaduan.report'))
            ->assertOk()
            // The report's only heading used to be the SIPRAS wordmark; the
            // document title itself is the h1, so a screen reader announces what
            // the page is — and it matches the <title> (WCAG 2.4.2). The wordmark
            // is decoration, not a heading.
            ->assertSee('<h1 class="title">Rekapitulasi Pengaduan Sarana</h1>', false)
            ->assertSee('<p class="brand">SIPRAS</p>', false)
            ->assertDontSee('<h1>SIPRAS</h1>', false)
            // Promoting the title to a heading must not move the printed layout,
            // so the margin is set in full (a heading's default bottom margin
            // would otherwise add ~10px).
            ->assertSee('.letterhead .title { margin: 12px 0 0;', false);
    }

    public function test_the_printed_status_pills_use_the_apps_own_tints(): void
    {
        // The recap is a standalone document — dompdf has no Tailwind and the page
        // loads no app.css — so its pills have to hardcode colours, which is exactly
        // how a printed pill drifts from the on-screen chip. Pin the chain: the
        // badge's class names say which token each status uses, app.css defines that
        // token's value, and the report has to agree with both. (What this cannot
        // check is Tailwind's own class-to-token mapping; that is `npm run build`
        // plus the live sweep.)
        $badge = file_get_contents(resource_path('views/components/status-badge.blade.php'));
        $report = file_get_contents(resource_path('views/admin/reports/complaints.blade.php'));
        $tokens = $this->colorTokens();

        foreach (Complaint::STATUSES as $status) {
            $this->assertSame(
                1,
                preg_match("/'".$status."' => 'bg-([a-z]+)-container text-on-([a-z]+)-container'/", $badge, $classes),
                "The status badge names no tint for {$status}."
            );

            [$family, $onFamily] = [$classes[1], $classes[2]];

            $this->assertSame($family, $onFamily, "The badge's {$status} chip mixes two token families.");

            foreach (["--color-{$family}-container", "--color-on-{$family}-container"] as $token) {
                $this->assertArrayHasKey($token, $tokens, "app.css defines no {$token}.");
            }

            $this->assertSame(
                1,
                preg_match('/\.st-'.$status.'\s*\{([^}]*)\}/', $report, $rule),
                "The report styles no .st-{$status} pill."
            );

            $this->assertSame(
                $tokens["--color-{$family}-container"],
                $this->declaredColor($rule[1], 'background'),
                "The report's {$status} pill fill is not --color-{$family}-container."
            );

            $this->assertSame(
                $tokens["--color-on-{$family}-container"],
                $this->declaredColor($rule[1], 'color'),
                "The report's {$status} pill text is not --color-on-{$family}-container."
            );

            // The recap's summary numbers carry the same hue as the status they
            // count (the dashboard cards' rule), so they are pinned to it too.
            $this->assertSame(
                1,
                preg_match('/\.num-'.$status.'\s*\{([^}]*)\}/', $report, $number),
                "The report styles no .num-{$status} summary number."
            );

            $this->assertSame(
                $tokens["--color-on-{$family}-container"],
                $this->declaredColor($number[1], 'color'),
                "The report's {$status} summary number is not --color-on-{$family}-container."
            );
        }
    }

    public function test_the_report_mirrors_the_app_palette_beyond_the_status_pills(): void
    {
        // The status pills and the summary numbers are pinned above; this covers
        // the rest of the palette the recap hardcodes because the standalone
        // document can never load Tailwind: the ink, the brand blue, the neutral
        // metadata fill, the muted labels, the grouped surface and the hairlines.
        $report = file_get_contents(resource_path('views/admin/reports/complaints.blade.php'));
        $tokens = $this->colorTokens();

        // selector => [css property => token short name]. The name records the
        // intent; the assertion compares hex values, so a value shared by two
        // tokens (eg #e5e5ea is both outline-variant and secondary-container) is
        // fine — what has to break is a token *change* the report has not mirrored.
        $mirrors = [
            'body' => ['color' => 'on-surface'],
            '.toolbar a, .toolbar button' => ['background' => 'primary', 'color' => 'on-primary'],
            '.toolbar .ghost' => ['background' => 'secondary-container', 'color' => 'on-surface'],
            '.toolbar a:focus-visible' => ['outline' => 'primary'],
            '.letterhead' => ['border-bottom' => 'on-surface'],
            '.letterhead .brand' => ['color' => 'primary'],
            '.letterhead p' => ['color' => 'on-surface-variant'],
            '.letterhead .title' => ['color' => 'on-surface'],
            '.meta' => ['color' => 'on-surface-variant'],
            '.meta strong' => ['color' => 'on-surface'],
            '.table-wrap:focus-visible' => ['outline' => 'primary'],
            '.summary td' => ['background' => 'surface', 'border' => 'outline-variant'],
            '.summary .label' => ['color' => 'on-surface-variant'],
            '.num-total' => ['color' => 'on-surface'],
            'table.data th, table.data td' => ['border' => 'outline'],
            'table.data thead th' => ['background' => 'surface', 'color' => 'on-surface'],
            'table.data tbody tr:nth-child(even)' => ['background' => 'surface-zebra'],
            '.foot .sign-wrap' => ['color' => 'on-surface-variant'],
            '.foot .sign-wrap .sig' => ['color' => 'on-surface'],
            '.note-empty' => ['color' => 'on-surface-variant', 'border' => 'outline'],
        ];

        foreach ($mirrors as $selector => $properties) {
            $rule = $this->reportRule($report, $selector);

            foreach ($properties as $property => $token) {
                $name = "--color-{$token}";

                $this->assertArrayHasKey($name, $tokens, "app.css defines no {$name}.");

                $this->assertSame(
                    $tokens[$name],
                    $this->declaredColor($rule, $property),
                    "The report's `{$selector}` {$property} is not {$name}."
                );
            }
        }
    }

    public function test_the_report_uses_no_colour_the_app_palette_does_not_define(): void
    {
        // Mirroring is only complete if nothing sneaks in beside it: with no
        // Tailwind the recap may print any hex it likes, so every colour in its
        // <style> has to be a value a --color-* token defines. There is no
        // exception list — the zebra stripe is `--color-surface-zebra`.
        $report = file_get_contents(resource_path('views/admin/reports/complaints.blade.php'));
        $tokens = $this->colorTokens();

        preg_match('!<style>(.*?)</style>!s', $report, $style);
        $this->assertNotEmpty($style, 'The report has no <style> block to check.');

        // Comments are prose ("ink is #1c1c1e"), not declarations.
        $css = preg_replace('!/\*.*?\*/!s', '', $style[1]);

        preg_match_all('/#[0-9a-fA-F]{3,8}\b/', $css, $found);

        $stray = array_values(array_diff(
            array_unique(array_map('strtolower', $found[0])),
            array_values($tokens)
        ));

        $this->assertSame([], $stray,
            'The report prints colours no --color-* token defines: '.implode(', ', $stray));
    }

    /** The declarations of the one rule whose selector is `$selector`. */
    private function reportRule(string $report, string $selector): string
    {
        $this->assertSame(
            1,
            preg_match('/'.preg_quote($selector, '/').'\s*\{([^}]*)\}/', $report, $rule),
            "The report styles no `{$selector}` rule."
        );

        return $rule[1];
    }

    /**
     * The single colour declared for `$property` in a rule body. The value may be
     * prefixed by shorthand (eg `border: 1px solid #c6c6c8`), so the hex does not
     * have to follow the colon directly.
     */
    private function declaredColor(string $rule, string $property): string
    {
        $this->assertSame(1, preg_match('/\b'.$property.':[^;]*?(#[0-9a-fA-F]{3,8})\s*;/', $rule, $value),
            "No `{$property}` colour declared in: {$rule}");

        return strtolower($value[1]);
    }

    public function test_admin_can_filter_report_by_status(): void
    {
        $this->complaint(['title' => 'Menunggu Ini', 'status' => 'pending']);
        $this->complaint(['title' => 'Selesai Ini', 'status' => 'resolved']);

        $this->actingAs($this->admin())
            ->get(route('admin.pengaduan.report', ['status' => 'resolved']))
            ->assertOk()
            ->assertSee('Selesai Ini')
            ->assertDontSee('Menunggu Ini');
    }

    public function test_the_focusable_scroll_containers_are_labelled(): void
    {
        $this->complaint();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.pengaduan.report'))
            ->assertOk();

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        $wraps = (new DOMXPath($dom))->query('//div[contains(@class, "table-wrap")]');

        $this->assertSame(2, $wraps->length);

        $names = [];

        foreach ($wraps as $wrap) {
            // Focusable so it can be scrolled from the keyboard (WCAG 2.1.1),
            // but a bare tab stop unless it is given a role and a name.
            $this->assertSame('0', $wrap->getAttribute('tabindex'));
            $this->assertSame('region', $wrap->getAttribute('role'));
            $this->assertNotSame('', trim($wrap->getAttribute('aria-label')));

            $names[] = $wrap->getAttribute('aria-label');
        }

        // Landmarks have to be distinguishable, so the two names must differ.
        $this->assertSame($names, array_values(array_unique($names)));
    }

    public function test_both_report_tables_are_named(): void
    {
        $this->complaint();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.pengaduan.report'))
            ->assertOk();

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        $tables = (new DOMXPath($dom))->query('//table');

        $this->assertSame(2, $tables->length);

        $names = [];

        foreach ($tables as $table) {
            // Named by attribute, not an `sr-only` <caption>: this page is also the
            // dompdf source, and dompdf ignores `position: absolute`, so a
            // visually-hidden caption would print as visible ink.
            $this->assertSame(0, $table->getElementsByTagName('caption')->length);
            $this->assertNotSame('', trim($table->getAttribute('aria-label')));

            $names[] = $table->getAttribute('aria-label');
        }

        $this->assertSame($names, array_values(array_unique($names)));
    }

    public function test_admin_can_download_pdf_report(): void
    {
        $this->complaint(['title' => 'Meja Siswa Patah']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.pengaduan.report-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_siswa_cannot_access_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.pengaduan.report'))
            ->assertForbidden();
    }
}
