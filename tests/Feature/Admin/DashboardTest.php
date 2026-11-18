<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_status_counts_zero_fill_every_status(): void
    {
        $this->assertSame([
            'total' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'resolved' => 0,
            'rejected' => 0,
        ], Complaint::statusCounts());
    }

    public function test_status_counts_are_ints_even_when_a_status_is_empty(): void
    {
        $this->complaint(['status' => 'pending']);
        $this->complaint(['status' => 'pending']);
        $this->complaint(['status' => 'resolved']);

        $counts = Complaint::statusCounts();

        $this->assertSame(3, $counts['total']);
        $this->assertSame(2, $counts['pending']);
        $this->assertSame(1, $counts['resolved']);
        $this->assertSame(0, $counts['in_progress']);
        $this->assertSame(0, $counts['rejected']);

        foreach ($counts as $value) {
            $this->assertIsInt($value);
        }
    }

    public function test_dashboard_and_list_agree_on_counts(): void
    {
        $this->complaint(['status' => 'pending']);
        $this->complaint(['status' => 'in_progress']);
        $this->complaint(['status' => 'resolved']);
        $this->complaint(['status' => 'rejected']);

        $expected = Complaint::statusCounts();

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertViewHas('counts', $expected);

        $this->actingAs($this->admin())
            ->get('/admin/pengaduan')
            ->assertOk()
            ->assertViewHas('counts', $expected);
    }

    public function test_dashboard_cards_link_to_the_matching_list_filter(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('href="'.route('admin.pengaduan.index').'"', false)
            ->assertSee('href="'.route('admin.pengaduan.index', ['status' => 'pending']).'"', false)
            ->assertSee('href="'.route('admin.pengaduan.index', ['status' => 'in_progress']).'"', false)
            ->assertSee('href="'.route('admin.pengaduan.index', ['status' => 'resolved']).'"', false)
            ->assertSee('href="'.route('admin.pengaduan.index', ['status' => 'rejected']).'"', false);
    }

    public function test_a_card_announces_its_count_in_its_accessible_name(): void
    {
        $this->complaint(['status' => 'pending']);
        $this->complaint(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('— 2 pengaduan', false);
    }

    public function test_dashboard_still_shows_recent_complaints_and_the_report_button(): void
    {
        $complaint = $this->complaint(['title' => 'AC ruang kelas rusak']);

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('AC ruang kelas rusak')
            ->assertSee($complaint->ticket_number)
            ->assertSee('Cetak Laporan');
    }
}
