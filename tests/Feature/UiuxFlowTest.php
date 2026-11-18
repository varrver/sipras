<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * The flow contract: no page dead-ends. Every surface hands the user its
 * next step — in-page returns, first-run CTAs, and the created-ticket
 * hand-off. Where an old assumption from the audit was wrong (details had
 * icon-only returns; the report already linked back), the test pins the
 * TRUE bar: visible text, filter-carrying links, real next steps.
 */
class UiuxFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function teknisi(string $status = 'pending'): array
    {
        $category = Category::factory()->create();
        $user = User::factory()->teknisi($category->id)->create();
        $complaint = Complaint::factory()->create(['status' => $status, 'category_id' => $category->id]);

        return [$user, $complaint];
    }

    public function test_each_role_detail_carries_a_named_return(): void
    {
        [$teknisiUser, $complaint] = $this->teknisi();

        $siswa = User::factory()->create(['role' => 'siswa']);
        $owned = Complaint::factory()->create(['user_id' => $siswa->id]);

        // The return is icon-only, so the named destination lives in the
        // accessible name — and must NOT also be printed as link text.
        foreach ([
            [$teknisiUser, "/teknisi/pengaduan/{$complaint->id}", 'Kembali ke antrean'],
            [$siswa, "/siswa/pengaduan/{$owned->id}", 'Kembali ke pengaduan saya'],
            [$this->admin(), "/admin/pengaduan/{$owned->id}", 'Kembali ke daftar'],
        ] as [$user, $url, $label]) {
            $content = $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString('aria-label="'.$label.'"', $content);
            $this->assertStringNotContainsString('>'.$label.'<', $content,
                'The return is icon-only: its destination must not be printed as text.');
        }
    }

    public function test_the_report_return_carries_the_active_filters(): void
    {
        $admin = $this->admin();
        $query = ['status' => 'pending', 'q' => 'proyektor', 'category' => 999, 'from' => '2026-01-01', 'to' => '2026-01-31'];

        $content = $this->actingAs($admin)
            ->get('/admin/pengaduan/laporan?'.http_build_query($query))
            ->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/href="[^"]*\/admin\/pengaduan\?[^"]*to=2026-01-31[^"]*"/',
            $content,
            'The report Kembali link must carry the same window to the list.',
        );
    }

    public function test_a_first_run_student_is_handed_the_create_form(): void
    {
        $content = $this->actingAs(User::factory()->create(['role' => 'siswa']))
            ->get('/siswa/pengaduan')
            ->assertOk()->getContent();

        $this->assertStringContainsString('Buat Pengaduan Pertama', $content);
        $this->assertStringContainsString('/siswa/pengaduan/create', $content);
        $this->assertStringNotContainsString('Tidak ada pengaduan yang cocok', $content);
    }

    public function test_a_filtered_empty_list_keeps_the_reset_hand(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        // The student owns one complaint, so first-run never shows — the miss
        // is the filter's doing, not the account's blank slate.
        Complaint::factory()->create(['user_id' => $siswa->id, 'title' => 'Proyektor mati']);

        $content = $this->actingAs($siswa)
            ->get('/siswa/pengaduan?q=inilah-yang-tidak-cocok')
            ->assertOk()->getContent();

        $this->assertStringContainsString('Tidak ada pengaduan yang cocok', $content);
        $this->assertStringNotContainsString('Buat pengaduan pertama', $content);
    }

    public function test_a_created_ticket_is_handed_to_the_list_highlighted(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $payload = [
            'title' => 'Uji hand-off',
            'description' => 'Kipas angin laboratorium mati.',
            'category_id' => Category::factory()->create()->id,
            'room_id' => Room::factory()->create()->id,
            'evidence_image' => $this->validJpeg(),
        ];

        $response = $this->actingAs($siswa)->post('/siswa/pengaduan', $payload);
        $ticket = Complaint::where('title', 'Uji hand-off')->value('ticket_number');

        $response->assertRedirect(route('siswa.pengaduan.index', ['created' => $ticket]));

        $content = $this->actingAs($siswa)
            ->get(route('siswa.pengaduan.index', ['created' => $ticket]))
            ->assertOk()->getContent();
        $this->assertStringContainsString('ring-2 ring-primary', $content);
    }

    public function test_the_student_list_follows_the_shared_table_pattern(): void
    {
        // Staff-table symmetry: a named table (sr-only caption) with the
        // student-scoped column set, rendered as the siswa surface of the
        // same pattern the admin/teknisi lists speak.
        $siswa = User::factory()->create(['role' => 'siswa']);
        Complaint::factory()->create(['user_id' => $siswa->id]);

        $content = $this->actingAs($siswa)
            ->get('/siswa/pengaduan')
            ->assertOk()->getContent();

        $this->assertStringContainsString('Daftar pengaduan saya', $content);
        foreach (['Status', 'Tipe kerusakan', 'Ruang', 'Keluhan', 'Tiket', 'Tanggal', 'Aksi'] as $header) {
            $this->assertStringContainsString($header, $content);
        }
    }

    public function test_an_unknown_created_ticket_renders_harmlessly(): void
    {
        $content = $this->actingAs(User::factory()->create(['role' => 'siswa']))
            ->get('/siswa/pengaduan?created=TKT-19700101-0000')
            ->assertOk()->getContent();

        $this->assertStringNotContainsString('ring-2 ring-primary', $content);
    }

    public function test_master_form_pages_return_to_their_index(): void
    {
        $admin = $this->admin();
        $categoryId = Category::factory()->create()->id;
        $roomId = Room::factory()->create()->id;
        $userId = User::factory()->create()->id;

        foreach ([
            ['/admin/master/categories/create', '/admin/master/categories'],
            ["/admin/master/categories/$categoryId/edit", '/admin/master/categories'],
            ['/admin/master/rooms/create', '/admin/master/rooms'],
            ["/admin/master/rooms/$roomId/edit", '/admin/master/rooms'],
            ['/admin/master/users/create', '/admin/master/users'],
            ["/admin/master/users/$userId/edit", '/admin/master/users'],
        ] as [$url, $index]) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('Kembali')
                ->assertSee($index);
        }
    }

    private function validJpeg(): UploadedFile
    {
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8QH//EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQIBAT8QH//EABQQAQAAAAAAAAAAAAAAAAAAABD/2gAIAQEAAT8QH//Z');

        return UploadedFile::fake()->createWithContent('bukti.jpg', $jpeg);
    }
}
