<?php

namespace Tests\Feature\Siswa;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    use RefreshDatabase;

    private function validJpeg(): UploadedFile
    {
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AR//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/AR//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Aqf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8QH//EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQIBAT8QH//EABQQAQAAAAAAAAAAAAAAAAAAABD/2gAIAQEAAT8QH//Z');

        return UploadedFile::fake()->createWithContent('bukti.jpg', $jpeg);
    }

    private function siswa(string $name = 'Siswa'): User
    {
        return User::factory()->create(['name' => $name]);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function complaintFor(User $user, array $attributes = []): Complaint
    {
        return Complaint::factory()->create(array_merge([
            'user_id' => $user->id,
            'category_id' => Category::factory()->create()->id,
            'room_id' => Room::factory()->create()->id,
        ], $attributes));
    }

    public function test_siswa_can_access_complaint_create_form(): void
    {
        $category = Category::factory()->create(['name' => 'Elektronik']);
        $room = Room::factory()->create(['name' => 'Lab Komputer 1']);

        $this->actingAs($this->siswa())
            ->get('/siswa/pengaduan/create')
            ->assertOk()
            ->assertSee('Elektronik')
            ->assertSee('Lab Komputer 1')
            ->assertSee('Bukti');

        // The 2 MB upload rides a slow school connection: the form pins a
        // submitting flag (set on submit, driving the button's :disabled and
        // the "Mengirim…" swap) so a slow upload cannot silently file a
        // duplicate ticket. These strings are the DOM contract for that flag.
        $this->actingAs($this->siswa())
            ->get('/siswa/pengaduan/create')
            ->assertOk()
            ->assertSee('@submit="submitting = true"', false)
            ->assertSee('x-bind:disabled="submitting"', false)
            ->assertSee('x-show="submitting" x-cloak', false)
            ->assertSee('Mengirim…');
    }

    public function test_the_create_form_groups_and_orders_the_room_picker(): void
    {
        // One room per group, plus two classrooms of the same floor: this pins
        // both the grouping contract and the code-ordered option layout.
        Room::factory()->create(['name' => 'Ruang 1.02', 'code' => 'R-1.02']);
        Room::factory()->create(['name' => 'Ruang 1.01', 'code' => 'R-1.01']);
        Room::factory()->create(['name' => 'Ruang 2.10', 'code' => 'R-2.10']);
        Room::factory()->create(['name' => 'Bengkel SKT', 'code' => 'R-2.13']);
        Room::factory()->create(['name' => 'Lab. Fisika', 'code' => 'LPF']);
        Room::factory()->create(['name' => 'RPS TKR', 'code' => 'RPS-TKR']);
        Room::factory()->create(['name' => 'UKS', 'code' => 'UKS']);

        $this->actingAs($this->siswa())
            ->get('/siswa/pengaduan/create')
            ->assertOk()
            // One ordered assertion pins everything: groups in floor-plan
            // order (classrooms floor by floor, labs, workshops, RPS,
            // facilities) and inside them plan-order options. Function names
            // rule over codes: Bengkel SKT carries the R-2.13 classroom code
            // but must file after the labs, with the workshops. The (…code)
            // option labels let the test read the rendered order itself.
            ->assertSeeInOrder([
                'R-1.01',
                'R-1.02',
                'R-2.10',
                'Lab. Fisika (LPF)',
                'Bengkel SKT (R-2.13)',
                'RPS TKR (RPS-TKR)',
                'UKS (UKS)',
            ]);
    }

    public function test_the_room_picker_is_searchable_and_the_category_picker_is_not(): void
    {
        Category::factory()->create();
        Room::factory()->create(['name' => 'Lab Komputer 1', 'code' => 'LPF']);

        $this->actingAs($this->siswa())
            ->get('/siswa/pengaduan/create')
            ->assertOk()
            // The 105-room menu opts into the in-page search; the category menu
            // stays a plain picker. The search input lives in the room menu and
            // keeps a real label, so its placeholder is an example, not its name.
            ->assertSee('data-searchable="1"', false)
            ->assertSee('data-searchable=""', false)
            ->assertSee('Cari ruang…')
            ->assertSee('aria-label="Cari ruang…"', false)
            ->assertSee('Tidak ada ruang yang cocok');
    }

    public function test_siswa_can_submit_complaint_with_evidence(): void
    {
        Storage::fake('public');

        $category = Category::factory()->create();
        $room = Room::factory()->create();

        $response = $this->actingAs($this->siswa())
            ->post('/siswa/pengaduan', [
                'category_id' => $category->id,
                'room_id' => $room->id,
                'title' => 'AC kelas tidak menyala',
                'description' => 'AC di ruang kelas rusak dan tidak menyala sejak kemarin.',
                'evidence_image' => $this->validJpeg(),
            ]);

        $response->assertRedirect('/siswa/pengaduan?created='.Complaint::first()->ticket_number);

        $this->assertDatabaseHas('complaints', [
            'title' => 'AC kelas tidak menyala',
            'status' => 'pending',
            'admin_notes' => null,
            'resolved_at' => null,
        ]);

        $complaint = Complaint::first();
        $this->assertMatchesRegularExpression('/^TKT-\d{8}-\d{4}$/', $complaint->ticket_number);
        Storage::disk('public')->assertExists($complaint->evidence_image);

        // Peak-end: the success flash names the ticket it just issued, so the
        // siswa can quote it to the admin without opening the detail page.
        $this->assertSame(
            'Pengaduan '.$complaint->ticket_number.' berhasil dikirim dan sedang menunggu verifikasi admin.',
            $response->getSession()->get('success'),
        );
    }

    public function test_description_cannot_exceed_5000_characters(): void
    {
        // Description is unbounded on purpose nowhere: it rides the same
        // rejection review screen as a sentence or a page of prose, so the
        // server caps it at 5.000 characters.
        $this->actingAs($this->siswa())
            ->post('/siswa/pengaduan', [
                'category_id' => Category::factory()->create()->id,
                'room_id' => Room::factory()->create()->id,
                'title' => 'Judul',
                'description' => str_repeat('a', 5001),
                'evidence_image' => $this->validJpeg(),
            ])
            ->assertSessionHasErrors('description');
    }

    public function test_complaint_requires_valid_data(): void
    {
        $this->actingAs($this->siswa())
            ->post('/siswa/pengaduan', [
                'category_id' => '',
                'room_id' => '',
                'title' => '',
                'description' => '',
                'evidence_image' => '',
            ])
            ->assertSessionHasErrors(['category_id', 'room_id', 'title', 'description', 'evidence_image']);
    }

    public function test_evidence_must_be_a_valid_image(): void
    {
        $this->actingAs($this->siswa())
            ->post('/siswa/pengaduan', [
                'category_id' => Category::factory()->create()->id,
                'room_id' => Room::factory()->create()->id,
                'title' => 'Judul',
                'description' => 'Deskripsi',
                'evidence_image' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('evidence_image');
    }

    public function test_evidence_must_not_exceed_two_megabytes(): void
    {
        $this->actingAs($this->siswa())
            ->post('/siswa/pengaduan', [
                'category_id' => Category::factory()->create()->id,
                'room_id' => Room::factory()->create()->id,
                'title' => 'Judul',
                'description' => 'Deskripsi',
                'evidence_image' => UploadedFile::fake()->create('besar.jpg', 3000, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('evidence_image');
    }

    public function test_ticket_numbers_are_unique_across_same_day(): void
    {
        $user = $this->siswa();

        foreach (range(1, 2) as $index) {
            $complaint = $this->complaintFor($user, ['title' => "Pengaduan {$index}"]);
            $this->assertMatchesRegularExpression('/^TKT-\d{8}-\d{4}$/', $complaint->ticket_number);
        }

        $tickets = Complaint::query()->pluck('ticket_number');
        $this->assertCount(2, $tickets->unique());
    }

    public function test_the_create_page_heading_aligns_with_its_form_column(): void
    {
        // The form card is a max-w-3xl column, so the page's h1 must live inside
        // a matching centered column — not on the max-w-7xl page-heading rail,
        // which at wide desktop leaves the heading far left of the card it names.
        $response = $this->actingAs($this->siswa())
            ->get('/siswa/pengaduan/create')
            ->assertOk();

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        $h1 = (new \DOMXPath($dom))->query('//h1')->item(0);
        $this->assertNotNull($h1, 'The create page rendered without an <h1>.');

        for ($node = $h1->parentNode; $node !== null; $node = $node->parentNode) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $class = $node->getAttribute('class');

            if (str_contains($class, 'max-w-3xl') && str_contains($class, 'mx-auto')) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail('The create page <h1> is not inside the card\'s centered max-w-3xl column.');
    }

    public function test_server_validation_errors_are_announced_on_the_create_page(): void
    {
        // After a validation redirect the page re-renders with the server's
        // error lists below the fields. Each list must be a live region
        // (role="alert") so screen readers announce it on arrival, and must
        // carry data-field-error — the handle the focus script looks for to
        // move the caret to the first field that needs the siswa.
        $response = $this->actingAs($this->siswa())
            ->from(route('siswa.pengaduan.create'))
            ->followingRedirects()
            ->post('/siswa/pengaduan', [
                'category_id' => '',
                'room_id' => '',
                'title' => '',
                'description' => '',
            ])
            ->assertOk();

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        $announced = (new \DOMXPath($dom))->query('//ul[@data-field-error and @role="alert"]');
        $this->assertGreaterThanOrEqual(1, $announced->length, 'The create page rendered no announced validation-error list.');
    }

    public function test_the_create_form_names_the_empty_master_data_dead_end(): void
    {
        // Fresh DB: the admin has not provisioned categories or rooms yet. The
        // selects would otherwise be placeholder-only — a silent dead end the
        // form cannot leave (native `required` blocks submission with a bubble
        // in the browser's locale), so the page must say why and who can fix it.
        $this->actingAs($this->siswa())
            ->get('/siswa/pengaduan/create')
            ->assertOk()
            ->assertSee('Belum ada kategori atau ruang yang tersedia')
            ->assertSee('hubungi admin');

        // With master data present the notice must not render.
        Category::factory()->create();
        Room::factory()->create();

        $this->actingAs($this->siswa('Berdata'))
            ->get('/siswa/pengaduan/create')
            ->assertOk()
            ->assertDontSee('Belum ada kategori');
    }

    public function test_siswa_can_list_own_complaints_only(): void
    {
        $user = $this->siswa();
        $other = $this->siswa('Siswa Lain');

        $this->complaintFor($user, ['title' => 'Pengaduan milik saya']);
        $this->complaintFor($other, ['title' => 'Pengaduan orang lain']);

        $this->actingAs($user)
            ->get('/siswa/pengaduan')
            ->assertOk()
            ->assertSee('Pengaduan milik saya')
            ->assertDontSee('Pengaduan orang lain');
    }

    public function test_siswa_can_view_own_complaint_details(): void
    {
        $user = $this->siswa();
        $complaint = $this->complaintFor($user, [
            'title' => 'AC rusak',
            'description' => 'AC tidak dingin.',
            'status' => 'in_progress',
        ]);

        $this->actingAs($user)
            ->get("/siswa/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee('AC rusak')
            ->assertSee('AC tidak dingin.')
            ->assertSee($complaint->ticket_number)
            ->assertSee('Dalam Proses');
    }

    public function test_siswa_cannot_view_other_users_complaint(): void
    {
        $owner = $this->siswa();
        $intruder = $this->siswa('Penyusup');
        $complaint = $this->complaintFor($owner);

        $this->actingAs($intruder)
            ->get("/siswa/pengaduan/{$complaint->id}")
            ->assertForbidden();
    }

    public function test_admin_cannot_access_siswa_complaint_pages(): void
    {
        $this->actingAs($this->admin())
            ->get('/siswa/pengaduan')
            ->assertForbidden();
    }

    public function test_siswa_dashboard_shows_recent_complaints(): void
    {
        $user = $this->siswa();
        $complaint = $this->complaintFor($user, ['title' => 'Meja kursi patah']);

        $this->actingAs($user)
            ->get('/siswa/dashboard')
            ->assertOk()
            ->assertSee('Meja kursi patah')
            ->assertSee($complaint->ticket_number);
    }

    public function test_detail_page_shows_status_timeline(): void
    {
        $user = $this->siswa();
        $complaint = $this->complaintFor($user, [
            'title' => 'Kran wudhu bocor',
            'status' => 'in_progress',
        ]);

        $this->actingAs($user)
            ->get("/siswa/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee('Linimasa')
            ->assertSeeInOrder(['Menunggu', 'Dalam Proses', 'Selesai'])
            ->assertSee('sedang berlangsung')
            ->assertDontSee('Ditolak');
    }

    public function test_timeline_marks_pending_complaint_as_first_stage(): void
    {
        $user = $this->siswa();
        $complaint = $this->complaintFor($user, ['title' => 'Lampu kelas mati']);

        $this->actingAs($user)
            ->get("/siswa/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee('Menunggu — sedang berlangsung')
            ->assertSee('Dalam Proses')
            ->assertSee('Selesai');
    }

    public function test_timeline_shows_resolution_time_for_resolved_complaint(): void
    {
        $user = $this->siswa();
        $resolvedAt = now()->subDays(2);
        $complaint = $this->complaintFor($user, [
            'title' => 'Proyektor rusak',
            'status' => 'resolved',
            'resolved_at' => $resolvedAt,
        ]);

        $this->actingAs($user)
            ->get("/siswa/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee($resolvedAt->translatedFormat('d M Y, H:i'))
            // Resolved is an end, not a pause: a filled completion node — no
            // "sedang berlangsung" anywhere on a finished track.
            ->assertSee('data-state="terminal-complete"', false)
            ->assertSee('Selesai')
            ->assertDontSee('sedang berlangsung');
    }

    public function test_timeline_for_rejected_complaint_shows_only_real_history(): void
    {
        $user = $this->siswa();
        $complaint = $this->complaintFor($user, [
            'title' => 'Spanduk tidak sesuai',
            'status' => 'rejected',
            'admin_notes' => 'Bukti tidak cukup jelas.',
        ]);

        // Real history: the complaint was filed as pending, then returned to
        // the student with an explicit red end-node. "Dalam Proses" never
        // happened (rejected seed rows never leave pending), so the track
        // must not celebrate reaching it.
        $this->actingAs($user)
            ->get("/siswa/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee('Linimasa')
            ->assertSee('Menunggu')
            ->assertSee('data-state="terminal-rejected"', false)
            ->assertSee('Ditolak')
            ->assertDontSee('Dalam Proses')
            ->assertDontSee('Selesai')
            ->assertDontSee('sedang berlangsung');
    }

    public function test_siswa_can_filter_own_complaints_by_status(): void
    {
        $user = $this->siswa();
        $this->complaintFor($user, ['title' => 'Kursi goyang', 'status' => 'pending']);
        $this->complaintFor($user, ['title' => 'Papan tulis retak', 'status' => 'in_progress']);

        $this->actingAs($user)
            ->get('/siswa/pengaduan?status=in_progress')
            ->assertOk()
            ->assertSee('Papan tulis retak')
            ->assertDontSee('Kursi goyang');
    }

    public function test_siswa_can_search_own_complaints_by_title_and_ticket(): void
    {
        $user = $this->siswa();
        $this->complaintFor($user, [
            'title' => 'AC kelas mati',
            'ticket_number' => 'TKT-20990101-7777',
        ]);
        $this->complaintFor($user, ['title' => 'Lampu koridor mati']);

        $this->actingAs($user)
            ->get('/siswa/pengaduan?q=AC+kelas')
            ->assertOk()
            ->assertSee('AC kelas mati')
            ->assertDontSee('Lampu koridor mati');

        $this->actingAs($user)
            ->get('/siswa/pengaduan?q=7777')
            ->assertOk()
            ->assertSee('AC kelas mati')
            ->assertDontSee('Lampu koridor mati');
    }

    public function test_search_never_reaches_other_students_complaints(): void
    {
        $user = $this->siswa();
        $other = $this->siswa('Siswa Lain');
        $this->complaintFor($user, ['title' => 'Kran wudhu bocor']);
        $this->complaintFor($other, ['title' => 'Proyektor kelas lain rusak']);

        $this->actingAs($user)
            ->get('/siswa/pengaduan?q=Proyektor')
            ->assertOk()
            ->assertDontSee('Proyektor kelas lain rusak')
            // The student's account has complaints, so a search matching none of
            // them lands on the filtered-empty state, not the onboarding card.
            ->assertSee('Tidak ada pengaduan yang cocok');
    }

    public function test_the_status_picker_lists_the_vocabulary_without_counts(): void
    {
        $user = $this->siswa();
        $this->complaintFor($user, ['status' => 'pending']);
        $this->complaintFor($this->siswa('Siswa Lain'), ['status' => 'pending']);

        $this->actingAs($user)
            ->get('/siswa/pengaduan')
            ->assertOk()
            ->assertSee('id="status"', false)
            ->assertSee('Status pengaduan', false)
            // The menu is the shared vocabulary, like the admin menu...
            ->assertSee('&quot;label&quot;:&quot;Menunggu&quot;', false)
            ->assertSee('&quot;label&quot;:&quot;Semua&quot;', false)
            // ...and the list no longer tallies anything: the old chips' count
            // pill and its sr-only unit are gone (the dashboard cards, not the
            // list, are where this student's numbers live).
            ->assertDontSee('— 1 pengaduan', false);
    }

    public function test_the_status_picker_marks_the_applied_status_and_keeps_the_keyword(): void
    {
        $user = $this->siswa();
        $this->complaintFor($user, ['title' => 'Proyektor mati', 'status' => 'pending']);

        $content = $this->actingAs($user)
            ->get('/siswa/pengaduan?status=pending&q=Proyektor')
            ->assertOk()
            ->assertSee('Proyektor mati')
            ->getContent();

        // The applied pick fills the trigger and marks aria-current — the
        // admin picker's own state signalling, read without a chip row.
        $this->assertStringContainsString('aria-current="true"', $content);
        // Neither dimension is dropped by the other: the keyword rides the
        // picker's form, the status rides the search form.
        $this->assertStringContainsString('<input type="hidden" name="q" value="Proyektor" />', $content);
        $this->assertStringContainsString('<input type="hidden" name="status" value="pending" />', $content);
    }

    public function test_unknown_status_filters_nothing_instead_of_erroring(): void
    {
        $user = $this->siswa();
        $this->complaintFor($user, ['title' => 'Kran bocor']);

        $this->actingAs($user)
            ->get('/siswa/pengaduan?status=hack')
            ->assertOk()
            ->assertSee('Kran bocor')
            // No filter was applied, so the "no match" copy must not appear —
            // that copy is reserved for a real filter that found nothing.
            ->assertDontSee('Tidak ada pengaduan yang cocok');
    }

    public function test_a_pending_complaint_can_be_withdrawn_and_its_evidence_is_removed(): void
    {
        Storage::fake('public');
        $user = $this->siswa();
        $complaint = $this->complaintFor($user, [
            'title' => 'AC salah kirim ruangan',
            'evidence_image' => 'complaints/evidence/wrong-upload.jpg',
        ]);
        Storage::disk('public')->put($complaint->evidence_image, 'fake');

        $this->actingAs($user)
            ->delete("/siswa/pengaduan/{$complaint->id}")
            ->assertRedirect('/siswa/pengaduan')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('complaints', ['id' => $complaint->id]);
        Storage::disk('public')->assertMissing($complaint->evidence_image);
    }

    public function test_a_non_pending_complaint_cannot_be_withdrawn(): void
    {
        Storage::fake('public');
        $user = $this->siswa();
        $complaint = $this->complaintFor($user, [
            'status' => 'in_progress',
            'evidence_image' => 'complaints/evidence/keep-me.jpg',
        ]);
        Storage::disk('public')->put($complaint->evidence_image, 'fake');

        $this->actingAs($user)
            ->delete("/siswa/pengaduan/{$complaint->id}")
            ->assertRedirect("/siswa/pengaduan/{$complaint->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id]);
        Storage::disk('public')->assertExists($complaint->evidence_image);
    }

    public function test_only_the_reporter_can_withdraw_a_complaint(): void
    {
        $owner = $this->siswa();
        $intruder = $this->siswa('Penyusup');
        $complaint = $this->complaintFor($owner, ['title' => 'Bukan punya penyusup']);

        $this->actingAs($intruder)
            ->delete("/siswa/pengaduan/{$complaint->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id]);
    }

    public function test_the_withdraw_action_is_offered_only_while_pending(): void
    {
        $user = $this->siswa();
        $pending = $this->complaintFor($user, ['title' => 'Masih menunggu']);
        $inProgress = $this->complaintFor($user, ['title' => 'Sudah ditangani', 'status' => 'in_progress']);

        $this->actingAs($user)
            ->get("/siswa/pengaduan/{$pending->id}")
            ->assertOk()
            ->assertSee('Batalkan pengaduan')
            ->assertSee('confirm-complaint-withdraw-'.$pending->id)
            // Destructive dialog: focus must move INTO it when it opens (the
            // `focusable` x-modal switch compiles to this focus call).
            ->assertSee('firstFocusable().focus()', false);

        $this->actingAs($user)
            ->get("/siswa/pengaduan/{$inProgress->id}")
            ->assertOk()
            ->assertDontSee('Batalkan pengaduan')
            ->assertDontSee('confirm-complaint-withdraw-'.$inProgress->id);
    }

    public function test_filtered_empty_state_differs_from_empty_account(): void
    {
        $user = $this->siswa();
        $this->complaintFor($user, ['status' => 'pending']);

        // With complaints but a filter that matches nothing: the toolbar stays
        // and the copy names the filter, not the account.
        $this->actingAs($user)
            ->get('/siswa/pengaduan?status=resolved')
            ->assertOk()
            ->assertSee('Cari judul')
            ->assertSee('Tidak ada pengaduan yang cocok dengan filter ini');

        // With no complaints at all: the onboarding card, not the filter copy.
        $this->actingAs($this->siswa('Baru'))
            ->get('/siswa/pengaduan')
            ->assertOk()
            ->assertSee('Belum ada pengaduan')
            ->assertDontSee('Cari judul');
    }
}
