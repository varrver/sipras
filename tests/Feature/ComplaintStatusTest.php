<?php

namespace Tests\Feature;

use App\Models\Complaint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ComplaintStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_status_has_a_label_and_no_extras(): void
    {
        // `STATUS_LABELS` is the one map behind the status badge, the filter chips,
        // the dashboard cards, the status select and the printed recap. Add a status
        // to `STATUSES` without a label and every one of those surfaces silently
        // prints the raw key instead, so fail here — next to the map — rather than
        // in five views at once. Order matters too: the chips, the select and the
        // recap all render in this sequence.
        $this->assertSame(Complaint::STATUSES, array_keys(Complaint::STATUS_LABELS));
    }

    public function test_an_unknown_status_is_labelled_by_its_raw_value(): void
    {
        // Deliberate: a status with no label must still read, which is why the
        // badge's guard below is blank-only rather than a whitelist.
        $this->assertSame('diproses', Complaint::statusLabel('diproses'));
    }

    public function test_the_status_badge_refuses_to_render_without_a_status(): void
    {
        // A blank status renders a chip with no text: a neutral gray pill that names
        // nothing, with no axe rule to catch an empty one. An *omitted* prop is left
        // undefined by `@props`, so that path dies inside the component instead.
        $cases = [
            ['/_test/badge-unset', 'testing.badge-unset', '<x-status-badge />'],
            ['/_test/badge-blank', 'testing.badge-blank', '<x-status-badge status="" />'],
        ];

        $this->withoutExceptionHandling();

        foreach ($cases as [$url, $route, $markup]) {
            Route::get($url, fn () => Blade::render($markup))->name($route);

            $this->assertFailsLoudly($url, "[{$route}]", 'rendered x-status-badge without a status');
        }
    }

    public function test_an_unknown_status_takes_the_neutral_fill_rather_than_throwing(): void
    {
        // The other half of "blank-only": no tint is known for `diproses`, so it
        // takes the neutral fill — and must not turn adding a status into a 500.
        $html = Blade::render('<x-status-badge status="diproses" />');

        $this->assertStringContainsString('diproses', $html);
        $this->assertStringContainsString('bg-secondary-container', $html);
    }
}
