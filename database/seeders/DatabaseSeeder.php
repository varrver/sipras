<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Demo photos for a fresh install: real 960x720 JPEGs committed next to
     * this seeder, copied verbatim into the public disk so the detail pages'
     * <img> tags preview actual visible images instead of a broken-image icon.
     * No GD or ImageMagick is assumed at seed time — the bytes ship with the
     * repo. Every fixture is captioned as demo data so it is never mistaken
     * for a real evidence photo from a real school.
     */
    private const EVIDENCE_FIXTURES = [
        __DIR__.'/fixtures/evidence-01.jpg',
        __DIR__.'/fixtures/evidence-02.jpg',
        __DIR__.'/fixtures/evidence-03.jpg',
    ];

    private const RESOLVED_FIXTURES = [
        __DIR__.'/fixtures/resolved-01.jpg',
        __DIR__.'/fixtures/resolved-02.jpg',
    ];

    /**
     * Seed the application's database: the accounts and master data the PRD
     * requires, plus a representative spread of complaints so a fresh install
     * demos with populated dashboards, filters and reports.
     *
     * `WithoutModelEvents` means the Complaint::creating ticket generator does
     * NOT run here — every seeded complaint carries an explicit ticket number.
     */
    public function run(): void
    {
        // A known-good state: any files left by earlier installs/testing go
        // first, so seeding twice never accumulates orphans the cascade sweep
        // would otherwise never know about.
        foreach (['complaints/evidence', 'complaints/resolved'] as $dir) {
            Storage::disk('public')->delete(Storage::disk('public')->allFiles($dir));
        }

        User::factory()->admin()->create([
            'name' => 'Admin SIPRAS',
            'email' => 'admin@sipras.test',
        ]);

        $categories = collect(['Elektronik', 'Furnitur', 'Sanitasi', 'Infrastruktur'])
            ->mapWithKeys(fn (string $name) => [$name => Category::factory()->create(['name' => $name])]);

        // The full SMK Negeri 5 Surakarta room list, distilled from the
        // school floor plan.
        $rooms = collect(require __DIR__.'/data/school_rooms.php')
            ->mapWithKeys(fn (array $room) => [$room['name'] => Room::factory()->create($room)]);

        $users = collect([
            ['name' => 'Budi Santoso', 'email' => 'budi@siswa.test'],
            ['name' => 'Siti Rahayu', 'email' => 'siti@siswa.test'],
            ['name' => 'Andi Wijaya', 'email' => 'andi@siswa.test'],
        ])->mapWithKeys(fn (array $user) => [$user['name'] => User::factory()->create($user)]);

        $this->seedComplaints($users, $categories, $rooms);
    }

    /**
     * Seed complaints across every status, reporter, category and room.
     *
     * Dates are relative to now (spread over the last two weeks) so the demo
     * never goes stale, and each row gets an explicit ticket derived from its
     * own day. Resolved rows carry proof photos, rejection rows carry the
     * reason the student will read.
     *
     * @param  Collection<string, User>  $users
     * @param  Collection<string, Category>  $categories
     * @param  Collection<string, Room>  $rooms
     */
    private function seedComplaints($users, $categories, $rooms): void
    {
        $rows = [
            // [user, category, room, title, status, daysAgo, admin_notes, resolved_after_days]
            ['Budi Santoso', 'Elektronik', 'Lab. RPL 1', 'Proyektor Lab Komputer 1 mati total', 'resolved', 13, 'Proyektor diganti dengan unit cadangan; kabel HDMI ikut diperbarui.', 2],
            ['Siti Rahayu', 'Furnitur', 'Ruang 1.01', 'Dua kursi kelas A1 patah', 'resolved', 12, 'Kedua kursi diganti dari stok gudang sarana.', 2],
            ['Andi Wijaya', 'Sanitasi', 'Lab. RPL 2', 'Kran wudhu dekat Lab Komputer 2 bocor', 'in_progress', 11, 'Petugas sarana sudah survei; menunggu suku cadang.', null],
            ['Budi Santoso', 'Infrastruktur', 'Ruang Perpustakaan', 'AC Perpustakaan tidak dingin', 'in_progress', 9, 'Jadwal pengisian freon oleh vendor hari Jumat.', null],
            ['Budi Santoso', 'Elektronik', 'Lab. RPL 2', 'Tiga keyboard Lab Komputer 2 rusak', 'in_progress', 8, 'Keyboard cadangan sedang diminta ke bagian inventaris.', null],
            ['Siti Rahayu', 'Elektronik', 'Ruang 1.01', 'Lampu kelas A1 sebagian mati', 'pending', 6, null, null],
            ['Andi Wijaya', 'Furnitur', 'Ruang Perpustakaan', 'Meja baca perpustakaan goyang', 'pending', 5, null, null],
            ['Budi Santoso', 'Infrastruktur', 'Lab. RPL 1', 'Kabel jaringan lab terpotong', 'resolved', 4, 'Kabel ditarik ulang dan konektor baru dipasang.', 1],
            ['Siti Rahayu', 'Infrastruktur', 'Ruang 1.01', 'Jendela kelas A1 pecah', 'rejected', 4, 'Sudah ditangani wali kelas sebelum pengaduan diverifikasi; laporan ditutup.', null],
            ['Andi Wijaya', 'Sanitasi', 'Ruang Perpustakaan', 'Tempat sampah perpustakaan tidak diangkat', 'pending', 3, null, null],
            ['Siti Rahayu', 'Elektronik', 'Ruang Perpustakaan', 'Kipas angin perpustakaan berbunyi keras', 'rejected', 2, 'Unit sudah masuk jadwal perbaikan minggu depan; laporan duplikat.', null],
            ['Andi Wijaya', 'Furnitur', 'Lab. RPL 2', 'Kursi Lab Komputer 2 bergoyang', 'resolved', 1, 'Baut dikencangkan dan dudukan diganti.', 0],
        ];

        $sequence = [];
        $file = 0;
        $resolvedFile = 0;

        foreach ($rows as [$userName, $categoryName, $roomName, $title, $status, $daysAgo, $notes, $resolvedAfter]) {
            $day = Carbon::now()->subDays($daysAgo)->setTime(8, rand(0, 45));

            // One ticket number per day per submission order: TKT-YYYYMMDD-XXXX.
            $key = $day->format('Ymd');
            $sequence[$key] = ($sequence[$key] ?? 0) + 1;
            $file++;

            $fixture = self::EVIDENCE_FIXTURES[($file - 1) % count(self::EVIDENCE_FIXTURES)];
            $evidence = 'complaints/evidence/seed-'.str_pad((string) $file, 2, '0', STR_PAD_LEFT).'.jpg';
            Storage::disk('public')->put($evidence, $this->fixtureBytes($fixture));

            $resolvedImage = null;
            $resolvedAt = null;

            if ($status === 'resolved') {
                $resolvedFixtureFile = $resolvedFile % count(self::RESOLVED_FIXTURES);
                $resolvedFile++;
                $resolvedImage = 'complaints/resolved/seed-'.str_pad((string) $file, 2, '0', STR_PAD_LEFT).'.jpg';
                Storage::disk('public')->put($resolvedImage, $this->fixtureBytes(self::RESOLVED_FIXTURES[$resolvedFixtureFile]));
                $resolvedAt = $day->copy()->addDays((int) $resolvedAfter)->setTime(13, rand(0, 59));
            }

            Complaint::factory()->create([
                'ticket_number' => 'TKT-'.$key.'-'.str_pad((string) $sequence[$key], 4, '0', STR_PAD_LEFT),
                'user_id' => $users[$userName]->id,
                'category_id' => $categories[$categoryName]->id,
                'room_id' => $rooms[$roomName]->id,
                'title' => $title,
                'description' => $title.'. Mohon segera ditindaklanjuti karena mengganggu kegiatan pembelajaran.',
                'evidence_image' => $evidence,
                'status' => $status,
                'admin_notes' => $notes,
                'resolved_image' => $resolvedImage,
                'resolved_at' => $resolvedAt,
                'created_at' => $day,
                'updated_at' => $resolvedAt ?? $day,
            ]);
        }
    }

    /**
     * Read the raw fixtures' bytes, failing loudly when they are missing so a
     * broken checkout cannot seed empty files as "images".
     */
    private function fixtureBytes(string $path): string
    {
        if (! is_file($path)) {
            throw new RuntimeException('Demo image fixture is missing: '.$path.' — database/seeders/fixtures ships with the repo.');
        }

        return (string) file_get_contents($path);
    }
}
