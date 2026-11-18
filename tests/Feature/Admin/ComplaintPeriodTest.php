<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ComplaintPeriodTest extends TestCase
{
    use RefreshDatabase;

    private function complaint(array $attributes = []): Complaint
    {
        return Complaint::factory()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'room_id' => Room::factory()->create()->id,
        ], $attributes));
    }

    private function periodRequest(string $queryString): Request
    {
        return Request::create('/?'.$queryString);
    }

    public function test_resolve_period_materializes_a_valid_pair(): void
    {
        $period = Complaint::resolvePeriod($this->periodRequest(http_build_query([
            'from' => '2026-10-01', 'to' => '2026-10-15',
        ])));

        $this->assertNotNull($period);
        $this->assertSame('2026-10-01', $period['from']);
        $this->assertSame('2026-10-15', $period['to']);
        $this->assertSame('1–15 Oktober 2026', $period['label']);
        // Jakarta midnight 1 Oct == 30 Sep 17:00 UTC; end of 15 Oct == 15 Oct 16:59:59.999999 UTC.
        $this->assertSame('2026-09-30 17:00:00', $period['bounds'][0]->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', (string) $period['bounds'][0]->tzName);
        $this->assertSame('2026-10-15 16:59:59.999999', $period['bounds'][1]->format('Y-m-d H:i:s.u'));
    }

    public function test_resolve_period_swaps_a_reversed_pair(): void
    {
        $period = Complaint::resolvePeriod($this->periodRequest(http_build_query([
            'from' => '2026-10-15', 'to' => '2026-10-01',
        ])));

        $this->assertSame('2026-10-01', $period['from']);
        $this->assertSame('2026-10-15', $period['to']);
        $this->assertSame('1–15 Oktober 2026', $period['label']);
    }

    public function test_resolve_period_labels_single_day_and_cross_year_ranges(): void
    {
        $single = Complaint::resolvePeriod($this->periodRequest(http_build_query([
            'from' => '2026-10-21', 'to' => '2026-10-21',
        ])));
        $crossMonth = Complaint::resolvePeriod($this->periodRequest(http_build_query([
            'from' => '2026-10-01', 'to' => '2026-11-15',
        ])));
        $crossYear = Complaint::resolvePeriod($this->periodRequest(http_build_query([
            'from' => '2025-12-28', 'to' => '2026-01-03',
        ])));

        $this->assertSame('21 Oktober 2026', $single['label']);
        $this->assertSame('1 Oktober – 15 November 2026', $crossMonth['label']);
        $this->assertSame('28 Desember 2025 – 3 Januari 2026', $crossYear['label']);
    }

    public function test_resolve_period_ignores_half_pairs_junk_and_rollover(): void
    {
        // Half pairs and junk are hand-edited URLs: the period is simply off.
        $this->assertNull(Complaint::resolvePeriod($this->periodRequest('from=2026-10-01')));
        $this->assertNull(Complaint::resolvePeriod($this->periodRequest('to=2026-10-15')));
        $this->assertNull(Complaint::resolvePeriod($this->periodRequest('from=asdf&to=2026-10-15')));
        // Rollover junk that Carbon would silently shift (Oct 99 -> Nov 9).
        $this->assertNull(Complaint::resolvePeriod($this->periodRequest('from=2026-10-99&to=2026-10-99')));
        // No params at all: no period.
        $this->assertNull(Complaint::resolvePeriod($this->periodRequest('')));
    }

    public function test_presets_materialize_explicit_dates_at_a_frozen_now(): void
    {
        // 21 Oct 2026 is a Wednesday in Jakarta; month has 31 days; 2026 is not a leap year.
        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(6, 0)->setTimezone('Asia/Jakarta'));

        $presets = collect(Complaint::presetPeriods());

        $this->assertSame(
            [
                ['name' => 'Hari ini', 'from' => '2026-10-21', 'to' => '2026-10-21'],
                ['name' => '7 hari', 'from' => '2026-10-15', 'to' => '2026-10-21'],
                ['name' => 'Bulan ini', 'from' => '2026-10-01', 'to' => '2026-10-31'],
                ['name' => 'Tahun ini', 'from' => '2026-01-01', 'to' => '2026-12-31'],
            ],
            $presets->all(),
        );
    }

    public function test_period_scope_filters_rows_on_both_jakarta_day_edges(): void
    {
        // created_at is stored as UTC wall-time. The out-of-edge rows sit one
        // second outside the Jakarta day-edges of 2026-10-15: its WIB day
        // spans [2026-10-14 17:00:00Z .. 2026-10-15 16:59:59.999999Z].
        $this->complaint(['title' => 'Selaras 2026-10-15', 'created_at' => '2026-10-15 00:00:00']);
        $this->complaint(['title' => 'WIB midnight edge', 'created_at' => '2026-10-14 17:00:00']); // == 15 Oct 00:00 WIB
        $this->complaint(['title' => 'One second before', 'created_at' => '2026-10-14 16:59:59']);
        $this->complaint(['title' => 'One second after', 'created_at' => '2026-10-15 17:00:00']); // == 16 Oct 00:00 WIB

        $period = Complaint::resolvePeriod($this->periodRequest(http_build_query([
            'from' => '2026-10-15', 'to' => '2026-10-15',
        ])));

        $titles = Complaint::query()->period($period)->orderBy('title')->pluck('title')->all();

        $this->assertSame(['Selaras 2026-10-15', 'WIB midnight edge'], $titles);
    }

    public function test_status_counts_accept_a_period_and_stay_global_without_one(): void
    {
        // In-period: 3 pending, 2 resolved. Out-of-period: 1 pending. The
        // all-time pending (4) and the in-period pending (3) must differ,
        // so the two calls cannot pass by coincidence.
        $this->complaint(['status' => 'pending', 'created_at' => '2026-10-10 03:00:00']);
        $this->complaint(['status' => 'pending', 'created_at' => '2026-10-11 03:00:00']);
        $this->complaint(['status' => 'pending', 'created_at' => '2026-10-12 03:00:00']);
        $this->complaint(['status' => 'pending', 'created_at' => '2025-01-01 03:00:00']);
        $this->complaint(['status' => 'resolved', 'created_at' => '2026-10-10 03:00:00']);
        $this->complaint(['status' => 'resolved', 'created_at' => '2026-10-10 03:00:00']);

        $period = Complaint::resolvePeriod($this->periodRequest(http_build_query([
            'from' => '2026-10-01', 'to' => '2026-10-31',
        ])));

        $this->assertSame(
            ['total' => 6, 'pending' => 4, 'in_progress' => 0, 'resolved' => 2, 'rejected' => 0],
            Complaint::statusCounts(),
        );
        $this->assertSame(
            ['total' => 5, 'pending' => 3, 'in_progress' => 0, 'resolved' => 2, 'rejected' => 0],
            Complaint::statusCounts($period),
        );
    }

    public function test_admin_list_filters_rows_to_the_period(): void
    {
        $this->complaint(['title' => 'Dalam periode', 'created_at' => '2026-10-10 03:00:00']);
        $this->complaint(['title' => 'Di luar periode', 'created_at' => '2025-03-03 03:00:00']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('Dalam periode')
            ->assertDontSee('Di luar periode');
    }

    public function test_junk_period_params_never_error_the_list(): void
    {
        $this->complaint(['title' => 'Barang tetap ada']);

        foreach (['from=2026-10-01', 'from=asdf&to=2026-10-31', 'from=2026-10-99&to=2026-10-99'] as $qs) {
            $this->actingAs(User::factory()->admin()->create())
                ->get('/admin/pengaduan?'.$qs)
                ->assertOk()
                ->assertSee('Barang tetap ada')
                // The period quietly did not resolve, so the active-filter
                // band must not announce a filter that isn't there: naming
                // "Filter aktif:" with no chips under it was a phantom state.
                ->assertDontSee('Filter aktif:');
        }
    }

    public function test_status_counts_are_scoped_by_the_period(): void
    {
        // In-period: 3 pending, 1 resolved. Out-of-period: 2 pending, 2
        // resolved, 1 in_progress, 1 rejected. All-time hence: total 10,
        // pending 5, in_progress 1, resolved 3, rejected 1 — the exact
        // numbers a dashboard card must keep showing while the list counts
        // the window.
        $this->complaint(['status' => 'pending', 'created_at' => '2026-10-10 03:00:00']);
        $this->complaint(['status' => 'pending', 'created_at' => '2026-10-11 03:00:00']);
        $this->complaint(['status' => 'pending', 'created_at' => '2026-10-12 03:00:00']);
        $this->complaint(['status' => 'pending', 'created_at' => '2026-01-01 03:00:00']);
        $this->complaint(['status' => 'pending', 'created_at' => '2026-01-02 03:00:00']);
        $this->complaint(['status' => 'resolved', 'created_at' => '2026-10-10 03:00:00']);
        $this->complaint(['status' => 'resolved', 'created_at' => '2026-01-03 03:00:00']);
        $this->complaint(['status' => 'resolved', 'created_at' => '2026-01-04 03:00:00']);
        $this->complaint(['status' => 'in_progress', 'created_at' => '2026-01-05 03:00:00']);
        $this->complaint(['status' => 'rejected', 'created_at' => '2026-01-06 03:00:00']);

        // The picker no longer prints counts, but the view data still scopes
        // them to the window: total 4, pending 3, in_progress 0, resolved 1.
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertViewHas('counts', [
                'total' => 4,
                'pending' => 3,
                'in_progress' => 0,
                'resolved' => 1,
                'rejected' => 0,
            ]);

        // The dashboard cards keep all-time numbers (global counts, untouched).
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('— 10 pengaduan');
    }

    public function test_pagination_carries_the_period(): void
    {
        Complaint::factory()->count(11)->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'room_id' => Room::factory()->create()->id,
            'created_at' => '2026-10-10 03:00:00',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            // The paginator appends page last (from, to, then page) and the
            // href ampersands render escaped, so match the rendering as-is.
            ->assertSee('to=2026-10-31&amp;page=2', false);
    }

    public function test_preset_chips_materialize_their_bounds_as_hrefs(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(6, 0)->setTimezone('Asia/Jakarta'));

        $list = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index'))
            ->assertOk()
            // No period is active: exactly the four preset chips, no Semua chip yet.
            ->assertSee('from=2026-10-21&to=2026-10-21')
            ->assertSee('from=2026-10-15&to=2026-10-21')
            ->assertSee('from=2026-10-01&to=2026-10-31')
            ->assertSee('from=2026-01-01&to=2026-12-31')
            // The Semua chip only renders while a period is active. Chips
            // render multi-line, so pin the unique text, not the tight tag.
            ->assertDontSee('✕ Semua');

        // Nothing is filtered yet, so nothing claims aria-current: no preset
        // chip, and the status picker's trigger only marks an applied status.
        $this->assertSame(0, substr_count($list->getContent(), 'aria-current="true"'));
    }

    public function test_an_active_period_marks_the_matching_preset_and_names_the_window(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(6, 0)->setTimezone('Asia/Jakarta'));

        $list = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk();

        // One policy on every surface (the report page already ships it):
        // the presets stay visible and the one whose bounds equal the
        // active window is the marked chip — with no status applied it is
        // the only aria-current on the page.
        $this->assertSame(1, substr_count($list->getContent(), 'aria-current="true"'));
        $list->assertSee('Hari ini');

        // The way out still heads the row (drawn-icon chip)...
        $list->assertSee('Semua periode')
            // The status chips still ride the period...
            ->assertSee('from=2026-10-01&to=2026-10-31')
            // The window is named on the trigger itself now (the band no
            // longer tags the period): the filled capsule reads
            // "1–31 Oktober 2026", swap-free. The tanggal-masuk basis is
            // stated once in the toolbar help line.
            ->assertSee('1–31 Oktober 2026')
            ->assertDontSee('Periode aktif (tanggal masuk)')
            ->assertDontSee('rentang dibalik otomatis');
    }

    public function test_a_custom_range_marks_the_range_control_instead_of_a_preset(): void
    {
        $list = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', ['from' => '2026-10-05', 'to' => '2026-10-20']))
            ->assertOk();

        // No preset owns the pair, so `Rentang…` — the control that
        // currently defines the view — is the marked one, and the only
        // aria-current on the page.
        $this->assertSame(1, substr_count($list->getContent(), 'aria-current="true"'));
        $list->assertSee('list-none bg-primary', false);
    }

    public function test_the_cetak_link_forwards_only_what_the_report_reads(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', [
                'status' => 'pending', 'q' => 'AC',
                'from' => '2026-10-01', 'to' => '2026-10-31',
            ]))
            ->assertOk()
            // q, status and the period ride; stray params never leak into
            // the printed window.
            ->assertSee('admin/pengaduan/laporan?q=AC&amp;status=pending&amp;from=2026-10-01&amp;to=2026-10-31', false)
            ->assertDontSee('laporan?q=AC&amp;status=pending&amp;from=2026-10-01&amp;to=2026-10-31&amp;sort', false);
    }

    public function test_the_range_form_rides_every_prior_filter(): void
    {

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', ['status' => 'pending', 'q' => 'AC']))
            ->assertOk()
            // The disclosure form re-posts every active filter so applying a
            // range cannot silently drop the state already chosen.
            ->assertSee('<input type="hidden" name="status" value="pending"', false)
            ->assertSee('<input type="hidden" name="q" value="AC"', false)
            ->assertSee('id="from"', false)
            ->assertSee('id="to"', false);
    }

    public function test_search_form_carries_an_active_period(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            // Searching must not silently drop the period.
            ->assertSee('<input type="hidden" name="from" value="2026-10-01"', false)
            ->assertSee('<input type="hidden" name="to" value="2026-10-31"', false);
    }

    public function test_the_report_screen_and_printed_meta_state_the_period(): void
    {
        $this->complaint(['title' => 'AC perlu rekap', 'created_at' => '2026-10-10 03:00:00']);
        $this->complaint(['title' => 'Di luar rekap', 'created_at' => '2025-02-02 03:00:00']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.report', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('1–31 Oktober 2026')
            ->assertSee('AC perlu rekap')
            ->assertDontSee('Di luar rekap');

        // No period: the existing "Semua waktu" phrasing stays the truth.
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.report'))
            ->assertOk()
            ->assertSee('<strong>Periode:</strong> Semua waktu', false);
    }

    public function test_period_and_status_compose_one_honest_meta_sentence(): void
    {
        $this->complaint(['status' => 'pending', 'created_at' => '2026-10-10 03:00:00']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.report', ['from' => '2026-10-01', 'to' => '2026-10-31', 'status' => 'pending']))
            ->assertOk()
            ->assertSee('1–31 Oktober 2026 — status Menunggu');
    }

    public function test_the_pdf_filename_carries_the_span(): void
    {
        $this->complaint(['created_at' => '2026-10-10 03:00:00']);

        $this->travelTo(now()->setDate(2026, 10, 15)->setTime(6, 0));

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.report-pdf', ['from' => '2026-10-01', 'to' => '2026-10-31']));

        $response->assertOk();

        $this->assertStringContainsString(
            'laporan-pengaduan-20261001-20261031-20261015-060000.pdf',
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_an_empty_period_still_prints_the_report(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.report', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            // The dashed empty-state note is already in the document.
            ->assertSee('Tidak ada data pengaduan untuk ditampilkan.', false)
            // The summary prints zeros rather than vanishing.
            ->assertSee('1–31 Oktober 2026');
    }

    public function test_the_pdf_name_stays_familiar_without_a_period(): void
    {
        $this->complaint();

        $this->travelTo(now()->setDate(2026, 10, 15)->setTime(6, 0));

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.report-pdf'));

        $response->assertOk();

        $this->assertStringContainsString(
            'laporan-pengaduan-20261015-060000.pdf',
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_the_status_menu_carries_and_honors_an_active_period(): void
    {
        // Click-through equality: the picker counts in-period rows and its
        // form re-posts the period, so choosing a status lands on the same
        // window the count promised — never on all-time.
        $this->complaint(['title' => 'Menunggu dalam', 'status' => 'pending', 'created_at' => '2026-10-10 03:00:00']);
        $this->complaint(['title' => 'Menunggu dalam dua', 'status' => 'pending', 'created_at' => '2026-10-11 03:00:00']);
        $this->complaint(['title' => 'Selesai dalam', 'status' => 'resolved', 'created_at' => '2026-10-12 03:00:00']);
        $this->complaint(['title' => 'Menunggu di luar', 'status' => 'pending', 'created_at' => '2025-01-01 03:00:00']);

        $page = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk();

        // The picker's form carries the period...
        $page->assertSee('<input type="hidden" name="from" value="2026-10-01"', false)
            ->assertSee('<input type="hidden" name="to" value="2026-10-31"', false);

        // ...and the combined URL shows exactly the in-period pending rows.
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', ['status' => 'pending', 'from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('Menunggu dalam dua')
            ->assertDontSee('Menunggu di luar');
    }

    public function test_a_reversed_pair_never_errors_the_list_at_http_level(): void
    {
        $this->complaint(['title' => 'Tetap terlihat', 'created_at' => '2026-10-10 03:00:00']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/pengaduan?from=2026-10-20&to=2026-10-05')
            ->assertOk()
            // The swap happened: the echo speaks the normalized order...
            ->assertSee('5–20 Oktober 2026')
            // ...and names the swap, so a hand-typed half-pair is never silent.
            ->assertSee('rentang dibalik otomatis')
            ->assertSee('Tetap terlihat');
    }

    public function test_the_cetak_and_pdf_links_ride_the_period(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            // The report hand-off carries the period...
            ->assertSee('admin/pengaduan/laporan?from=2026-10-01&amp;to=2026-10-31', false);

        // ...and the report's PDF download link carries it back.
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.report', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('laporan/pdf?from=2026-10-01&amp;to=2026-10-31', false);
    }

    public function test_a_period_empty_list_offers_tampilkan_semua(): void
    {
        $this->complaint(['title' => 'Jauh dari jendela', 'created_at' => '2025-01-01 03:00:00']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengaduan.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('Tidak ada pengaduan yang cocok dengan filter ini.', false)
            // The empty-state way out renders for a period-only filter too.
            ->assertSee('Tampilkan Semua');
    }
}
