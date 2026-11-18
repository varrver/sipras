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

class MasterDataTest extends TestCase
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

    public function test_admin_can_list_categories(): void
    {
        Category::factory()->create(['name' => 'Elektronik']);

        $this->actingAs($this->admin())
            ->get('/admin/master/categories')
            ->assertOk()
            ->assertSee('Elektronik');
    }

    public function test_admin_can_create_category_with_name_only(): void
    {
        // The slug column was dropped: a category carries nothing but its name.
        $response = $this->actingAs($this->admin())
            ->post('/admin/master/categories', ['name' => 'Elektronik']);

        $response->assertRedirect('/admin/master/categories');
        $this->assertDatabaseHas('categories', ['name' => 'Elektronik']);
    }

    public function test_category_name_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/master/categories', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_category_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Elektronik']);

        $this->actingAs($this->admin())
            ->post('/admin/master/categories', ['name' => 'elektronik'])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Elektronik']);

        $this->actingAs($this->admin())
            ->put("/admin/master/categories/{$category->id}", ['name' => 'Elektronik & IT'])
            ->assertRedirect('/admin/master/categories');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Elektronik & IT']);
    }

    public function test_admin_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin())
            ->delete("/admin/master/categories/{$category->id}")
            ->assertRedirect('/admin/master/categories');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_deleting_a_category_in_use_by_a_complaint_is_blocked(): void
    {
        $complaint = Complaint::factory()->create();
        $category = $complaint->category;

        $this->actingAs($this->admin())
            ->delete("/admin/master/categories/{$category->id}")
            ->assertRedirect('/admin/master/categories')
            ->assertSessionHas('error', 'Kategori tidak dapat dihapus karena masih dipakai pengaduan.');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_deleting_a_room_in_use_by_a_complaint_is_blocked(): void
    {
        $complaint = Complaint::factory()->create();
        $room = $complaint->room;

        $this->actingAs($this->admin())
            ->delete("/admin/master/rooms/{$room->id}")
            ->assertRedirect('/admin/master/rooms')
            ->assertSessionHas('error', 'Ruangan tidak dapat dihapus karena masih dipakai pengaduan.');

        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_admin_can_list_rooms(): void
    {
        Room::factory()->create(['name' => 'Lab Komputer 1', 'code' => 'LAB-01']);

        $this->actingAs($this->admin())
            ->get('/admin/master/rooms')
            ->assertOk()
            ->assertSee('LAB-01');
    }

    public function test_admin_can_create_room(): void
    {
        $response = $this->actingAs($this->admin())
            ->post('/admin/master/rooms', ['name' => 'Lab Komputer 1', 'code' => 'LAB-01']);

        $response->assertRedirect('/admin/master/rooms');
        $this->assertDatabaseHas('rooms', ['name' => 'Lab Komputer 1', 'code' => 'LAB-01']);
    }

    public function test_room_fields_are_required(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/master/rooms', ['name' => '', 'code' => ''])
            ->assertSessionHasErrors(['name', 'code']);
    }

    public function test_room_code_must_be_unique(): void
    {
        Room::factory()->create(['code' => 'LAB-01']);

        $this->actingAs($this->admin())
            ->post('/admin/master/rooms', ['name' => 'Lab Lain', 'code' => 'LAB-01'])
            ->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_room(): void
    {
        $room = Room::factory()->create(['name' => 'Lab Komputer 1', 'code' => 'LAB-01']);

        $this->actingAs($this->admin())
            ->put("/admin/master/rooms/{$room->id}", ['name' => 'Lab Komputer Utama', 'code' => 'LAB-01'])
            ->assertRedirect('/admin/master/rooms');

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'name' => 'Lab Komputer Utama']);
    }

    public function test_every_master_data_table_is_named(): void
    {
        Category::factory()->create();
        Room::factory()->create();
        User::factory()->create();

        $names = [];

        foreach (['/admin/master/categories', '/admin/master/rooms', '/admin/master/users'] as $path) {
            $response = $this->actingAs($this->admin())->get($path)->assertOk();

            $dom = new DOMDocument;
            libxml_use_internal_errors(true);
            $dom->loadHTML($response->getContent());
            libxml_clear_errors();

            $captions = (new DOMXPath($dom))->query('//table/caption');

            // A table with no name is announced as "table" and cannot be
            // jumped to; the caption names it for AT without a visible row.
            $this->assertSame(1, $captions->length, "{$path} should name its table");
            $this->assertStringContainsString('sr-only', $captions->item(0)->getAttribute('class'));
            $this->assertNotSame('', trim($captions->item(0)->textContent));

            $names[] = trim($captions->item(0)->textContent);
        }

        // The three names have to be distinguishable from each other.
        $this->assertSame($names, array_values(array_unique($names)));
    }

    public function test_siswa_cannot_access_master_data(): void
    {
        $this->actingAs($this->siswa())
            ->get('/admin/master/categories')
            ->assertForbidden();
    }
}
