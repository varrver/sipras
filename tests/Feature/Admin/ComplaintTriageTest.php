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

class ComplaintTriageTest extends TestCase
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

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    public function test_status_counts_ignore_the_active_filter(): void
    {
        $this->complaint(['status' => 'pending']);
        $this->complaint(['status' => 'pending']);
        $this->complaint(['status' => 'resolved']);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan?status=resolved')
            ->assertOk()
            ->assertViewHas('counts', [
                'total' => 3,
                'pending' => 2,
                'in_progress' => 0,
                'resolved' => 1,
                'rejected' => 0,
            ]);
    }

    public function test_the_status_menu_speaks_the_shared_vocabulary(): void
    {
        $this->complaint(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan')
            ->assertOk()
            // The picker reads the same STATUS_LABELS map as the badge and the
            // dashboard cards, and carries no count: vocabulary only.
            ->assertSee('&quot;label&quot;:&quot;Menunggu&quot;', false)
            ->assertSee('&quot;label&quot;:&quot;Ditolak&quot;', false)
            ->assertDontSee('— 1 pengaduan', false);
    }

    public function test_the_list_is_newest_first(): void
    {
        $this->travel(-1)->days();
        $this->complaint(['title' => 'Pengaduan lama', 'status' => 'pending']);
        $this->travelBack();

        $this->complaint(['title' => 'Pengaduan baru', 'status' => 'resolved']);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan')
            ->assertOk()
            ->assertSeeInOrder(['Pengaduan baru', 'Pengaduan lama']);
    }

    public function test_the_list_links_to_the_detail_page_instead_of_editing_in_place(): void
    {
        $complaint = $this->complaint(['status' => 'pending']);

        $response = $this->actingAs($this->admin())
            ->get('/admin/pengaduan')
            ->assertOk()
            ->assertSee('href="'.route('admin.pengaduan.show', $complaint).'"', false);

        // No status *mutation* on the list at all — no row-level dropdown and
        // no form posting to a /status route. The toolbar's status picker is a
        // GET filter (its no-JS fallback is the <noscript> select), and the
        // detail page remains the only place a status can change.
        $xpath = $this->xpath($response->getContent());

        $this->assertSame(0, $xpath->query('//form[contains(@action, "/status")]')->length);
        $this->assertSame(0, $xpath->query('//select[@name="status" and not(ancestor::noscript)]')->length);
    }

    public function test_the_mobile_card_is_structured_content_behind_one_stretched_link(): void
    {
        $complaint = $this->complaint(['title' => 'Proyektor mati']);

        $response = $this->actingAs($this->admin())
            ->get('/admin/pengaduan')
            ->assertOk();

        $xpath = $this->xpath($response->getContent());

        // The mobile list is the `xl:hidden` block (the admin table needs the
        // full xl width its sidebar leaves). It holds exactly one link, and
        // that link carries only the title, so assistive tech reads the ticket,
        // status and meta as content instead of one run-on link name.
        $links = $xpath->query('//div[contains(@class, "xl:hidden")]//a');

        $this->assertSame(1, $links->length);

        $link = $links->item(0);

        $this->assertSame('Proyektor mati', trim($link->textContent));
        $this->assertStringNotContainsString($complaint->ticket_number, $link->textContent);

        // The whole card stays a single tap target: the link's `::after` is
        // stretched over the card.
        $this->assertStringContainsString('after:absolute', $link->getAttribute('class'));
        $this->assertStringContainsString('after:inset-0', $link->getAttribute('class'));

        // The ticket is still present as content, not lost with the link name.
        $mobile = $xpath->query('//div[contains(@class, "xl:hidden")]')->item(0);

        $this->assertStringContainsString($complaint->ticket_number, $mobile->textContent);
    }

    public function test_a_resolved_complaint_offers_the_proof_attachment(): void
    {
        $complaint = $this->complaint(['status' => 'resolved']);

        $response = $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee("formStatus: 'resolved'", false)
            ->assertSee('Dokumentasi Perbaikan');

        $xpath = $this->xpath($response->getContent());

        $input = $xpath->query('//input[@id="resolved_image"]')->item(0);

        $this->assertNotNull($input);
        // Reachable before Alpine boots, or the photo could not be attached.
        $this->assertNotSame('display: none', trim($input->parentNode->getAttribute('style')));

        $textarea = $xpath->query('//textarea[@id="admin_notes"]')->item(0);

        $this->assertNotNull($textarea);
        $this->assertFalse($textarea->hasAttribute('required'));
    }

    public function test_a_resolved_complaint_without_a_timestamp_still_renders(): void
    {
        // The write path always stamps `resolved_at`, but a factory, an import,
        // or a direct write can leave it unset. The detail page must degrade
        // gracefully rather than 500.
        $complaint = $this->complaint(['status' => 'resolved', 'resolved_at' => null]);

        $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            // The resolved-without-timestamp page names itself by the record
            // it renders, and "Selesai pada" stays out of a ticket that was
            // never stamped.
            ->assertSee($complaint->title)
            ->assertDontSee('Selesai pada');
    }

    public function test_a_rejected_complaint_requires_a_reason_on_the_detail_page(): void
    {
        $complaint = $this->complaint(['status' => 'rejected', 'admin_notes' => 'Bukan kewenangan sekolah.']);

        $response = $this->actingAs($this->admin())
            ->get("/admin/pengaduan/{$complaint->id}")
            ->assertOk()
            ->assertSee("formStatus: 'rejected'", false)
            ->assertSee('Alasan Penolakan');

        $textarea = $this->xpath($response->getContent())
            ->query('//textarea[@id="admin_notes"]')
            ->item(0);

        $this->assertNotNull($textarea);
        $this->assertTrue($textarea->hasAttribute('required'));
    }

    public function test_pagination_renders_localized_indonesian_strings_on_mobile(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->complaint();
        }

        $response = $this->actingAs($this->admin())
            ->get('/admin/pengaduan')
            ->assertOk();

        // The paginator renders unified compact buttons with chevrons and page numbers.
        // It must never leak the raw translation keys `pagination.previous` / `pagination.next`.
        $response->assertDontSee('pagination.next');
        $response->assertDontSee('pagination.previous');
        $response->assertSee('aria-label="Navigasi halaman"', false);
    }
}
