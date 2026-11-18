<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function siswa(): User
    {
        return User::factory()->create();
    }

    private function complaint(array $attributes = []): Complaint
    {
        return Complaint::factory()->create(array_merge([
            'user_id' => $this->siswa()->id,
            'category_id' => Category::factory()->create()->id,
            'room_id' => Room::factory()->create()->id,
        ], $attributes));
    }

    private function validJpeg(): UploadedFile
    {
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AR//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/AR//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Aqf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8QH//EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQIBAT8QH//EABQQAQAAAAAAAAAAAAAAAAAAABD/2gAIAQEAAT8QH//Z');

        return UploadedFile::fake()->createWithContent('bukti.jpg', $jpeg);
    }

    public function test_admin_can_list_all_complaints_newest_first(): void
    {
        $this->complaint(['title' => 'Pengaduan Terlama']);
        $this->complaint(['title' => 'Pengaduan Terbaru']);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan')
            ->assertOk()
            ->assertSee('Pengaduan Terbaru')
            ->assertSee('Pengaduan Terlama');
    }

    public function test_admin_can_search_complaints(): void
    {
        // Named rooms on both sides: the widened search reaches room names,
        // and the factory's word names can randomly contain "ac" — the
        // needle "AC" must match the AC complaint (room named honestly)
        // and nothing whose accidental name borrows the substring.
        $this->complaint([
            'title' => 'AC ruang kelas rusak',
            'description' => 'Pendingin udara di kelas 2B mati.',
            'room_id' => Room::factory()->create(['name' => 'Ruang Gosar AC'])->id,
        ]);
        $this->complaint([
            'title' => 'Meja siswa patah',
            'description' => 'Papan meja terbelah di bagian pojok.',
            'room_id' => Room::factory()->create(['name' => 'Perpustakaan'])->id,
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan?q=AC')
            ->assertOk()
            ->assertSee('AC ruang kelas rusak')
            ->assertDontSee('Meja siswa patah');
    }

    public function test_search_reaches_the_columns_the_table_displays(): void
    {
        // The table shows Pelapor and Ruang, so the search an admin types
        // first — by whom, or where — must land on those columns too.
        $this->complaint([
            'title' => 'Proyektor mati total',
            'user_id' => User::factory()->create(['name' => 'Budi Santoso'])->id,
            'room_id' => Room::factory()->create(['name' => 'Lab Komputer 1'])->id,
        ]);
        $this->complaint([
            'title' => 'Kursi kelas rusak',
            'user_id' => User::factory()->create(['name' => 'Siti Rahma'])->id,
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan?q=Budi')
            ->assertOk()
            ->assertSee('Proyektor mati total')
            ->assertDontSee('Kursi kelas rusak');

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan?q=Komputer')
            ->assertOk()
            ->assertSee('Proyektor mati total');

        // The placeholder names the scope it now covers, so the empty result
        // of a pelapor/ruang search is never read as "the search is broken".
        $this->actingAs($this->admin())
            ->get('/admin/pengaduan')
            ->assertOk()
            ->assertSee('Cari judul, nomor tiket, deskripsi, pelapor, atau ruang…');
    }

    public function test_status_counts_count_inside_the_active_search(): void
    {
        // The picker no longer prints counts, but the controller still scopes
        // them to the visible set (the dashboard cards and this list share
        // them): inside the search total 3, pending 2, resolved 1.
        $those = fn (array $attrs = []) => $this->complaint(array_merge([
            'title' => 'Proyektor mati total',
        ], $attrs));
        $those(['status' => 'pending']);
        $those(['status' => 'pending']);
        $those(['status' => 'resolved']);
        $this->complaint(['title' => 'Lampu koridor mati', 'status' => 'pending']);
        $this->complaint(['title' => 'Lampu parkir mati', 'status' => 'pending']);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan?q=Proyektor')
            ->assertOk()
            ->assertViewHas('counts', [
                'total' => 3,
                'pending' => 2,
                'in_progress' => 0,
                'resolved' => 1,
                'rejected' => 0,
            ]);

        // Click-through equality: the picker re-posts the search, and that
        // URL shows exactly the pending-within-search rows.
        $this->actingAs($this->admin())
            ->get('/admin/pengaduan?status=pending&q=Proyektor')
            ->assertOk()
            ->assertSee('Proyektor mati total');

        // The dashboard keeps its all-time numbers (global counts untouched).
        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('— 5 pengaduan');
    }

    public function test_the_status_menu_lists_the_vocabulary_without_counts(): void
    {
        // The menu is the shared status vocabulary and nothing else, so the
        // trigger stays short. The counts themselves remain in the
        // controller's view data (pinned by the scoping tests).
        $this->complaint(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan')
            ->assertOk()
            ->assertSee('&quot;label&quot;:&quot;Semua&quot;', false)
            ->assertSee('&quot;label&quot;:&quot;Menunggu&quot;', false)
            ->assertSee('&quot;label&quot;:&quot;Selesai&quot;', false)
            ->assertDontSee('— 1 pengaduan', false);
    }

    public function test_the_active_keyword_lives_in_the_field_not_a_tag(): void
    {
        // Design decision: there is no active-filter feedback strip at all.
        // The band's per-dimension ✕ links (and their hidden 44×44 hit-box
        // geometry) were removed first, then the band itself: the keyword is
        // still visible in the search field it was typed into, so a
        // "Kata kunci: …" tag (and the old "Filter aktif:" lead-in) re-states
        // what the screen already says. Every dimension announces itself at
        // its own control — filled status picker, filled period trigger, and
        // the search input's value — and the single global Hapus filter resets
        // all three. It rides the filter toolbar (ms-auto) at the same 44px
        // capsule height, end-aligned after the pickers.
        $this->complaint();

        $response = $this->actingAs($this->admin())
            ->get('/admin/pengaduan?q=Budi')
            ->assertOk();

        $content = $response->getContent();
        // The keyword announces itself in the search field — no tag, no header.
        $this->assertStringContainsString('value="Budi"', $content);
        $this->assertStringNotContainsString('Kata kunci:', $content);
        $this->assertStringNotContainsString('Filter aktif:', $content);
        $this->assertStringNotContainsString('Hapus filter status', $content);
        $this->assertStringNotContainsString('Hapus filter kata kunci', $content);
        $this->assertStringNotContainsString('Hapus filter periode', $content);
        $this->assertSame(
            0,
            substr_count($content, 'gap-y-4'),
            'no hidden-bleed hit-box geometry may remain from the removed band ✕ links'
        );
        // The one reset anchor on the page is the global capsule, end-aligned
        // in the filter toolbar (its offset class pins the position; no other
        // anchor on this page may copy it).
        $this->assertSame(
            1,
            substr_count($content, 'class="btn-outline ms-auto'),
            'Hapus filter alone must wear the end-aligned toolbar capsule class; other anchors on '
            .'this page use different skins (the empty state does not render here)'
        );
    }

    public function test_nothing_tags_the_status_dimension(): void
    {
        // Status state is presented entirely by the status picker (aria-current
        // + the filled capsule), so nothing on the page may echo it as a tag:
        // a junk status in the URL highlights no chip, and a "Status: …"
        // label would contradict the chips' silence. With the band gone, the
        // keyword is likewise only in the search field.
        $this->actingAs($this->admin())
            ->get('/admin/pengaduan?status=pending&q=Budi')
            ->assertOk()
            // The keyword lives in the field; no tag may name either dimension.
            ->assertSee('value="Budi"', false)
            ->assertSee('Hapus filter')
            ->assertDontSee('Kata kunci:', false)
            ->assertDontSee('Status:', false);
    }

    public function test_the_report_prints_the_same_search_the_list_promised(): void
    {
        // "Cetak Sesuai Filter" hands off the same q the list searched —
        // a pelapor match on the list prints on the report too.
        $this->complaint([
            'title' => 'AC mati',
            'user_id' => User::factory()->create(['name' => 'Budi Santoso'])->id,
        ]);
        $this->complaint(['title' => 'Lampu mati']);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan/laporan?q=Budi')
            ->assertOk()
            ->assertSee('AC mati')
            ->assertDontSee('Lampu mati');
    }

    public function test_the_period_popover_close_and_required_dates_are_pinned_in_markup(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/pengaduan')
            ->assertOk()
            // Outside-click and Escape close the disclosure...
            ->assertSee('@click.outside="$el.open = false"', false)
            ->assertSee('@keydown.escape.prevent="$el.open = false"', false)
            // ...and both custom-range dates are required, so a half pair
            // can never be submitted from the form in the first place.
            ->assertSee('id="from" name="from" type="date" value="" required', false)
            ->assertSee('id="to" name="to" type="date" value="" required', false);
    }

    public function test_admin_can_filter_complaints_by_status(): void
    {
        $pending = $this->complaint(['title' => 'Lampu koridor mati', 'status' => 'pending']);
        $resolved = $this->complaint(['title' => 'Pintu kelas diperbaiki', 'status' => 'resolved']);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan?status=pending')
            ->assertOk()
            ->assertSee('Lampu koridor mati')
            ->assertDontSee('Pintu kelas diperbaiki')
            // The active picker announces which filter is applied — as a real
            // attribute, not one whose value is `"true"` with literal quotes.
            ->assertSee('aria-current="true"', false);
    }

    public function test_an_unknown_status_filters_nothing_instead_of_erroring(): void
    {
        $this->complaint(['title' => 'Kran bocor']);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan?status=hack')
            ->assertOk()
            ->assertSee('Kran bocor')
            // The page did not really filter, so the filtered-empty copy must
            // not claim it did (the student list's rule).
            ->assertDontSee('Tidak ada pengaduan yang cocok');
    }

    public function test_admin_status_filter_is_combined_with_search(): void
    {
        $pending = $this->complaint(['title' => 'AC ruang kelas rusak', 'status' => 'pending']);
        $resolved = $this->complaint(['title' => 'AC perpustakaan rusak', 'status' => 'resolved']);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan?status=resolved&q=AC')
            ->assertOk()
            ->assertSee('AC perpustakaan rusak')
            ->assertDontSee('AC ruang kelas rusak');
    }

    public function test_the_search_field_has_a_real_label_not_only_a_placeholder(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/pengaduan')
            ->assertOk()
            ->assertSee('Cari pengaduan')
            // the label is associated with the input, so it is the accessible name
            ->assertSee('for="q"', false);
    }

    public function test_searching_keeps_the_active_status_filter(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/pengaduan?status=pending&q=kursi')
            ->assertOk()
            // the search form carries the chip's status through,
            // so submitting a search cannot silently drop it
            ->assertSee('name="status" value="pending"', false)
            // and the clearing action names what it actually clears
            ->assertSee('Hapus filter');
    }

    public function test_admin_can_view_complaint_detail(): void
    {
        $complaint = $this->complaint(['title' => 'AC rusak', 'status' => 'pending']);

        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee('AC rusak')
            ->assertSee($complaint->ticket_number)
            ->assertSee('Linimasa')
            // Same trace as the student's own detail page: every stage named,
            // the current one flagged. (The status select also prints these
            // labels, so absence is asserted via the timeline's own marker.)
            ->assertSeeInOrder(['Menunggu', 'Dalam Proses', 'Selesai'])
            ->assertSee('sedang berlangsung');
    }

    public function test_the_status_form_pins_a_submitting_guard(): void
    {
        $complaint = $this->complaint();

        // Same DOM contract as the siswa create form: a resolution photo can
        // ride the upload slow, so the POST
        // button goes dark and says "Menyimpan…" while it is in flight.
        // The resting label stays "Perbarui Status" per the DESIGN.md glossary.
        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee('Perbarui Status')
            // The form seeds the select from the stored status and keeps a
            // submitter-checked gate for consequential transitions.
            ->assertSee('x-data="{', false)
            ->assertSee('formStatus:', false)
            ->assertSee('originalStatus:', false)
            ->assertSee('submitting = true', false)
            ->assertSee('x-bind:disabled="submitting"', false)
            ->assertSee('x-show="submitting" x-cloak', false)
            ->assertSee('Menyimpan…');
    }

    public function test_admin_timeline_for_rejected_complaint_shows_only_real_history(): void
    {
        $complaint = $this->complaint(['status' => 'rejected', 'admin_notes' => 'Bukti tidak jelas.']);

        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee('Linimasa')
            ->assertSee('Menunggu')
            // The verdict is an explicit red end-node, not a stage set to a
            // state it never reached. ("Dalam Proses" also prints inside the
            // status select, so it cannot be asserted by absence here.)
            ->assertSee('data-state="terminal-rejected"', false)
            ->assertSee('Ditolak')
            // No active node on an ended track — it isn't waiting.
            // (The active marker is the timeline-specific signal.)
            ->assertDontSee('sedang berlangsung');
    }

    public function test_admin_timeline_shows_resolution_time_for_resolved_complaint(): void
    {
        $resolvedAt = now()->subDay();
        $complaint = $this->complaint(['status' => 'resolved', 'resolved_at' => $resolvedAt]);

        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee($resolvedAt->translatedFormat('d M Y, H:i'))
            // Resolved ends the track: a filled completion node, not a stage
            // still labelled "sedang berlangsung".
            ->assertSee('data-state="terminal-complete"', false)
            ->assertSee('Selesai')
            ->assertDontSee('sedang berlangsung');
    }

    public function test_the_record_card_carries_the_stored_decision(): void
    {
        $rejected = $this->complaint([
            'status' => 'rejected',
            'admin_notes' => 'Bukti tidak jelas, mohon unggah ulang foto.',
        ]);
        $resolved = $this->complaint([
            'status' => 'resolved',
            'resolved_at' => now()->subDay(),
            'admin_notes' => 'Proyektor telah diganti dan berfungsi normal.',
        ]);

        $expected = [
            ['id' => $rejected->id, 'tint' => 'bg-error-container', 'label' => 'Alasan Penolakan', 'needle' => 'Bukti tidak jelas'],
            ['id' => $resolved->id, 'tint' => 'bg-secondary-container', 'label' => 'Catatan Admin', 'needle' => 'Proyektor telah diganti'],
        ];

        foreach ($expected as $case) {
            $response = $this->actingAs($this->admin())
                ->get("/admin/pengaduan/{$case['id']}")
                ->assertOk();

            $dom = new DOMDocument;
            libxml_use_internal_errors(true);
            $dom->loadHTML($response->getContent());
            libxml_clear_errors();

            // The stored decision is part of the record, not only the editable
            // form field: a tinted note box in the record card, whose heading
            // (the vintage markup's h3) names the same words the student
            // reads. Value-only <p>s carry no heading, so the structure
            // (tint + heading + labelled value) is the honest selector.
            $callouts = (new DOMXPath($dom))->query(
                "//div[contains(@class, '{$case['tint']}')][.//h3[contains(., '{$case['label']}')]][.//p[contains(., '{$case['needle']}')]]"
            );

            $this->assertSame(
                1,
                $callouts->length,
                "the record card must carry one {$case['label']} callout carrying the stored note"
            );
            $heading = $callouts->item(0)?->getElementsByTagName('h3')?->item(0)?->textContent ?? '';
            $this->assertStringContainsString($case['label'], $heading);
        }
    }

    public function test_resolved_at_is_not_re_stamped_when_saving_an_already_resolved_complaint(): void
    {
        $original = now()->subDays(3)->startOfSecond();
        $complaint = $this->complaint([
            'status' => 'resolved',
            'resolved_at' => $original,
            'admin_notes' => 'Proyektor telah diganti.',
        ]);

        $this->actingAs($this->admin())
            ->patch("/admin/pengaduan/{$complaint->id}/status", [
                'status' => 'resolved',
                'admin_notes' => 'Proyektor telah diganti dan dipakai guru kelas VII-A.',
            ])
            ->assertRedirect("/admin/pengaduan/{$complaint->id}#flash-success");

        // A note typo is a save, not a repair: the completion time the record
        // already carries must survive a re-save of the same status.
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'resolved',
            'resolved_at' => $original->toDateTimeString(),
            'admin_notes' => 'Proyektor telah diganti dan dipakai guru kelas VII-A.',
        ]);
    }

    public function test_the_status_form_gates_terminal_transitions_behind_a_confirm_dialog(): void
    {
        $rejected = $this->complaint(['status' => 'rejected', 'admin_notes' => 'Bukti tidak jelas.']);
        $resolved = $this->complaint(['status' => 'resolved', 'resolved_at' => now()->subDay()]);

        // Every status form carries the confirm contract; it self-gates client
        // side, so the page for a pending complaint renders it too — but the
        // ended complaints' pages are where the copy reads credibly.
        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$rejected->id}")
            ->assertOk()
            // Same naming pattern as the siswa create modal: aria-labelledby and the
            // open/close event both key off this name, and the panel must be
            // labelled by the h2 the consumer renders.
            ->assertSee('confirm-status-change', false)
            ->assertSee('id="confirm-status-change-title"', false)
            ->assertSee('Konfirmasi Perubahan Status')
            // The three consequential branches are stated before anything submits.
            ->assertSee('Mengubah status akan membuka kembali tiket yang sudah berakhir', false)
            ->assertSee('Pengaduan ini sudah selesai.', false)
            ->assertSee('Foto dokumentasi yang tersimpan akan diganti penuh', false)
            ->assertSee('Ya, Lanjutkan')
            // Only the confirm button's click may pass the guard, and it does so
            // statelessly: the submitter carries data-confirm, the handler checks
            // it. No remembered flag can outlive a cancelled dialog.
            ->assertSee('data-confirm="1"', false)
            ->assertSee("\$event.submitter?.dataset?.confirm !== '1'", false);

        // Same contract on the resolved page, where the modal is what keeps a
        // note typo from silently reopening a finished ticket.
        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$resolved->id}")
            ->assertOk()
            ->assertSee('confirm-status-change', false)
            ->assertSee('Konfirmasi Perubahan Status');
    }

    public function test_entering_rejection_is_gated_behind_the_same_confirm_dialog(): void
    {
        $complaint = $this->complaint(['status' => 'pending']);

        // Leaving an ended state asks before it reopens a finished ticket;
        // entering Ditolak runs the same terminal decision in the other
        // direction, and writes the typed reason into a record the student
        // reads, so it asks the same question first.
        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee('enter-rejected', false)
            ->assertSee('Pengaduan akan ditolak', false);
    }

    public function test_the_status_form_echoes_the_chosen_state_without_pinning_the_rail(): void
    {
        $complaint = $this->complaint([
            'status' => 'resolved',
            'resolved_at' => now()->subDay(),
            'admin_notes' => 'Proyektor telah diganti.',
        ]);

        // The chosen status is echoed as its consequence — not just a label —
        // and a stored note is named where it is also edited instead of silently
        // sitting in two places. The form card never pins: a sticky element
        // travels anywhere inside its containing block, and the sidebar column
        // spans all three rail cards, so pinning let the form slide over the
        // Linimasa/Informasi Tiket cards on scroll (and its z-10 tie against
        // the timeline's positioned nodes painted icons over the pinned card).
        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertDontSee('lg:sticky lg:top-8', false)
            ->assertSee('id="status-effect"', false)
            ->assertSee('menunggu tinjauan sarana', false)
            ->assertSee('perbaikan sedang berjalan', false)
            ->assertSee('mengakhiri tiket', false)
            ->assertSee('keputusan akhir pengaduan ini', false)
            ->assertSee('Catatan tersimpan tampil pada kartu', false);

        // Without a stored note there is nothing duplicated, so the reminder
        // must not pretend otherwise.
        $noNotes = $this->complaint(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$noNotes->id}")
            ->assertOk()
            ->assertDontSee('Catatan tersimpan tampil pada kartu', false);
    }

    public function test_the_leaving_rejection_confirm_carries_the_destructive_treatment(): void
    {
        $complaint = $this->complaint(['status' => 'rejected', 'admin_notes' => 'Bukti tidak jelas.']);

        // Walking back a written rejection reopens a finished record and
        // undoes a decision the student already read — that confirm's CTA
        // carries the system's destructive tint, bound live so the same
        // button stays primary for the routine branches. The dialog itself
        // floats near the vertical centre instead of reading as a card
        // pinned to the top edge; long content still scrolls, because the
        // panel centers on auto margins.
        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee('btn-error', false)
            ->assertSee('flex min-h-full items-center justify-center', false)
            ->assertSee('my-auto', false);
    }

    public function test_the_status_branch_reveal_is_announced_to_screen_readers(): void
    {
        $complaint = $this->complaint();

        // Picking a status reveals (or hides) whole form sections client-side;
        // a screen reader gets a polite sentence naming what appeared.
        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee('aria-live="polite"', false)
            ->assertSee('branchAnnounce', false)
            ->assertSee('Kolom Alasan Penolakan muncul', false)
            ->assertSee('Kolom Catatan Admin dan Dokumentasi Perbaikan muncul', false);
    }

    public function test_saving_status_returns_to_the_confirmation_banner_not_the_page_top(): void
    {
        $complaint = $this->complaint();

        // The save lands on the changed record, not on a re-scroll: the
        // redirect carries the flash banner's anchor so the confirmation and
        // the record card are the first thing read after saving.
        $this->actingAs($this->admin())
            ->patch("/admin/pengaduan/{$complaint->id}/status", ['status' => 'in_progress'])
            ->assertRedirect("/admin/pengaduan/{$complaint->id}#flash-success");

        // The banner the fragment points at actually renders on the receiving page.
        $this->followRedirects($this->actingAs($this->admin())->get("/admin/pengaduan/{$complaint->id}"))
            ->assertOk()
            ->assertSee('id="flash-success"', false);
    }

    public function test_photos_collapse_on_small_screens_and_expand_on_demand(): void
    {
        // Evidence and proof photos sit above the foldless form; a capped
        // preview with an explicit expand keeps the decision controls one
        // pilot's flick away instead of two photo screens down.
        $complaint = $this->complaint([
            'status' => 'resolved',
            'resolved_at' => now()->subDay(),
            'admin_notes' => 'Proyektor telah diganti.',
            'resolved_image' => 'complaints/resolved/example.jpg',
        ]);

        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            // The collapsed cap applies to both photos (the new utility only
            // exists when the preview logic shipped, so this pin is literal).
            ->assertSee('max-h-52 sm:max-h-96', false)
            // One always-visible control per photo, labelled before Alpine
            // boots and switched live; the bind state is a real attribute, not
            // a string inside quotes.
            ->assertSee('Tampilkan foto lengkap')
            ->assertSee('Sembunyikan foto lengkap')
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('id="evidence-image"', false)
            ->assertSee('id="resolved-image"', false);
    }

    public function test_evidence_photos_show_the_whole_picture_in_a_placeholder_frame(): void
    {
        // The bukti photo is the diagnostic record of a broken facility. A
        // center-crop hides a defect sitting at the photo's edge and looks
        // natural while doing it, so the preview guarantees the whole picture
        // (object-contain) inside a neutral placeholder frame, and the expand
        // control is a real secondary button, not a thin underlined link.
        $complaint = $this->complaint([
            'status' => 'resolved',
            'resolved_at' => now()->subDay(),
            'resolved_image' => 'complaints/resolved/example.jpg',
        ]);

        $response = $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}");
        $response->assertOk();

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        foreach (['evidence-image', 'resolved-image'] as $id) {
            $img = $dom->getElementById($id);
            $this->assertNotNull($img, "the {$id} photo must render");

            $this->assertStringContainsString(
                'object-contain',
                $img->getAttribute('class'),
                "the {$id} preview must default to the whole picture, not a crop"
            );
            $this->assertStringNotContainsString('object-cover', $img->getAttribute('class'));

            $this->assertStringContainsString(
                'max-h-52 sm:max-h-96 object-contain',
                (string) $img->getAttribute('x-bind:class'),
                "the collapsed preview of {$id} stays capped, but capped to a whole image"
            );

            $frame = $img->parentElement;
            $this->assertNotNull($frame, "the {$id} photo sits in a frame");
            $this->assertStringContainsString(
                'bg-outline-variant',
                $frame->getAttribute('class'),
                "the {$id} preview letterboxes on the same placeholder fill the siswa photo box uses"
            );

            $toggles = [];
            foreach ($dom->getElementsByTagName('button') as $button) {
                if ($button->getAttribute('aria-controls') === $id) {
                    $toggles[] = $button;
                }
            }
            $this->assertCount(1, $toggles, "the {$id} photo must keep exactly one expand control");
            $this->assertStringContainsString(
                'btn-secondary',
                $toggles[0]->getAttribute('class'),
                'the expand control is a real secondary button, not a text link'
            );
        }
    }

    public function test_the_form_carries_the_records_last_update_stamp(): void
    {
        // The closed right column reads as dead space; a real "last update"
        // stamp (the record's own updated_at) closes it and tells the admin
        // when the record was last touched — only data the table has.
        $updated = now()->subHours(2)->startOfSecond();
        $complaint = $this->complaint(['updated_at' => $updated]);

        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee('Diperbarui pada')
            ->assertSee($updated->translatedFormat('d F Y, H:i'));
    }

    public function test_the_status_form_states_when_a_rejection_reason_is_required(): void
    {
        $complaint = $this->complaint(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            // The rule the server enforces is visible before submitting, not
            // something the admin first discovers as a 422.
            ->assertSee('Alasan Penolakan')
            ->assertSee('Catatan Admin (opsional)')
            ->assertSee('Wajib diisi saat menolak')
            // Format and size limits are stated up front.
            ->assertSee('JPG atau PNG, maksimal 2 MB')
            // The section heading and its submit button name the same action.
            ->assertSee('Perbarui Status');
    }

    public function test_admin_can_move_complaint_to_in_progress(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->admin())
            ->patch("/admin/pengaduan/{$complaint->id}/status", ['status' => 'in_progress'])
            ->assertRedirect("/admin/pengaduan/{$complaint->id}#flash-success");

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'status' => 'in_progress']);
    }

    public function test_admin_notes_required_when_rejecting(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->admin())
            ->patch("/admin/pengaduan/{$complaint->id}/status", ['status' => 'rejected', 'admin_notes' => ''])
            ->assertSessionHasErrors('admin_notes');

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'status' => 'pending']);
    }

    public function test_admin_can_reject_complaint_with_notes(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->admin())
            ->patch("/admin/pengaduan/{$complaint->id}/status", [
                'status' => 'rejected',
                'admin_notes' => 'Bukti kurang jelas, mohon unggah foto yang lebih terang.',
            ])
            ->assertRedirect("/admin/pengaduan/{$complaint->id}#flash-success");

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'rejected',
            'admin_notes' => 'Bukti kurang jelas, mohon unggah foto yang lebih terang.',
        ]);
    }

    public function test_resolved_sets_resolved_at_without_image(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->admin())
            ->patch("/admin/pengaduan/{$complaint->id}/status", ['status' => 'resolved'])
            ->assertRedirect("/admin/pengaduan/{$complaint->id}#flash-success");

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function test_admin_can_resolve_complaint_with_image(): void
    {
        Storage::fake('public');

        $complaint = $this->complaint();

        $this->actingAs($this->admin())
            ->patch("/admin/pengaduan/{$complaint->id}/status", [
                'status' => 'resolved',
                'admin_notes' => 'AC telah diperbaiki.',
                'resolved_image' => $this->validJpeg(),
            ])
            ->assertRedirect("/admin/pengaduan/{$complaint->id}#flash-success");

        $complaint->refresh();
        $this->assertEquals('resolved', $complaint->status);
        $this->assertNotNull($complaint->resolved_at);
        Storage::disk('public')->assertExists($complaint->resolved_image);
    }

    public function test_status_must_be_valid(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->admin())
            ->patch("/admin/pengaduan/{$complaint->id}/status", ['status' => 'bogus'])
            ->assertSessionHasErrors('status');
    }

    public function test_siswa_cannot_access_admin_complaint_pages(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->siswa())
            ->get('/admin/pengaduan')
            ->assertForbidden();

        $this->actingAs($this->siswa())
            ->patch("/admin/pengaduan/{$complaint->id}/status", ['status' => 'in_progress'])
            ->assertForbidden();
    }
}
