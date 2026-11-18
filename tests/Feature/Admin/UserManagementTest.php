<?php

namespace Tests\Feature\Admin;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_list_users(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Ani Siswa', 'email' => 'ani@siswa.test']);

        $this->actingAs($admin)
            ->get(route('admin.master.users.index'))
            ->assertOk()
            ->assertSee('ani@siswa.test')
            ->assertSee('Ani Siswa');
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.master.users.store'), [
                'name' => 'Ani Siswa',
                'email' => 'ani@siswa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'siswa',
            ])
            ->assertRedirect(route('admin.master.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'ani@siswa.test',
            'role' => 'siswa',
        ]);
    }

    public function test_admin_cannot_create_user_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ani@siswa.test']);

        $this->actingAs($this->admin())
            ->from(route('admin.master.users.create'))
            ->post(route('admin.master.users.store'), [
                'name' => 'Ani Siswa',
                'email' => 'ani@siswa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'siswa',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 2);
    }

    public function test_admin_cannot_create_user_with_mismatched_password(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.master.users.store'), [
                'name' => 'Ani Siswa',
                'email' => 'ani@siswa.test',
                'password' => 'password123',
                'password_confirmation' => 'password456',
                'role' => 'siswa',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'ani@siswa.test']);
    }

    public function test_admin_cannot_create_user_with_invalid_role(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.master.users.store'), [
                'name' => 'Ani Siswa',
                'email' => 'ani@siswa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'superuser',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'ani@siswa.test']);
    }

    public function test_admin_can_update_user_identity_and_role(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Lama', 'email' => 'lama@siswa.test', 'role' => 'siswa']);

        $this->actingAs($admin)
            ->put(route('admin.master.users.update', $user), [
                'name' => 'Baru',
                'email' => 'lama@siswa.test',
                'role' => 'admin',
                'password' => '',
            ])
            ->assertRedirect(route('admin.master.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Baru',
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.master.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'password' => 'secret-new-pass',
                'password_confirmation' => 'secret-new-pass',
            ])
            ->assertRedirect(route('admin.master.users.index'));

        $this->assertTrue(Hash::check('secret-new-pass', $user->fresh()->password));
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.master.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'siswa',
                'password' => '',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.master.users.destroy', $user))
            ->assertRedirect(route('admin.master.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_deleting_a_user_cascades_complaints_and_sweeps_their_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $user = User::factory()->create();

        $open = Complaint::factory()->create([
            'user_id' => $user->id,
            'evidence_image' => 'complaints/evidence/open.jpg',
        ]);
        $resolved = Complaint::factory()->create([
            'user_id' => $user->id,
            'status' => 'resolved',
            'resolved_at' => now(),
            'evidence_image' => 'complaints/evidence/closed.jpg',
            'resolved_image' => 'complaints/resolved/proof.jpg',
        ]);
        Storage::disk('public')->put($open->evidence_image, 'x');
        Storage::disk('public')->put($resolved->evidence_image, 'x');
        Storage::disk('public')->put($resolved->resolved_image, 'x');
        $otherUser = User::factory()->create();
        $otherComplaint = Complaint::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($admin)
            ->delete(route('admin.master.users.destroy', $user))
            ->assertRedirect(route('admin.master.users.index'));

        // The cascade must not orphan evidence/resolved images on the disk.
        Storage::disk('public')->assertMissing([$open->evidence_image, $resolved->evidence_image, $resolved->resolved_image]);
        $this->assertDatabaseMissing('complaints', ['id' => $open->id]);
        $this->assertDatabaseMissing('complaints', ['id' => $resolved->id]);
        $this->assertDatabaseHas('complaints', ['id' => $otherComplaint->id]);
    }

    public function test_deleting_a_user_clears_their_stale_session_rows(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'testing',
            'payload' => base64_encode('x'),
            'last_activity' => time(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.master.users.destroy', $user));

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    }

    public function test_user_delete_modal_states_the_complaint_count_being_deleted(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        Complaint::factory()->count(3)->create(['user_id' => $user->id]);

        $content = $this->actingAs($admin)->get(route('admin.master.users.index'))->getContent();

        $this->assertStringContainsString('beserta 3 pengaduan', $content);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.master.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_siswa_cannot_access_user_management(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.master.users.index'))
            ->assertForbidden();
    }
}
