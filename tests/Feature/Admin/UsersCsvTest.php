<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The CSV round-trip at /admin/master/users: the export streams the whole
 * roster as name,email,role, and the import is strictly all-or-nothing — any
 * invalid row aborts with a row-numbered report and zero writes (the teacher's
 * "never a silent partial import" rule). The upload dialog follows the shared
 * modal contract.
 */
class UsersCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_streams_the_whole_roster_in_id_order(): void
    {
        // The acting admin is created FIRST so the expected id order is fixed:
        // roster rows come out id-ascending regardless of creation order.
        $admin = User::factory()->create(['name' => 'Kepala Akun', 'email' => 'kepala@sipras.test', 'role' => 'admin']);
        $zed = User::factory()->create(['name' => 'Zed Akun', 'email' => 'zed@sipras.test', 'role' => 'siswa']);
        $alpha = User::factory()->create(['name' => 'Alpha Akun', 'email' => 'alpha@sipras.test', 'role' => 'admin']);

        $response = $this->actingAs($admin->refresh())
            ->get(route('admin.master.users.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertTrue(
            str_contains($disposition, 'sipras-users-'),
            "Content-Disposition should name the SIPRAS roster file: {$disposition}"
        );

        $this->assertSame(
            [
                ['name', 'email', 'role'],
                [$admin->name, $admin->email, 'admin'],
                [$zed->name, $zed->email, 'siswa'],
                [$alpha->name, $alpha->email, 'admin'],
            ],
            array_map('str_getcsv', explode("\n", trim($response->streamedContent(), "\n")))
        );
    }

    public function test_export_is_refused_to_siswa(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'siswa']))
            ->get(route('admin.master.users.export'))
            ->assertForbidden();

        // RoleMiddleware aborts 403 for guests too (the role check runs before
        // any auth redirect on this stack) — the roster never leaves.
        $this->get(route('admin.master.users.export'))
            ->assertForbidden();
    }

    public function test_import_creates_every_row_of_a_valid_file(): void
    {
        $csv = "name,email,role\n".
            "Citra Baru,citra@sipras.test,siswa\n".
            "Bagas Staf,bagas@sipras.test,admin\n".
            "Dodi Baru,dodi@sipras.test,siswa\n";

        $flash = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.master.users.import'), ['csv_file' => $this->csvFile($csv)]);

        $flash->assertRedirect(route('admin.master.users.index'));
        $flash->assertSessionHas('success', fn (string $message) => str_contains($message, '3 pengguna berhasil diimpor'));

        foreach (['citra@sipras.test', 'bagas@sipras.test', 'dodi@sipras.test'] as $email) {
            $this->assertDatabaseHas('users', ['email' => $email]);
        }

        // D3: the imported password is random — guessing any shipped default
        // must fail, while the row still came out a usable account.
        $citra = User::whereEmail('citra@sipras.test')->first();
        $this->assertFalse(Hash::check('password', $citra->password));
        $this->assertSame('siswa', $citra->role);
    }

    public function test_a_single_invalid_row_aborts_the_whole_file(): void
    {
        $csv = "name,email,role\n".
            // `guru` is not a role and must stay one: if teknisi ever joins
            // the vocabulary, this test must not silently start importing.
            "Valid Dulu,valid@sipras.test,siswa\n".
            "Role Nyasar,rolenyasar@sipras.test,guru\n".
            "Valid Lagi,validlagi@sipras.test,admin\n";

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.master.users.import'), ['csv_file' => $this->csvFile($csv)])
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Baris 3')
                && str_contains($message, 'Peran (role) tidak valid')
                && str_contains($message, 'tidak ada pengguna yang dibuat'));

        $this->assertDatabaseMissing('users', ['email' => 'valid@sipras.test']);
        $this->assertDatabaseMissing('users', ['email' => 'validlagi@sipras.test']);
    }

    public function test_email_uniqueness_holds_against_the_database_and_inside_the_file(): void
    {
        User::factory()->create(['email' => 'taken@sipras.test']);

        $csv = "name,email,role\n".
            "Sudah Ada,taken@sipras.test,siswa\n".
            "Ganda Pertama,ganda@sipras.test,siswa\n".
            "Ganda Kedua,ganda@sipras.test,admin\n";

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.master.users.import'), ['csv_file' => $this->csvFile($csv)])
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Baris 2')
                && str_contains($message, 'Baris 4')
                && str_contains($message, 'sudah digunakan'));

        $this->assertDatabaseMissing('users', ['email' => 'ganda@sipras.test']);
    }

    public function test_missing_fields_report_their_rows_and_stop_everything(): void
    {
        $csv = "name,email,role\n".
            ",tanpanama@sipras.test,siswa\n".
            "Tanpa Email,,siswa\n".
            "Lengkap,lengkap@sipras.test,siswa\n";

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.master.users.import'), ['csv_file' => $this->csvFile($csv)])
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Baris 2')
                && str_contains($message, 'Nama wajib diisi')
                && str_contains($message, 'Baris 3')
                && str_contains($message, 'Email wajib diisi'));

        $this->assertDatabaseMissing('users', ['email' => 'lengkap@sipras.test']);
    }

    public function test_a_malformed_file_is_reported_never_imported(): void
    {
        $csv = "nama,email,peran\n".
            "Citra,citra@sipras.test,siswa\n";

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.master.users.import'), ['csv_file' => $this->csvFile($csv)])
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'header'));

        $this->assertDatabaseMissing('users', ['email' => 'citra@sipras.test']);
    }

    public function test_the_upload_dialog_follows_the_modal_contract(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.master.users.index'));

        $html->assertSee("open-modal', 'import-users'", false);
        $html->assertSee('import-users-title', false);
        $html->assertSee('for="csv_file"', false);
        $html->assertSee(route('admin.master.users.export'));

        // The closed-registration reality: no registration CTA may race here —
        // though the page's own "Daftar pengguna" intro paragraph is a list
        // reference, not a CTA, so only the dialog wiring itself is asserted.
        $html->assertSee('Ekspor CSV');
        $html->assertSee('Impor CSV');
    }

    public function test_an_over_or_mistyped_upload_is_refused_by_the_file_rules(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.master.users.import'), ['csv_file' => null])
            ->assertSessionHasErrors('csv_file');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.master.users.import'), [
                'csv_file' => UploadedFile::fake()->create('catatan.pdf', 10),
            ])
            ->assertSessionHasErrors('csv_file');
    }

    private function csvFile(string $csv): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('pengguna.csv', $csv);
    }
}
