<?php

namespace Tests\Feature\Teknisi;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * The teknisi surface's workflow invariants: a guarded arm of its own, a
 * queue that speaks the list grammar with no status controls, and a detail
 * page whose three transitions run through the same shared contract every
 * other surface uses — proof required when finishing, justification required
 * when rejecting, final states closed.
 */
class TeknisiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Category $field;

    protected function setUp(): void
    {
        parent::setUp();

        // Every complaint this suite touches belongs to the one field the
        // technician covers, so the specialization boundary never blocks a
        // transition under test (that boundary is TeknisiSpecializationTest's).
        $this->field = Category::factory()->create();
    }

    private function validJpeg(): UploadedFile
    {
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8QH//EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQIBAT8QH//EABQQAQAAAAAAAAAAAAAAAAAAABD/2gAIAQEAAT8QH//Z');

        return UploadedFile::fake()->createWithContent('bukti.jpg', $jpeg);
    }

    private function teknisi(): User
    {
        return User::factory()->teknisi($this->field->id)->create();
    }

    private function complaint(string $status = 'pending'): Complaint
    {
        return Complaint::factory()->create(['status' => $status, 'category_id' => $this->field->id]);
    }

    // The role boundary ---------------------------------------------------

    public function test_teknisi_reaches_their_queue_and_detail(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->teknisi())
            ->get(route('teknisi.pengaduan.index'))
            ->assertOk()
            ->assertSee('Pengaduan Teknisi', false)
            ->assertSee($complaint->ticket_number, false);

        $this->get(route('teknisi.pengaduan.show', $complaint))
            ->assertOk()
            ->assertSee($complaint->title, false);
    }

    public function test_reaching_their_detail_leaves_the_rail_unpinned(): void
    {
        // The action card once pinned itself while travelling inside the rail:
        // a sticky element may roam its containing block, so it slid over the
        // timeline and ticket-info cards below it on scroll. The pin pair
        // stays absent so the overlap cannot quietly return.
        $complaint = $this->complaint();

        $this->actingAs($this->teknisi())
            ->get(route('teknisi.pengaduan.show', $complaint))
            ->assertOk()
            ->assertDontSee('lg:sticky lg:top-20', false);
    }

    public function test_teknisi_is_refused_at_every_admin_and_siswa_address(): void
    {
        $teknisi = $this->teknisi();

        // Admin arm: dashboard, master data (incl. the CSV export), report,
        // complaint management.
        foreach (['/admin/dashboard', '/admin/pengaduan', '/admin/pengaduan/laporan', '/admin/master/users', '/admin/master/users/export'] as $address) {
            $this->actingAs($teknisi)->get($address)->assertForbidden();
        }

        // Siswa arm: dashboard, own list, complaint creation.
        foreach (['/siswa/dashboard', '/siswa/pengaduan', '/siswa/pengaduan/create'] as $address) {
            $this->actingAs($teknisi)->get($address)->assertForbidden();
        }
    }

    public function test_admin_and_siswa_cannot_reach_the_teknisi_surface(): void
    {
        $complaint = $this->complaint();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('teknisi.pengaduan.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'siswa']))
            ->get(route('teknisi.pengaduan.show', $complaint))
            ->assertForbidden();
    }

    public function test_guests_are_refused_at_the_teknisi_surface(): void
    {
        $this->get(route('teknisi.pengaduan.index'))->assertRedirect('/login');
    }

    // The queue -----------------------------------------------------------

    public function test_the_queue_orders_newest_first(): void
    {
        // Two distinct instants: a minute apart, so `latest()` has a head.
        $older = $this->complaint();
        $newer = $this->complaint();
        $newer->forceFill(['created_at' => now()->addMinutes(5)])->save();

        $content = $this->actingAs($this->teknisi())
            ->get(route('teknisi.pengaduan.index'))
            ->assertOk()
            ->getContent();

        $firstSeen = mb_strpos($content, $newer->ticket_number);
        $laterSeen = mb_strpos($content, $older->ticket_number);

        $this->assertNotFalse($firstSeen);
        $this->assertNotFalse($laterSeen);
        $this->assertLessThan($laterSeen, $firstSeen, 'the queue must read newest-first');
    }

    public function test_the_queue_searches_like_the_admin_list(): void
    {
        $this->complaint();

        $this->actingAs($this->teknisi())
            ->get(route('teknisi.pengaduan.index', ['q' => 'nomatch-xyz']))
            ->assertOk()
            ->assertSee('Tidak ada pengaduan yang cocok', false);
    }

    public function test_an_unknown_status_filters_nothing_instead_of_erroring(): void
    {
        $complaint = $this->complaint();

        $this->actingAs($this->teknisi())
            ->get(route('teknisi.pengaduan.index', ['status' => 'hack']))
            ->assertOk()
            ->assertSee($complaint->title, false)
            ->assertDontSee('Tidak ada pengaduan yang cocok');
    }

    public function test_the_technisi_list_carries_no_status_change_control(): void
    {
        $this->complaint();

        $content = $this->actingAs($this->teknisi())
            ->get(route('teknisi.pengaduan.index'))
            ->assertOk()
            ->getContent();

        // The admin-list invariant, mirrored for the teknisi arm: no status
        // CHANGE control or PATCH target on any row, and none of the admin
        // arm's print capability. The queue's own status field is the filter
        // picker's GET input (`name="status"`), so the mutation is refused by
        // its markers instead: no CSRF `_method` spoof and no `/status` URL.
        $this->assertStringNotContainsString('name="_method"', $content);
        $this->assertStringNotContainsString('/status"', $content);
        $this->assertStringNotContainsString('Cetak', $content);
    }

    // The three transitions ----------------------------------------------

    public function test_teknisi_starts_work_on_a_pending_complaint(): void
    {
        $complaint = $this->complaint('pending');

        $this->actingAs($this->teknisi())
            ->patch(route('teknisi.pengaduan.status', $complaint), ['status' => 'in_progress'])
            ->assertRedirect();

        $this->assertSame('in_progress', $complaint->fresh()->status);
    }

    public function test_teknisi_finishes_with_the_required_proof(): void
    {
        $complaint = $this->complaint('in_progress');

        $this->actingAs($this->teknisi())
            ->patch(route('teknisi.pengaduan.status', $complaint), ['status' => 'resolved'])
            ->assertSessionHasErrors('resolved_image');

        $response = $this->actingAs($this->teknisi())
            ->patch(route('teknisi.pengaduan.status', $complaint), [
                'status' => 'resolved',
                'resolved_image' => $this->validJpeg(),
            ])
            ->assertRedirect();

        $complaint->refresh();
        $this->assertSame('resolved', $complaint->status);
        $this->assertNotNull($complaint->resolved_at);
        $this->assertNotNull($complaint->resolved_image);
        \Storage::disk('public')->assertExists($complaint->resolved_image);
    }

    public function test_the_proof_photo_rejects_non_images(): void
    {
        $complaint = $this->complaint('in_progress');

        $pdf = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');

        $this->actingAs($this->teknisi())
            ->patch(route('teknisi.pengaduan.status', $complaint), [
                'status' => 'resolved',
                'resolved_image' => $pdf,
            ])
            ->assertSessionHasErrors('resolved_image');
    }

    public function test_teknisi_rejects_with_the_required_justification(): void
    {
        $complaint = $this->complaint('pending');

        $this->actingAs($this->teknisi())
            ->patch(route('teknisi.pengaduan.status', $complaint), ['status' => 'rejected'])
            ->assertSessionHasErrors('admin_notes');

        $this->actingAs($this->teknisi())
            ->patch(route('teknisi.pengaduan.status', $complaint), [
                'status' => 'rejected',
                'admin_notes' => 'Kerusakan bukan pada sarana sekolah.',
            ])
            ->assertRedirect();

        $complaint->refresh();
        $this->assertSame('rejected', $complaint->status);
        $this->assertSame('Kerusakan bukan pada sarana sekolah.', $complaint->admin_notes);
    }

    // The shared contract --------------------------------------------------

    public function test_a_flip_out_of_a_final_state_is_a_validation_error(): void
    {
        $resolved = $this->complaint('resolved');

        $this->actingAs($this->teknisi())
            ->patch(route('teknisi.pengaduan.status', $resolved), ['status' => 'in_progress'])
            ->assertSessionHasErrors('status');

        $this->assertSame('resolved', $resolved->fresh()->status);
    }

    public function test_the_admin_surface_refuses_the_same_flip(): void
    {
        $resolved = $this->complaint('resolved');

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.pengaduan.status', $resolved), ['status' => 'in_progress'])
            ->assertSessionHasErrors('status');

        $this->assertSame('resolved', $resolved->fresh()->status);
    }

    public function test_the_admin_re_save_of_a_resolved_complaint_still_works(): void
    {
        // The established admin flow: a proof typo is re-attached on an
        // already resolved complaint — same-status, so the contract allows it
        // and the resolved_at stamp must NOT move.
        $complaint = $this->complaint('resolved');
        $complaint->forceFill(['resolved_at' => now()])->save();
        $stamp = $complaint->fresh()->resolved_at;
        $this->assertNotNull($stamp);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.pengaduan.status', $complaint), ['status' => 'resolved'])
            ->assertRedirect();

        $this->assertSame('resolved', $complaint->fresh()->status);
        $this->assertEquals($stamp->toIso8601String(), $complaint->fresh()->resolved_at->toIso8601String());
    }

    public function test_the_technisi_detail_hides_the_status_form_once_final(): void
    {
        $resolved = $this->complaint('resolved');

        $content = $this->actingAs($this->teknisi())
            ->get(route('teknisi.pengaduan.show', $resolved))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Perbarui Status', $content);
    }
}
