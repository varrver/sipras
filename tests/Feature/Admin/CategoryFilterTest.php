<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function complaint(User $reporter, Category $category, string $title, array $attributes = []): Complaint
    {
        return Complaint::factory()
            ->for($reporter, 'user')
            ->for(Room::factory()->create(), 'room')
            ->create([
                'title' => $title,
                'category_id' => $category->id,
                ...$attributes,
            ]);
    }

    public function test_resolve_category_only_accepts_existing_integer_ids(): void
    {
        $category = Category::factory()->create();
        $resolved = function (string $qs) {
            return Complaint::resolveCategory(Request::create('/admin/pengaduan?'.$qs));
        };

        $this->assertSame($category->id, $resolved('category='.$category->id));
        $this->assertSame($category->id, $resolved('category='.(string) $category->id));
        $this->assertNull($resolved(''));                       // missing → no filters
        $this->assertNull($resolved('category='));              // empty → no filters
        $this->assertNull($resolved('category=abc'));           // non-numeric → no filters
        $this->assertNull($resolved('category=99'));            // never created → no filters
        $this->assertNull($resolved('category=abc99'));         // junk-suffixed → no filters
        $this->assertNull($resolved('category%5B0%5D=1'));      // array injection → no filters
        Category::query()->find($category->id)->deleteQuietly();
        $this->assertNull($resolved('category='.$category->id)); // since-deleted → no filters
    }

    public function test_the_picker_lists_every_category(): void
    {
        Category::factory()->create(['name' => 'Listrik']);
        Category::factory()->create(['name' => 'Perpipaan']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.pengaduan.index'));

        $response->assertSee('Listrik')
            ->assertSee('Perpipaan');
    }

    public function test_the_list_narrows_to_the_picked_category(): void
    {
        $admin = $this->admin();
        $catA = Category::factory()->create(['name' => 'AKatX']);
        $catB = Category::factory()->create(['name' => 'BKatX']);

        $this->complaint($admin, $catA, 'MilikAKatX');
        $this->complaint($admin, $catB, 'MilikBKatX');

        $response = $this->actingAs($admin)
            ->get(route('admin.pengaduan.index', ['category' => $catA->id]));

        $response->assertSee('MilikAKatX')
            ->assertDontSee('MilikBKatX');
    }

    public function test_junk_category_urls_filter_nothing_and_never_error(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();

        $this->complaint($admin, $category, 'Barang tetap ada');

        foreach ([
            'category=abc',
            'category=9999',
            'category%5B0%5D=abc',
            'category=',
            'category=abc&from=not-a-date',
            'category=9999&q=Barang%20tetap%20ada',
        ] as $qs) {
            $this->actingAs($admin)
                ->get('/admin/pengaduan?'.$qs)
                ->assertOk()
                ->assertSee('Barang tetap ada');
        }
    }

    public function test_status_counts_live_inside_the_category(): void
    {
        $admin = $this->admin();
        $catA = Category::factory()->create();
        $catB = Category::factory()->create();

        $this->complaint($admin, $catA, 'Satu', ['status' => 'pending']);
        $this->complaint($admin, $catA, 'Dua', ['status' => 'pending']);
        $this->complaint($admin, $catA, 'Tiga', ['status' => 'resolved', 'resolved_at' => now()]);
        $this->complaint($admin, $catB, 'Empat', ['status' => 'pending']);

        // The picker no longer prints counts, but the view data still scopes
        // them to the category the list is showing.
        $this->actingAs($admin)
            ->get(route('admin.pengaduan.index', ['category' => $catA->id]))
            ->assertOk()
            ->assertViewHas('counts', [
                'total' => 3,
                'pending' => 2,
                'in_progress' => 0,
                'resolved' => 1,
                'rejected' => 0,
            ]);

        $this->actingAs($admin)
            ->get(route('admin.pengaduan.index'))
            ->assertOk()
            ->assertViewHas('counts', [
                'total' => 4,
                'pending' => 3,
                'in_progress' => 0,
                'resolved' => 1,
                'rejected' => 0,
            ]);
    }

    public function test_chips_search_and_cetak_carry_the_category(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();
        $this->complaint($admin, $category, 'Rantai filter');

        $content = $this->actingAs($admin)
            ->get(route('admin.pengaduan.index', ['category' => $category->id]))
            ->getContent();

        // The dimension forward-propagates through the whole toolbar,
        // exactly like q/status/from/to always have: the search form's
        // hidden input, the period links, the status picker's form, and the
        // Cetak link all name it.
        $this->assertStringContainsString('name="category" value="'.$category->id.'"', $content);
        $this->assertStringContainsString('category='.$category->id, $content);
    }

    public function test_the_report_follows_the_same_category_filter(): void
    {
        $admin = $this->admin();
        $catA = Category::factory()->create(['name' => 'AKatX']);
        $catB = Category::factory()->create(['name' => 'BKatX']);

        $this->complaint($admin, $catA, 'LaporanAKatX');
        $this->complaint($admin, $catB, 'LaporanBKatX');

        $response = $this->actingAs($admin)
            ->get(route('admin.pengaduan.report', ['category' => $catA->id]));

        $response->assertSee('LaporanAKatX')
            ->assertDontSee('LaporanBKatX');

        // The recap's meta line names the active window in the same
        // breath as the period: the sheet cannot print a window its
        // filter did not select.
        $response->assertSee('kategori AKatX', false)
            ->assertDontSee('kategori BKatX', false);

        $pdf = $this->actingAs($admin)
            ->get(route('admin.pengaduan.report-pdf', ['category' => $catA->id]));

        $pdf->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_pagination_carries_the_category(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();

        $this->complaint($admin, $category, 'Page one edge');
        for ($i = 0; $i < 11; $i++) {
            $this->complaint($admin, $category, 'Filler row '.$i);
        }

        $content = $this->actingAs($admin)
            ->get(route('admin.pengaduan.index', ['category' => $category->id]))
            ->getContent();

        $this->assertStringContainsString('category='.$category->id.'&amp;page=2', $content);
    }
}
