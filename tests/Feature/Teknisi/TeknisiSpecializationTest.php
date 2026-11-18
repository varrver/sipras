<?php

namespace Tests\Feature\Teknisi;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The specialization's invariants: a technician sees and works only their
 * assigned field, the master-data round-trip carries the assignment with the
 * same all-or-nothing spirit as every other constraint, and an unstaffed
 * category queues for nobody — the admin stays the net.
 */
class TeknisiSpecializationTest extends TestCase
{
    use RefreshDatabase;

    private Category $fieldA;

    private Category $fieldB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldA = Category::factory()->create(['name' => 'Listrik']);
        $this->fieldB = Category::factory()->create(['name' => 'Sanitasi']);
    }

    // Assignment ----------------------------------------------------------

    public function test_creating_a_teknisi_without_a_specialty_refuses(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.master.users.store'), [
                'name' => 'Juru Listrik',
                'email' => 'juru@smkn5.test',
                'password' => 'rahasia-9876',
                'password_confirmation' => 'rahasia-9876',
                'role' => 'teknisi',
            ])
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('users', ['email' => 'juru@smkn5.test']);
    }

    public function test_creating_a_teknisi_assigns_the_specialty(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.master.users.store'), [
                'name' => 'Juru Listrik',
                'email' => 'juru@smkn5.test',
                'password' => 'rahasia-9876',
                'password_confirmation' => 'rahasia-9876',
                'role' => 'teknisi',
                'category_id' => $this->fieldA->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.master.users.index'));

        $user = User::whereEmail('juru@smkn5.test')->first();
        $this->assertSame('teknisi', $user->role);
        $this->assertSame($this->fieldA->id, $user->category_id);
    }

    public function test_a_stray_category_under_a_non_technician_role_is_nulled(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.master.users.store'), [
                'name' => 'Siwa Rugin',
                'email' => 'siswa@smkn5.test',
                'role' => 'siswa',
                'category_id' => $this->fieldA->id,
                'password' => 'rahasia-9876',
                'password_confirmation' => 'rahasia-9876',
            ])
            ->assertRedirect(route('admin.master.users.index'));

        $this->assertNull(User::whereEmail('siswa@smkn5.test')->first()->category_id);
    }

    public function test_edit_page_shows_the_stored_specialty(): void
    {
        $teknisi = User::factory()->teknisi($this->fieldA->id)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.master.users.edit', $teknisi))
            ->assertOk()
            ->assertSee((string) $this->fieldA->name, false);
    }

    // The scoped queue ----------------------------------------------------

    public function test_the_queue_lists_only_the_technician_s_specialty(): void
    {
        $teknisi = User::factory()->teknisi($this->fieldA->id)->create();

        $mine = Complaint::factory()->create(['category_id' => $this->fieldA->id]);
        $foreign = Complaint::factory()->create(['category_id' => $this->fieldB->id]);

        $content = $this->actingAs($teknisi)
            ->get(route('teknisi.pengaduan.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($mine->ticket_number, $content);
        $this->assertStringNotContainsString($foreign->ticket_number, $content);
    }

    public function test_the_technitian_queue_omits_unstaffed_foreign_work(): void
    {
        $teknisi = User::factory()->teknisi($this->fieldA->id)->create();

        // A second teknisi covers field B; the complaint sits under NEITHER
        // technician's field because it belongs to an UNSTAFFED category.
        User::factory()->teknisi($this->fieldB->id)->create();
        $orphan = Complaint::factory()->create([
            'category_id' => Category::factory()->create(['name' => 'Tanpa juru'])->id,
        ]);

        $content = $this->actingAs($teknisi)->get(route('teknisi.pengaduan.index'))->getContent();

        $this->assertStringNotContainsString($orphan->ticket_number, $content);
    }

    public function test_the_queue_has_no_category_picker(): void
    {
        // D2's blade rule: the scoped queue drops the picker entirely.
        $teknisi = User::factory()->teknisi($this->fieldA->id)->create();

        $content = $this->actingAs($teknisi)
            ->get(route('teknisi.pengaduan.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Tipe kerusakan', $content);
        $this->assertStringNotContainsString('Semua kategori', $content);
    }

    // Refusals ------------------------------------------------------------

    public function test_the_detail_of_a_foreign_complaint_refuses(): void
    {
        $teknisi = User::factory()->teknisi($this->fieldA->id)->create();
        $foreign = Complaint::factory()->create(['category_id' => $this->fieldB->id]);

        // The localized 403 error card — the app renders its own fixed
        // message (never the abort's details), so assert the card, not the
        // controller's message.
        $this->actingAs($teknisi)
            ->get(route('teknisi.pengaduan.show', $foreign))
            ->assertForbidden()
            ->assertSee('Tidak punya akses', false);
    }

    public function test_the_status_mutation_of_a_foreign_complaint_refuses(): void
    {
        $teknisi = User::factory()->teknisi($this->fieldA->id)->create();
        $foreign = Complaint::factory()->create(['category_id' => $this->fieldB->id]);

        $this->actingAs($teknisi)
            ->patch(route('teknisi.pengaduan.status', $foreign), ['status' => 'in_progress'])
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $foreign->fresh()->status);
    }

    public function test_admin_still_works_every_category(): void
    {
        $admin = User::factory()->admin()->create();
        $foreign = Complaint::factory()->create(['category_id' => $this->fieldB->id]);

        $this->actingAs($admin)
            ->patch(route('admin.pengaduan.status', $foreign), ['status' => 'in_progress'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('in_progress', $foreign->fresh()->status);
    }

    // The deletion guard ----------------------------------------------------

    public function test_a_staffed_category_with_no_complaints_still_refuses_deletion(): void
    {
        User::factory()->teknisi($this->fieldB->id)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.master.categories.destroy', $this->fieldB))
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['id' => $this->fieldB->id]);
    }

    public function test_a_freed_category_after_reassignment_deletes(): void
    {
        $teknisi = User::factory()->teknisi($this->fieldB->id)->create();

        // Free the field by moving the specialist, then delete it.
        $teknisi->update(['category_id' => $this->fieldA->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.master.categories.destroy', $this->fieldB))
            ->assertRedirect(route('admin.master.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $this->fieldB->id]);
    }
}
