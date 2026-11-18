<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetailLayoutTest extends TestCase
{
    use RefreshDatabase;

    // The vintage single-card layout --------------------------------------

    public function test_detail_pages_render_the_single_card_layout_without_returning_markers(): void
    {
        // One record card for every audience: ticket header, info grid,
        // timeline, description and evidence, in the student page's reading
        // order. Staff add their status card *below* the record (never beside
        // it), so a decision follows the evidence instead of racing it. The
        // rolled-back `.detail-col` columns, the `section-tiket` banner strip
        // and the old side rail stay gone; the status card also stays
        // unpinned (sticky pins slid it over the Linimasa card).
        $complaint = Complaint::factory()->create(); // pending, its own category
        $teknisi = User::factory()->teknisi($complaint->category_id)->create();

        $cases = [
            'admin' => [User::factory()->admin()->create(), "/admin/pengaduan/{$complaint->id}"],
            'teknisi' => [$teknisi, "/teknisi/pengaduan/{$complaint->id}"],
            'siswa' => [$complaint->user, "/siswa/pengaduan/{$complaint->id}"],
        ];

        foreach ($cases as $role => [$actor, $address]) {
            $content = $this->actingAs($actor)->get($address)->assertOk()->getContent();

            $this->assertStringContainsString(
                '<h1 class="text-2xl font-bold tracking-tight text-on-surface">',
                $content,
                "[{$role}] lost the page-naming h1 of the vintage detail layout."
            );
            $this->assertStringContainsString(
                'card p-6 text-on-surface',
                $content,
                "[{$role}] lost the single-card body of the vintage detail layout."
            );
            $this->assertStringNotContainsString(
                'detail-col',
                $content,
                "[{$role}] grew back the rolled-back .detail-col columns."
            );
            $this->assertStringNotContainsString(
                'aria-labelledby="section-tiket"',
                $content,
                "[{$role}] grew back the rolled-back ticket banner strip."
            );

            if ($role !== 'siswa') {
                // Read first, decide second: the status card sits below the
                // evidence, not in a side rail beside it.
                $this->assertStringNotContainsString('grid grid-cols-1 lg:grid-cols-2 gap-5', $content);
                $this->assertGreaterThan(
                    strpos($content, 'id="evidence-image"'),
                    strpos($content, 'Perbarui Status'),
                    "[{$role}] moved the status panel back beside or above the record."
                );
                // The pin classes stay banned (see ComplaintManagementTest /
                // TeknisiWorkflowTest).
                $this->assertStringNotContainsString('lg:sticky lg:top-8', $content);
            }
        }
    }

    // The sticky chrome ---------------------------------------------------

    public function test_the_siswa_and_teknisi_topbars_carry_the_glass_backdrop(): void
    {
        // `bg-surface` painted the bar in the page's own gray (#F2F2F7), so the
        // two pill topbars read as floating buttons with content sliding
        // beneath them. The bar is frosted white now (DESIGN.md's translucent
        // sticky chrome); these pins keep the backdrop from quietly disappearing.
        $complaint = Complaint::factory()->create(); // pending, its own category
        $teknisi = User::factory()->teknisi($complaint->category_id)->create();

        $this->actingAs($complaint->user)
            ->get(route('siswa.pengaduan.show', $complaint))
            ->assertOk()
            ->assertSee('bg-surface-container/80 backdrop-blur-md sticky top-0 z-30', false);

        $this->actingAs($teknisi)
            ->get(route('teknisi.pengaduan.show', $complaint))
            ->assertOk()
            ->assertSee('bg-surface-container/80 backdrop-blur-md sticky top-0 z-30', false);
    }
}
