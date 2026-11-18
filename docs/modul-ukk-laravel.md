# MODUL PEMBELAJARAN UKK — LARAVEL + TAILWIND + MYSQL

**Basis belajar:** project SIPRAS (Sistem Pengaduan Sarana Sekolah) — sub-tema C guideline project kelas XII.
**Target:** kamu mampu *membangun sendiri, dari nol, offline*, aplikasi dari salah satu dari 4 sub-tema UKK di PC sekolah (Windows, PHP 8.2 native, MySQL, **Laravel 12**).
**Cara pakai:** baca bagian 1–3 sekali (pahami konsep), lalu kerjakan bagian 5 sambil membuka project SIPRAS sebagai contoh. Bagian 6–10 dipakai saat latihan dan persiapan ujian.

---

## DAFTAR ISI

| Bagian | Isi |
|---|---|
| 1 | Memahami UKK: satu kerangka untuk 4 tema |
| 2 | Teori MVC + siklus request Laravel |
| 3 | Anatomi project SIPRAS (contoh nyata tiap konsep) |
| 4 | Persiapan PC sekolah (Windows + PHP 8.2 + MySQL + Laravel 12) |
| 5 | **LANGKAH-DEMI-LANGKAH MEMBUAT PROJECT DARI NOL** |
| 6 | Cheat sheet: perintah & sintaks yang wajib hafal |
| 7 | Latihan mandiri + cek pemahaman |
| 8 | Simulasi UKK & strategi waktu |
| 9 | Prediksi pertanyaan penguji (code review) |
| 10 | Troubleshooting Windows |

---

# BAGIAN 1 — MEMAHAMI UKK: SATU KERANGKA UNTUK 4 TEMA

## 1.1 Apa yang sesungguhnya diminta guideline

Baca persyaratan teknis guideline (bagian D). Semua poinnya:

1. Framework **Laravel**.
2. Database dengan **Migration dan Seeder**.
3. **Multi-user** (role-based) minimal 2 role: Admin/Petugas dan Siswa/User.
4. UI/UX **responsif**.
5. Fitur minimal:
   - **Autentikasi**: login, logout, pengaturan profil/password.
   - **Master Data**: olah data pengguna + data pendukung (kategori/ruang/dll).
   - **Transaksi/Layanan utama**: siswa *mengajukan* sesuatu → admin *verifikasi* dan ubah status **Pending / Approved / Rejected** (+ mencatat pengembalian/penyerahan).
   - **Dashboard & Laporan**: rekap dalam tabel + **cetak/export PDF atau Excel**.

Artinya UKK ini **bukan menguji tema**, tapi menguji *satu kerangka aplikasi* yang sama:

```
LOGIN (2 role)
   └─► ADMIN:  CRUD master data  +  CRUD user  +  verifikasi transaksi (ubah status)  +  dashboard  +  laporan PDF
   └─► SISWA:  buat pengajuan/transaksi  +  lihat status pengajuan sendiri  +  dashboard ringkas
```

Setiap sub-tema hanya **mengganti nama entitas**-nya:

| Konsep kerangka | C. Pengaduan (SIPRAS) | A. Inventaris | B. Perpustakaan | D. Dokumen Akademik |
|---|---|---|---|---|
| Master pendukung 1 | Category (kategori) | Category (kategori barang) | Category (genre/rak) | JenisDokumen |
| Master pendukung 2 | Room (ruang) | Location (lokasi) | Shelf / none | Kelas/TahunAjaran |
| Entitas transaksi | Complaint | Loan (peminjaman) | Loan (peminjaman) | DocumentRequest |
| Status | pending / in_progress / resolved / rejected | pending / approved / rejected / returned | pending / borrowed / returned / late | pending / approved / ready / taken |
| Bukti/lampiran | evidence_image | foto kondisi | — | scan dokumen |
| Catatan admin | admin_notes | catatan petugas | denda (hitung hari) | alasan penolakan |
| Laporan | rekap pengaduan | rekap peminjaman | rekap + denda | rekap legalisir |

**Strategi:** hafalkan SATU kerangka (bagian 5), lalu di hari-H tinggal ganti nama tabel, kolom, dan label. Jangan hafal 4 aplikasi.

## 1.2 Kriteria nilai = checklist kamu

Gunakan ini sebagai daftar serah-terima. Sebelum dikumpulkan, SEMUA harus bisa didemokan hidup-hidup:

- [ ] Login/logout jalan untuk 2 akun dengan role berbeda.
- [ ] Siswa tidak bisa membuka URL admin (dapat 403), dan sebaliknya.
- [ ] Admin bisa CRUD minimal 2 master data + data pengguna (termasuk ganti role & reset password).
- [ ] Data master yang masih dipakai transaksi tidak bisa dihapus (muncul pesan ramah, bukan error 500).
- [ ] Siswa bisa membuat pengajuan (dengan upload bukti bila relevan).
- [ ] Admin bisa ubah status pengajuan; status *resolved/approved* mencatat waktu penyelesaian.
- [ ] Dashboard menampilkan angka ringkasan per status.
- [ ] Laporan bisa dilihat di layar dan dicetak/export ke PDF (tombol cetak browser).
- [ ] Profil: ganti nama/email dan ganti password jalan.
- [ ] Tampilan rapi di layar lebar DAN sempit (responsif).
- [ ] `php artisan migrate:fresh --seed` dari nol tidak error.

---

# BAGIAN 2 — TEORI MVC + SIKLUS REQUEST

## 2.1 MVC dalam satu kalimat

**MVC (Model–View–Controller)** memisahkan kode berdasarkan tugasnya supaya tidak bercampur:

| Lapisan | Tugas | Di Laravel | Analogi restoran |
|---|---|---|---|
| **Model** | Data & aturan data: tabel, relasi, query, perilaku otomatis | `app/Models/*.php` | Bahan dapur & stok |
| **View** | Tampilan yang dilihat user | `resources/views/**/*.blade.php` | Piring & plating |
| **Controller** | Perantara: terima request → ambil data lewat Model → pilih View untuk render | `app/Http/Controllers/**` | Pelayan |
| Pendukung | Routing, validasi, middleware | `routes/web.php`, `app/Http/Requests`, `app/Http/Middleware` | Menu, QC, satpam |

Aturan praktis yang membuat penilai senang:

- **Controller tipis** — tidak ada query rumit dan tidak ada HTML di controller.
- **Model pinter** — relasi, konstanta status, dan perilaku otomatis (misal nomor tiket) hidup di model.
- **Validasi di FormRequest** — bukan `if-else` manual di controller.
- **View tidak berhitung** — Blade hanya menampilkan data yang sudah siap.

## 2.2 Siklus satu request (hafalkan, sering ditanya)

Contoh: siswa submit form pengaduan di SIPRAS.

```
Browser POST /siswa/pengaduan  (dengan token @csrf)
   │
   ▼
routes/web.php ─── menemukan route [Siswa\ComplaintController::class, 'store']
   │               grup middleware: auth + role:siswa
   ▼
Middleware 'auth' ── belum login? → redirect ke /login
Middleware 'role:siswa' ── bukan siswa? → abort(403)
   │
   ▼
StoreComplaintRequest ── validasi (kategori wajib, judul max 255, foto jpg/png ≤ 2 MB)
   │                     gagal? → kembali ke form + $errors (tanpa menyentuh controller)
   ▼
ComplaintController::store()
   │  $data = $request->validated();
   │  $data['user_id'] = $request->user()->id;
   │  $data['evidence_image'] = $request->file('evidence_image')->store('complaints/evidence','public');
   ▼
Model Complaint::create($data)
   │  event creating() → nomor tiket TKT-YYYYMMDD-XXXX dibuat otomatis
   ▼
INSERT ke MySQL
   │
   ▼
redirect()->route('siswa.pengaduan.index') + flash 'Pengaduan berhasil dikirim'
   │
   ▼
View siswa/pengaduan/index.blade.php merender tabel pengaduan user
```

Itu seluruh arsitektur. Setiap bagian 5 nanti adalah pengulangan pola ini untuk tiap fitur.

## 2.3 Kata kunci yang harus bisa kamu jelaskan

- **Route** — peta URL → controller. `Route::get/post/patch/delete`, `->name()` untuk penamaan, `Route::resource()` untuk CRUD otomatis, grup `->middleware()` + `->prefix()`.
- **Middleware** — penyaring sebelum controller (auth, role).
- **Eloquent ORM** — Model mewakili tabel; `$complaint->user` = relasi; `Complaint::where('status','pending')->get()` = query.
- **Migration** — versi-skema database dalam PHP; bisa diulang di PC mana pun (`migrate:fresh`).
- **Seeder** — pengisi data awal (akun admin, data dummy).
- **Blade** — template engine: `{{ }}` echo aman, `@if`, `@foreach`, `@csrf`, komponen `<x-…>`.
- **CSRF (`@csrf`)** — token anti-pemalsuan request; wajib di setiap `<form method="POST">`.
- **Flash message** — pesan sekali-tampil (`session()->flash('success', …)`), ditampilkan di halaman tujuannya (biasanya di atas konten).

---

# BAGIAN 3 — ANATOMI PROJECT SIPRAS (CONTOH NYATA)

> **Catatan versi penting:** project SIPRAS ini dibuat dengan Laravel 13/PHP 8.5, sedangkan PC sekolah memakai **Laravel 12/PHP 8.2**. Hampir semua polanya sama. **Tiga perbedaan** yang perlu kamu tahu saat membaca kode SIPRAS:
> 1. SIPRAS menulis `#[Fillable([...])]` (atribut PHP). **Di Laravel 12 tulis properti klasik: `protected $fillable = [...];`** — itulah yang dipakai di semua contoh modul ini.
> 2. Struktur folder Laravel 12 = sama (tanpa `app/Http/Kernel.php`, middleware didaftarkan di `bootstrap/app.php`).
> 3. Auth SIPRAS dibuat dengan paket **Breeze** — modul ini **tidak** memakai Breeze (auth dibangun manual di Langkah 4). Jadi `routes/auth.php`, `app/Http/Controllers/Auth/*`, dan views `auth/*` milik SIPRAS hanya sebagai referensi pola, bukan untuk ditiru utuh.

## 3.1 Peta folder yang relevan

```
sipras/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/            ← semua controller sisi admin
│   │   │   │   ├── CategoryController.php      (CRUD master kategori)
│   │   │   │   ├── RoomController.php          (CRUD master ruang)
│   │   │   │   ├── UserController.php          (CRUD user + role)
│   │   │   │   ├── ComplaintManagementController.php (verifikasi + laporan + PDF)
│   │   │   │   └── DashboardController.php     (kartu statistik)
│   │   │   ├── Siswa/
│   │   │   │   ├── ComplaintController.php     (buat & lihat pengaduan sendiri)
│   │   │   │   └── DashboardController.php
│   │   │   ├── Auth/               ← dari Breeze (login/logout/profil)
│   │   │   └── Controller.php
│   │   ├── Middleware/RoleMiddleware.php       ← autorisasi role
│   │   └── Requests/               ← FormRequest (validasi per fitur)
│   │       ├── Category/StoreCategoryRequest.php
│   │       ├── Complaint/StoreComplaintRequest.php
│   │       └── ...
│   └── Models/
│       ├── User.php      (role: admin|siswa)
│       ├── Category.php
│       ├── Room.php
│       └── Complaint.php (tiket otomatis, konstanta status, relasi)
├── bootstrap/app.php               ← daftar alias middleware 'role'
├── routes/
│   ├── web.php                     ← semua route aplikasi (grup admin & siswa)
│   └── auth.php                    ← route login/logout (Breeze)
├── database/
│   ├── migrations/                 ← users, categories, rooms, complaints
│   ├── seeders/DatabaseSeeder.php  ← 1 admin, 3 siswa, 4 kategori, 4 ruang, data uji
│   └── factories/
├── resources/views/
│   ├── layouts/                    ← app (dashboard), guest (login), navigation
│   ├── admin/…                     ← dashboard, master, pengaduan, reports
│   ├── siswa/…                     ← dashboard, pengaduan (index/create/show)
│   ├── auth/…                      ← login, dsb (Breeze)
│   ├── components/                 ← status-badge, modal, input-error, dst
│   └── errors/403|404|419|500
├── public/storage/                 ← symlink ke storage (hasil upload)
└── .env                            ← konfigurasi DB, locale
```

## 3.2 Belajar dengan membaca file asli

Setiap kali mempelajari satu konsep, buka file aslinya di SIPRAS dan cocokkan:

| Konsep | Buka file ini di SIPRAS |
|---|---|
| Model + relasi + konstanta status | `app/Models/Complaint.php` |
| Model sederhana + relasi | `app/Models/Category.php` |
| Middleware role | `app/Http/Middleware/RoleMiddleware.php` |
| Registrasi middleware | `bootstrap/app.php` |
| Pengelompokan route + resource | `routes/web.php` |
| CRUD master data lengkap | `app/Http/Controllers/Admin/CategoryController.php` |
| Transaksi siswa + upload file | `app/Http/Controllers/Siswa/ComplaintController.php` |
| Verifikasi admin + laporan + PDF | `app/Http/Controllers/Admin/ComplaintManagementController.php` |
| FormRequest + pesan Indonesia | `app/Http/Requests/Complaint/StoreComplaintRequest.php` |
| Migration + foreign key | `database/migrations/2026_09_09_000003_create_complaints_table.php` |
| Seeder realistis | `database/seeders/DatabaseSeeder.php` |
| Form Blade + tampilkan error | `resources/views/siswa/pengaduan/create.blade.php` |
| Tabel + filter + chip status | `resources/views/admin/pengaduan/index.blade.php` |
| Laporan cetak (inline CSS untuk PDF) | `resources/views/admin/reports/complaints.blade.php` |

---

# BAGIAN 4 — PERSIAPAN PC SEKOLAH (WINDOWS)

## 4.1 Cek dulu apa yang sudah ada

Buka **Command Prompt** (bukan PowerShell lama) dan jalankan satu per satu:

```bat
php -v          :: harus PHP 8.2.x
composer -V     :: harus Composer 2.x
node -v         :: harus Node 18/20/22 (untuk Vite + Tailwind)
npm -v
mysql --version :: kalau MySQL ada di PATH (kalau via XAMPP, cek dari panel)
```

- Yang **hilang**, instal sesuai 4.2. Catat versi apa yang tersedia di lab — di hari-H kamu tidak mau kaget.
- Kalau `php -v` "not recognized": PHP belum masuk PATH → bagian 10 (Troubleshooting T1).

## 4.2 Instalasi standar (jika harus memasang sendiri)

1. **PHP 8.2** (zip dari windows.php.net): ekstrak ke `C:\php`, tambahkan `C:\php` ke **PATH**, lalu aktifkan ekstensi di `C:\php\php.ini` (hapus `;` di depan):
   `extension=pdo_mysql`, `extension=mbstring`, `extension=fileinfo`, `extension=gd`, `extension=zip`, `extension=intl`, `extension=curl`, `extension=openssl`.
2. **Composer** (installer .exe dari getcomposer.org) — saat instalasi, arahkan ke `C:\php\php.exe`.
3. **Node.js LTS** (nodejs.org) — untuk build Tailwind.
4. **MySQL**: paling praktis **XAMPP** (MySQL + phpMyAdmin sekaligus) ATAU MySQL Community + MySQL Workbench. Start MySQL dari panel XAMPP sebelum migrate.
5. Cek ulang 4.1 — semuanya harus terbaca.

## 4.3 Rencana B: kalau internet di sekolah terbatas

`composer create-project` dan `composer require` butuh internet. Siapkan **sebelum hari-H**:

1. Di rumah: `composer create-project laravel/laravel:^12.0 template-ukk` saja — modul ini **tidak butuh paket tambahan sama sekali** (auth manual di Langkah 4, laporan dicetak via browser di Langkah 13), lalu **jangan dihapus vendor-nya**. Zip folder template ini (termasuk `vendor/`).
2. Bawa zip di flashdisk + upload ke GitHub pribadi (cadangan).
3. Di sekolah: ekstrak zip → project siap, tinggal `php artisan migrate:fresh --seed` dan `npm run build` (atau pakai `node_modules` yang ikut ditempel, atau fallback CDN Tailwind di 5.1).
4. **Konfirmasi ke guru** apakah membawa template boleh — kejujuran dalam ujian itu nilai plus, dan biasanya yang dilarang adalah membawa aplikasi jadi, bukan kerangka kosong.

## 4.4 Perintah harian yang akan kamu pakai terus

```bat
cd C:\xampp\htdocs\namaprojek        (atau folder kerjamu)
php artisan serve                    :: server dev di http://127.0.0.1:8000
php artisan migrate                  :: jalankan migration baru
php artisan migrate:fresh --seed     :: reset total + isi data awal
php artisan make:model Nama -mcr     :: model + migration + resource controller sekaligus
php artisan make:request NamaRequest :: FormRequest validasi
php artisan storage:link             :: symlink uploads
npm run dev                          :: Vite dev server (terminal kedua saat koding)
npm run build                        :: build aset final (untuk demo)
```

---

# BAGIAN 5 — LANGKAH-DEMI-LANGKAH MEMBUAT PROJECT DARI NOL

> Studi kasus di bawah memakai tema **Pengaduan** (sama dengan SIPRAS, supaya kamu bisa membandingkan langsung dengan project contoh). Untuk tema lain, ganti nama tabel/kolom mengikuti tabel pemetaan di 1.1 — **struktur langkahnya identik 1:1**.

Target akhir langkah 1–14 persis seperti kerangka bagian 1.1.

---

## Langkah 1 — Buat project Laravel 12

```bat
cd C:\xampp\htdocs
composer create-project laravel/laravel:^12.0 sipras-ukk
cd sipras-ukk
php artisan serve
```

Buka `http://127.0.0.1:8000` — harus muncul halaman welcome Laravel. (Terminal serve **tetap jalan**; buka terminal baru untuk perintah berikutnya.)

**Cek:** halaman welcome tampil tanpa error.

---

## Langkah 2 — Database MySQL + `.env`

Buat database lewat phpMyAdmin (XAMPP → New → nama `sipras_ukk`, collation `utf8mb4_unicode_ci`) atau CLI:

```bat
mysql -u root -e "CREATE DATABASE sipras_ukk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Edit `.env` di root project:

```env
APP_NAME=SIPRAS
APP_LOCALE=id
APP_FAKER_LOCALE=id_ID

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sipras_ukk
DB_USERNAME=root
DB_PASSWORD=
```

> Laravel 12 default-nya `DB_CONNECTION=sqlite` dan `SESSION_DRIVER=database`. Biarkan `SESSION_DRIVER=database` — sesi disimpan di tabel `sessions`, itu bagus (dan dipakai untuk logout paksa). Pastikan `APP_LOCALE=id` supaya pesan error bawaan berbahasa Indonesia.

**Cek:** `php artisan migrate` → tabel-tabel default (users, cache, jobs, sessions) terbentuk tanpa error.

---

## Langkah 3 — Kolom `role` di tabel users

Sebelum migrate:fresh berikutnya, edit migration users: `database/migrations/0001_01_01_000000_create_users_table.php`, di dalam `Schema::create('users', ...)` tambahkan satu baris setelah `password`:

```php
$table->enum('role', ['admin', 'siswa'])->default('siswa')->after('password');
```

Lalu reset:

```bat
php artisan migrate:fresh
```

**Cek:** di phpMyAdmin, kolom `role` muncul di tabel `users`.

---

## Langkah 4 — Autentikasi manual (login, logout, profil + ganti password)

Modul ini **tidak memakai Breeze**. Inti auth sudah ada di Laravel: `Auth::attempt()`, middleware `auth`/`guest` bawaan, dan cast `'password' => 'hashed'` di model User. Butuh ±45–60 menit kerja, tapi **setiap baris bisa kamu jelaskan ke penguji saat code review**, tanpa satu pun paket tambahan — dan karena registrasi tidak pernah dibuat, akun hanya bisa dibuat lewat seeder/admin (persis konsep SIPRAS).

#### 4.1 Dua layout: tamu & aplikasi

`resources/views/layouts/guest.blade.php` — untuk halaman login (Vite + Tailwind sudah bawaan Laravel 12 — `resources/css/app.css` berisi `@import 'tailwindcss'`):

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — SIPRAS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">
    @yield('content')
</body>
</html>
```

Lalu `resources/views/layouts/app.blade.php` — untuk user yang sudah login: header berisi nama user, link profil, dan form logout (POST, bukan link — logout mengubah state):

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — SIPRAS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <span class="font-bold text-gray-900">SIPRAS</span>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                <a href="{{ route('profile.edit') }}" class="text-sm text-gray-600 hover:underline">Profil</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="text-sm font-medium text-red-600">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
        @yield('content')
    </main>
</body>
</html>
```

Semua view aplikasi berikutnya (Langkah 10–13) memakai `@extends('layouts.app')` — header + logout ini otomatis hadir di setiap halaman.

#### 4.2 Controller auth

`php artisan make:controller AuthController`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Auth::attempt = cek email+password terhadap tabel users (password dicocokkan ter-hash).
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }

        // Regenerasi session id setelah login: perisai dari session fixation.
        $request->session()->regenerate();

        return redirect()->intended(
            auth()->user()->role === 'admin' ? route('admin.dashboard') : route('siswa.dashboard')
        );
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();   // matikan session lama
        $request->session()->regenerateToken(); // ganti token CSRF

        return redirect()->route('login');
    }
}
```

Dua kalimat yang harus kamu hafal untuk penguji: **`regenerate()` setelah login** (mencegah session fixation — id session baru untuk user baru) dan **`invalidate()` + `regenerateToken()` saat logout** (session mati total, token CSRF ganti supaya form lama tidak sah).

#### 4.3 Routes

`routes/web.php` — middleware `guest` dan `auth` sudah bawaan Laravel, tidak perlu dibuat. Dua route dashboard di bawah adalah *placeholder* agar redirect setelah login tidak error — Langkah 9 menggantinya dengan yang sungguhan:

```php
use App\Http\Controllers\AuthController;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store']);
});

Route::post('logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/admin/dashboard', fn () => 'Dasbor Admin (dibangun di Langkah 13)')->middleware('auth')->name('admin.dashboard');
Route::get('/siswa/dashboard', fn () => 'Dasbor Siswa (dibangun di Langkah 13)')->middleware('auth')->name('siswa.dashboard');
```

#### 4.4 View login

`resources/views/auth/login.blade.php`:

```blade
@extends('layouts.guest')

@section('title', 'Masuk')
@section('content')
<div class="flex items-center justify-center min-h-screen px-4">
    <form method="POST" action="{{ route('login') }}"
          class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-sm space-y-4">
        @csrf
        <h1 class="text-xl font-bold text-gray-900">Masuk</h1>

        @if ($errors->any())
            <p class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</p>
        @endif

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autofocus
                   class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" name="password" type="password"
                   class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2">
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" class="rounded border-gray-300"> Ingat saya
        </label>

        <button class="w-full rounded-full bg-red-600 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
            Masuk
        </button>
    </form>
</div>
@endsection
```

#### 4.5 Profil + ganti password (sisa fitur auth yang diminta guideline)

Controller kecil (`php artisan make:controller ProfileController`), tiga route, satu view:

```php
// routes/web.php — di dalam grup middleware('auth')
Route::get('profil',  [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('profil',[ProfileController::class, 'update'])->name('profile.update');
Route::put('profil/password', [ProfileController::class, 'password'])->name('password.update');

// ProfileController (jangan lupa use Illuminate\Validation\Rule; di atas kelas)
public function edit()
{
    return view('profile.edit');
}

public function update(Request $request)
{
    $data = $request->validate([
        'name'  => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($request->user())],
    ], [
        'name.required' => 'Nama wajib diisi.',
        'email.unique'  => 'Email sudah dipakai akun lain.',
    ]);

    $request->user()->update($data);

    return back()->with('success', 'Profil berhasil diperbarui.');
}

public function password(Request $request)
{
    $request->validate([
        'current_password' => ['required', 'current_password:web'],
        'password'         => ['required', 'confirmed', 'min:8'],
    ], [
        'current_password.current_password' => 'Password saat ini salah.',
        'password.confirmed' => 'Konfirmasi password tidak cocok.',
        'password.min'       => 'Password minimal :min karakter.',
    ]);

    $request->user()->update(['password' => $request->password]); // cast 'hashed' meng-hash otomatis

    return back()->with('success', 'Password berhasil diganti.');
}
```

Aturan bawaan yang menarik untuk dijelaskan ke penguji: **`current_password:web`** — Laravel mencocokkan isian dengan password user login yang sebenarnya, dan **`confirmed`** — otomatis membandingkan dengan kolom `password_confirmation` di form.

View-nya (`resources/views/profile/edit.blade.php`) — dua form dalam satu halaman:

```blade
@extends('layouts.app')

@section('title', 'Profil Saya')
@section('content')
<div class="max-w-xl mx-auto px-4 py-8 space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Profil Saya</h1>

    @if (session('success'))
        <p class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</p>
    @endif

    <form method="POST" action="{{ route('profile.update') }}"
          class="rounded-2xl border border-gray-200 bg-white p-6 space-y-4">
        @csrf
        @method('PATCH')
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nama</label>
            <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}"
                   class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}"
                   class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button class="rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white">Simpan</button>
    </form>

    <form method="POST" action="{{ route('password.update') }}"
          class="rounded-2xl border border-gray-200 bg-white p-6 space-y-4">
        @csrf
        @method('PUT')
        <h2 class="font-semibold text-gray-900">Ganti Password</h2>
        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700">Password Saat Ini</label>
            <input id="current_password" name="current_password" type="password"
                   class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2">
            @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                <input id="password" name="password" type="password"
                       class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2">
            </div>
        </div>
        <button class="rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white">Ganti Password</button>
    </form>
</div>
@endsection
```

**Cek:** login salah → pesan Indonesia, isian email tetap terisi; login benar → tiap role masuk dashboard-nya masing-masing; logout → kembali ke login dan tombol back tidak membuka halaman lama; ganti password → login lama gagal, login baru berhasil.

---

## Langkah 5 — Middleware role (autorisasi)

Buat file `app/Http/Middleware/RoleMiddleware.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user() || $request->user()->role !== $role) {
            abort(403);
        }

        return $next($request);
    }
}
```

Daftarkan aliasnya di `bootstrap/app.php` (Laravel 12 tidak punya `app/Http/Kernel.php`):

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ]);
})
```

**Cek (sementara):** buat route percobaan `Route::get('/coba-admin', fn() => 'HALO ADMIN')->middleware(['auth','role:admin']);` — akses dengan akun siswa harus 403. (Hapus lagi setelah dicek.)

---

## Langkah 6 — Migration: master data & transaksi

```bat
php artisan make:migration create_categories_table
php artisan make:migration create_rooms_table
php artisan make:migration create_complaints_table
```

`..._create_categories_table.php`:

```php
public function up(): void
{
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('categories'); }
```

`..._create_rooms_table.php`:

```php
Schema::create('rooms', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('code')->unique();
    $table->timestamps();
});
```

`..._create_complaints_table.php` (entitas transaksi — perhatikan foreign key):

```php
Schema::create('complaints', function (Blueprint $table) {
    $table->id();
    $table->string('ticket_number')->unique();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->constrained()->restrictOnDelete();
    $table->foreignId('room_id')->constrained()->restrictOnDelete();
    $table->string('title');
    $table->text('description');
    $table->string('evidence_image');
    $table->enum('status', ['pending', 'in_progress', 'resolved', 'rejected'])->default('pending');
    $table->text('admin_notes')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();
});
```

Bedah keputusan penting (sering ditanya penguji):

- `foreignId(...)->constrained()` — kunci tamu ke tabel terkait; intinya relasi dijaga database, bukan cuma kode.
- `cascadeOnDelete()` di `user_id` — hapus user → pengaduannya ikut hilang (data pribadi).
- `restrictOnDelete()` di master — kategori/ruang yang masih dipakai **ditolak** database; controller kita ubah jadi pesan ramah (langkah 10).
- `enum('status')` + `default('pending')` — status terkunci pilihan, pengaduan baru selalu menunggu.
- `nullable()` — kolom yang boleh kosong (catatan admin, waktu selesai).

```bat
php artisan migrate
```

**Cek:** keempat tabel ada di phpMyAdmin, relasi terlihat di tab *Structure → Relation view*.

---

## Langkah 7 — Model + relasi + perilaku otomatis

```bat
php artisan make:model Category
php artisan make:model Room
php artisan make:model Complaint
```

`app/Models/Category.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
```

(`use Illuminate\Database\Eloquent\Relations\HasMany;` di atas.)

`app/Models/Room.php` — pola sama: `protected $fillable = ['name', 'code'];` + `complaints()` hasMany.

`app/Models/Complaint.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Complaint extends Model
{
    protected $fillable = [
        'ticket_number', 'user_id', 'category_id', 'room_id',
        'title', 'description', 'evidence_image',
        'status', 'admin_notes', 'resolved_at',
    ];

    public const STATUSES = ['pending', 'in_progress', 'resolved', 'rejected'];

    public const STATUS_LABELS = [
        'pending'    => 'Menunggu',
        'in_progress' => 'Dalam Proses',
        'resolved'   => 'Selesai',
        'rejected'   => 'Ditolak',
    ];

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Nomor tiket otomatis: TKT-YYYYMMDD-0001 (lanjut dari tiket terakhir di hari yang sama).
        static::creating(function (Complaint $complaint): void {
            if (empty($complaint->ticket_number)) {
                $prefix = 'TKT-' . now()->format('Ymd') . '-';
                $last = static::query()->where('ticket_number', 'like', $prefix.'%')
                    ->latest('id')->value('ticket_number');
                $next = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

                $complaint->ticket_number = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
}
```

`app/Models/User.php` — tambahkan `role` ke fillable dan helper:

```php
protected $fillable = ['name', 'email', 'password', 'role'];

public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function complaints(): HasMany
{
    return $this->hasMany(Complaint::class);
}
```

> Di Laravel 12, cast password `hashed` sudah bawaan (`protected function casts(): array` berisi `'password' => 'hashed'`) — jadi `User::create(['password' => $plain])` otomatis ter-hash. Jangan double-hash dengan `Hash::make` di controller yang sama.

**Cek:** `php artisan tinker` → `App\Models\Category::create(['name' => 'Elektronik']);` lalu `App\Models\Complaint::count();` → 0 tanpa error.

---

## Langkah 8 — Seeder (wajib guideline)

`database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin SIPRAS',
            'email' => 'admin@sipras.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $budi = User::create(['name' => 'Budi Santoso', 'email' => 'budi@siswa.test', 'password' => 'password', 'role' => 'siswa']);
        $siti = User::create(['name' => 'Siti Rahayu', 'email' => 'siti@siswa.test', 'password' => 'password', 'role' => 'siswa']);

        $kategoriElektronik = Category::create(['name' => 'Elektronik']);
        Category::create(['name' => 'Furnitur']);
        Category::create(['name' => 'Sanitasi']);
        Category::create(['name' => 'Infrastruktur']);

        $lab1 = Room::create(['name' => 'Lab Komputer 1', 'code' => 'LAB-01']);
        Room::create(['name' => 'Lab Komputer 2', 'code' => 'LAB-02']);
        Room::create(['name' => 'Ruang Kelas A1', 'code' => 'RKA-01']);

        Complaint::create([
            'user_id' => $budi->id,
            'category_id' => $kategoriElektronik->id,
            'room_id' => $lab1->id,
            'title' => 'Proyektor Lab Komputer 1 mati total',
            'description' => 'Proyektor tidak menyala sejak Senin pagi.',
            'evidence_image' => 'complaints/evidence/dummy.jpg',
            'status' => 'pending',
        ]);

        Complaint::create([
            'user_id' => $siti->id,
            'category_id' => $kategoriElektronik->id,
            'room_id' => $lab1->id,
            'title' => 'Lampu Lab Komputer 1 berkedip',
            'description' => 'Dua lampu berkedip terus mengganggu.',
            'evidence_image' => 'complaints/evidence/dummy.jpg',
            'status' => 'resolved',
            'admin_notes' => 'Lampu diganti petugas sarana.',
            'resolved_at' => now(),
        ]);
    }
}
```

```bat
php artisan migrate:fresh --seed
php artisan storage:link
```

**Cek:** login `admin@sipras.test` / `password` → masuk; `budi@siswa.test` / `password` → masuk. (Tiket otomatis terbentuk oleh model — buktikan di phpMyAdmin.)

---

## Langkah 9 — Routes: dua dunia yang terpisah

Lengkapi `routes/web.php`: hapus dua *placeholder* dashboard dari Langkah 4, lalu tambahkan blok ini (route `/`, login, dan logout dari Langkah 4 tetap):

```php
<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ComplaintManagementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Siswa\ComplaintController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [AdminDashboard::class, 'index'])
    ->middleware(['auth', 'role:admin'])->name('dashboard');

Route::prefix('admin')->middleware(['auth', 'role:admin'])->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    Route::resource('master/categories', CategoryController::class)->names('master.categories');
    Route::resource('master/rooms', RoomController::class)->names('master.rooms');
    Route::resource('master/users', UserController::class)->names('master.users');

    Route::get('pengaduan', [ComplaintManagementController::class, 'index'])->name('pengaduan.index');
    Route::get('pengaduan/{complaint}', [ComplaintManagementController::class, 'show'])->name('pengaduan.show');
    Route::patch('pengaduan/{complaint}/status', [ComplaintManagementController::class, 'updateStatus'])->name('pengaduan.status');
    Route::get('pengaduan/laporan', [ComplaintManagementController::class, 'report'])->name('pengaduan.report');
});

Route::prefix('siswa')->middleware(['auth', 'role:siswa'])->name('siswa.')->group(function () {
    Route::get('/dashboard', [SiswaDashboard::class, 'index'])->name('dashboard');
    Route::get('pengaduan', [ComplaintController::class, 'index'])->name('pengaduan.index');
    Route::get('pengaduan/create', [ComplaintController::class, 'create'])->name('pengaduan.create');
    Route::post('pengaduan', [ComplaintController::class, 'store'])->name('pengaduan.store');
    Route::get('pengaduan/{complaint}', [ComplaintController::class, 'show'])->name('pengaduan.show');
});
```

Poin yang perlu kamu pahami, bukan sekadar disalin:

- `Route::resource('master/categories', ...)` otomatis membuat 7 route CRUD (`index, create, store, show, edit, update, destroy`) — cek dengan `php artisan route:list --path=admin`.
- `->name('admin.')` + `->names('master.categories')` menghasilkan nama `admin.master.categories.index` — dipakai di view: `route('admin.master.categories.index')`.
- Grup middleware = dua pintu berbeda; `/admin/*` hanya admin, `/siswa/*` hanya siswa.
- `pengaduan/laporan` **harus didaftarkan sebelum** `pengaduan/{complaint}` — kalau tidak, kata "laporan" ditelan parameter `{complaint}`.

---

## Langkah 10 — CRUD Master Data (pola yang diulang 3×)

Ini pola paling penting di UKK. Kamu akan mengulangnya untuk **categories, rooms, users** — dan di tema lain untuk barang/buku/dokumen.

### 10.1 FormRequest (validasi + pesan Indonesia)

```bat
php artisan make:request Category/StoreCategoryRequest
php artisan make:request Category/UpdateCategoryRequest
```

`app/Http/Requests/Category/StoreCategoryRequest.php`:

```php
<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // sudah dijaga middleware role:admin
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique'   => 'Kategori dengan nama tersebut sudah ada.',
        ];
    }
}
```

`UpdateCategoryRequest` — sama, tapi nama boleh dipakai record yang sedang diedit:

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($this->category)],
    ];
}
```

### 10.2 Controller resource

```bat
php artisan make:controller Admin/CategoryController --model=Category --requests
```

`app/Http/Controllers/Admin/CategoryController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request)
    {
        Category::create($request->validated());

        session()->flash('success', 'Kategori berhasil ditambahkan.');

        return redirect()->route('admin.master.categories.index');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        session()->flash('success', 'Kategori berhasil diperbarui.');

        return redirect()->route('admin.master.categories.index');
    }

    public function destroy(Category $category)
    {
        if ($category->complaints()->exists()) {
            session()->flash('error', 'Kategori tidak dapat dihapus karena masih dipakai pengaduan.');

            return redirect()->route('admin.master.categories.index');
        }

        $category->delete();

        session()->flash('success', 'Kategori berhasil dihapus.');

        return redirect()->route('admin.master.categories.index');
    }
}
```

Konsep kunci: **route model binding** — `{category}` di URL otomatis dicari oleh Laravel; kalau id tidak ada, otomatis 404. `Category $category` di parameter = tidak perlu `findOrFail` manual.

### 10.3 Views (3 file)

Struktur view: `resources/views/admin/categories/{index,create,edit}.blade.php`.

`index.blade.php` — tabel + flash + tombol:

```blade
@extends('layouts.app')

@section('title', 'Data Kategori')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-900">Data Kategori</h1>
        <a href="{{ route('admin.master.categories.create') }}"
           class="rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
            + Tambah Kategori
        </a>
    </div>

    @if (session('success'))
        <p class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</p>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.master.categories.edit', $category) }}"
                               class="font-medium text-blue-600 hover:underline">Ubah</a>
                            <form action="{{ route('admin.master.categories.destroy', $category) }}"
                                  method="POST" class="inline"
                                  onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="font-medium text-red-600 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">Belum ada kategori.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
```

Konsep yang muncul di sini: `@extends/@section` (warisan layout), `@forelse/@empty` (loop + state kosong), **`@method('DELETE')`** (HTML form hanya kenal GET/POST — Laravel menyamar-kan menjadi DELETE), `@csrf` wajib, dan `confirm()` sebagai penjaga hapus.

`create.blade.php` — form + tampilkan error validasi:

```blade
@extends('layouts.app')

@section('title', 'Tambah Kategori')
@section('content')
<div class="max-w-lg mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Tambah Kategori</h1>

    <form method="POST" action="{{ route('admin.master.categories.store') }}"
          class="rounded-2xl border border-gray-200 bg-white p-6 space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nama Kategori</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}"
                   class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 focus:border-red-500 focus:ring-2 focus:ring-red-200 focus:outline-none">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-3">
            <button class="rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Simpan</button>
            <a href="{{ route('admin.master.categories.index') }}"
               class="rounded-full border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Batal</a>
        </div>
    </form>
</div>
@endsection
```

Catat pola **`value="{{ old('name') }}"`** — kalau validasi gagal, isi yang sudah diketik tidak hilang. Ini detail kecil yang dinilai.

`edit.blade.php` — sama dengan create, tapi: action ke `...update`, tambah `@method('PATCH')`, value dari `$category->name`.

### 10.4 Duplikasi ke Rooms & Users

- **Rooms**: identik dengan categories (kolom `name` + `code`; validasi `code` unik).
- **Users** (`app/Http/Controllers/Admin/UserController.php`) — ada dua tambahan khas:

```php
// store(): buat user + role
$user = User::create([
    'name'     => $request->name,
    'email'    => $request->email,
    'password' => $request->password, // cast 'hashed' meng-hash otomatis
    'role'     => $request->role,
]);

// update(): reset password hanya kalau diisi
$data = $request->only(['name', 'email', 'role']);
if ($request->filled('password')) {
    $data['password'] = $request->password;
}
$user->update($data);
```

Validasi user (store): `name required`, `email required|email|unique:users,email`, `password required|min:8` (update: `nullable|min:8` + `Rule::unique(...)->ignore($this->user)` untuk email), `role required|in:admin,siswa`.

**Cek bagian 10:** tambah/ubah/hapus kategori & ruang & user jalan; kategori yang dipakai pengaduan menolak dihapus dengan pesan ramah; email ganda ditolak dengan pesan Indonesia; password salah format muncul error merah di bawah input.

---

## Langkah 11 — Transaksi sisi Siswa (buat pengaduan + upload bukti)

### 11.1 FormRequest dengan upload

`php artisan make:request Complaint/StoreComplaintRequest`

```php
public function rules(): array
{
    return [
        'category_id'    => ['required', 'exists:categories,id'],
        'room_id'        => ['required', 'exists:rooms,id'],
        'title'          => ['required', 'string', 'max:255'],
        'description'    => ['required', 'string'],
        'evidence_image' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
    ];
}

public function messages(): array
{
    return [
        'category_id.required'    => 'Kategori wajib dipilih.',
        'room_id.required'        => 'Ruang wajib dipilih.',
        'title.required'          => 'Judul pengaduan wajib diisi.',
        'description.required'    => 'Deskripsi pengaduan wajib diisi.',
        'evidence_image.required' => 'Foto bukti wajib diunggah.',
        'evidence_image.mimes'    => 'Foto bukti harus berformat jpeg, png, atau jpg.',
        'evidence_image.max'      => 'Foto bukti maksimal 2 MB.',
    ];
}
```

### 11.2 Controller

`php artisan make:controller Siswa/ComplaintController`

```php
<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;

class ComplaintController extends Controller
{
    public function index()
    {
        $complaints = auth()->user()->complaints()
            ->with(['category', 'room'])
            ->when(in_array(request('status'), Complaint::STATUSES, true), function ($query) {
                $query->where('status', request('status'));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('siswa.pengaduan.index', compact('complaints'));
    }

    public function create()
    {
        return view('siswa.pengaduan.create', [
            'categories' => Category::orderBy('name')->get(),
            'rooms'      => Room::orderBy('name')->get(),
        ]);
    }

    public function store(StoreComplaintRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['evidence_image'] = $request->file('evidence_image')
            ->store('complaints/evidence', 'public');

        Complaint::create($data);

        session()->flash('success', 'Pengaduan berhasil dikirim dan sedang menunggu verifikasi admin.');

        return redirect()->route('siswa.pengaduan.index');
    }

    public function show(Complaint $complaint)
    {
        // Kunci keamanan: siswa hanya boleh melihat pengaduannya sendiri.
        abort_if($complaint->user_id !== auth()->id(), 403);

        return view('siswa.pengaduan.show', compact('complaint'));
    }
}
```

Karena `show()` memakai route model binding, tanpa cek ini siswa bisa menebak `/siswa/pengaduan/1, /2, …` dan membuka milik orang lain. **Selalu autentikasi kepemilikan di sisi server** — menyembunyikan link saja tidak cukup.

Empat pola query baru di `index()`:

- `auth()->user()->complaints()` — mulai dari relasi = otomatis hanya data milik user login (bukan `Complaint::all()`!).
- `->with(['category','room'])` — **eager loading**: ambil relasi sekali lewat JOIN/IN, mencegah "N+1 query" (penguji suka menanyakan ini).
- `->when(kondisi, fn)` — filter kondisional; kalau `?status=` tidak ada, filter dilewati.
- `paginate(10)->withQueryString()` — pagination + pertahankan filter di tautan halaman.

### 11.3 View form (siswa/pengaduan/create.blade.php)

```blade
@extends('layouts.app')

@section('title', 'Buat Pengaduan')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Buat Pengaduan</h1>

    <form method="POST" action="{{ route('siswa.pengaduan.store') }}" enctype="multipart/form-data"
          class="rounded-2xl border border-gray-200 bg-white p-6 space-y-4">
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                <select id="category_id" name="category_id"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 bg-white">
                    <option value="">Pilih kategori…</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="room_id" class="block text-sm font-medium text-gray-700">Ruang</label>
                <select id="room_id" name="room_id"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 bg-white">
                    <option value="">Pilih ruang…</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>
                            {{ $room->name }} ({{ $room->code }})
                        </option>
                    @endforeach
                </select>
                @error('room_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Judul</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}"
                   class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
            <textarea id="description" name="description" rows="4"
                      class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2">{{ old('description') }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="evidence_image" class="block text-sm font-medium text-gray-700">Foto Bukti</label>
            <input id="evidence_image" name="evidence_image" type="file" accept="image/jpeg,image/png"
                   class="mt-1 w-full text-sm">
            <p class="mt-1 text-xs text-gray-500">JPG atau PNG, maksimal 2 MB.</p>
            @error('evidence_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button class="rounded-full bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
            Kirim Pengaduan
        </button>
    </form>
</div>
@endsection
```

Dua detail yang membuat upload bekerja: **`enctype="multipart/form-data"`** di `<form>` (tanpa ini `$request->file()` selalu null) dan disk `public` di controller. Setelah upload, file ada di `storage/app/public/complaints/evidence/...`, ditampilkan lewat `Storage::url($complaint->evidence_image)` (butuh `php artisan storage:link`).

**Cek bagian 11:** login siswa → buat pengaduan dengan foto → muncul di daftar dengan status "Menunggu"; akses `/siswa/pengaduan/{id milik orang lain}` → 403.

---

## Langkah 12 — Verifikasi sisi Admin (ubah status + catatan)

`Admin/ComplaintManagementController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\UpdateComplaintStatusRequest;
use App\Models\Complaint;

class ComplaintManagementController extends Controller
{
    public function index()
    {
        $query = Complaint::query()
            ->with(['user', 'category', 'room'])
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
            ->when(request('q'), function ($query, $q) {
                $query->where(fn ($query) => $query
                    ->where('title', 'like', "%{$q}%")
                    ->orWhere('ticket_number', 'like', "%{$q}%"));
            })
            ->latest();

        return view('admin.pengaduan.index', [
            'complaints' => $query->paginate(10)->withQueryString(),
            'counts'     => $this->statusCounts(),
        ]);
    }

    public function show(Complaint $complaint)
    {
        $complaint->load(['user', 'category', 'room']);

        return view('admin.pengaduan.show', compact('complaint'));
    }

    public function updateStatus(UpdateComplaintStatusRequest $request, Complaint $complaint)
    {
        $data = $request->safe()->only(['status', 'admin_notes']);

        if ($request->status === 'resolved') {
            $data['resolved_at'] = now();
        }

        $complaint->update($data);

        session()->flash('success', 'Status pengaduan berhasil diperbarui.');

        return redirect()->route('admin.pengaduan.show', $complaint);
    }

    public function report()
    {
        [$complaints, $summary] = $this->reportData();

        return view('admin.reports.complaints', compact('complaints', 'summary'));
    }

    private function statusCounts(): array
    {
        $byStatus = Complaint::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = ['total' => 0];
        foreach (Complaint::STATUSES as $status) {
            $counts[$status] = (int) ($byStatus[$status] ?? 0);
            $counts['total'] += $counts[$status];
        }

        return $counts;
    }

    private function reportData(): array
    {
        $query = Complaint::query()->with(['user', 'category', 'room'])
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest();

        $complaints = $query->get();

        $summary = ['total' => $complaints->count()];
        foreach (Complaint::STATUSES as $status) {
            $summary[$status] = $complaints->where('status', $status)->count();
        }

        return [$complaints, $summary];
    }
}
```

`php artisan make:request Complaint/UpdateComplaintStatusRequest`:

```php
public function rules(): array
{
    return [
        'status'      => ['required', Rule::in(Complaint::STATUSES)],
        'admin_notes' => ['required_if:status,rejected', 'nullable', 'string'],
    ];
}

public function messages(): array
{
    return [
        'status.required'       => 'Status wajib dipilih.',
        'status.in'             => 'Status tidak valid.',
        'admin_notes.required_if' => 'Alasan penolakan wajib diisi saat menolak pengaduan.',
    ];
}
```

`required_if:status,rejected` — alasan **wajib hanya saat menolak**; aturan kondisional seperti ini kelihatan profesional dan sering ditanya penguji.

View detail admin (`admin/pengaduan/show.blade.php`) — inti bagian ubah status:

```blade
<form method="POST" action="{{ route('admin.pengaduan.status', $complaint) }}" class="space-y-3">
    @csrf
    @method('PATCH')

    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
    <select id="status" name="status" class="w-full rounded-xl border border-gray-300 px-3 py-2 bg-white">
        @foreach (\App\Models\Complaint::STATUS_LABELS as $value => $label)
            <option value="{{ $value }}" @selected($complaint->status === $value)>{{ $label }}</option>
        @endforeach
    </select>

    <label for="admin_notes" class="block text-sm font-medium text-gray-700">Catatan Admin</label>
    <textarea id="admin_notes" name="admin_notes" rows="3"
              class="w-full rounded-xl border border-gray-300 px-3 py-2">{{ old('admin_notes', $complaint->admin_notes) }}</textarea>
    @error('admin_notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

    <button class="rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white">Simpan Status</button>
</form>
```

> `STATUS_LABELS` dijadikan satu-satunya sumber daftar status di UI (dropdown, chip, badge, laporan). Kalau suatu hari status baru ditambahkan, cukup ubah satu tempat. Prinsip **single source of truth**.

**Cek bagian 12:** ubah status jadi "Selesai" → `resolved_at` terisi otomatis (cek phpMyAdmin); pilih "Ditolak" tanpa catatan → ditolak validasi dengan pesan Indonesia; chip filter `?status=pending` memfilter daftar.

---

## Langkah 13 — Dashboard + Laporan PDF

### 13.1 Dashboard admin

`Admin/DashboardController.php`:

```php
public function index()
{
    return view('admin.dashboard', [
        'counts' => $this->statusCounts(), // copy method dari ComplaintManagementController, atau taruh di model
        'recent' => Complaint::with(['user', 'category', 'room'])->latest()->limit(5)->get(),
    ]);
}
```

`resources/views/admin/dashboard.blade.php` — kartu statistik:

```blade
<div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
    @foreach (\App\Models\Complaint::STATUS_LABELS as $key => $label)
        <a href="{{ route('admin.pengaduan.index', ['status' => $key]) }}"
           class="rounded-2xl border border-gray-200 bg-white p-5 hover:border-red-300">
            <p class="text-sm text-gray-500">{{ $label }}</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $counts[$key] }}</p>
        </a>
    @endforeach
</div>
```

+ tabel 5 pengaduan terbaru (pola tabel dari langkah 10). Kartu = link ke daftar terfilter → terlihat fungsional saat demo.

### 13.2 Dashboard siswa

Sama, tapi datanya milik user login: `auth()->user()->complaints()` + `selectRaw('status, COUNT(*) ...')`.

### 13.3 Cetak laporan → PDF tanpa paket apa pun (cara utama modul ini)

Tanpa paket apa pun: **tombol cetak browser** (`window.print()`) membuka dialog cetak yang punya pilihan **"Save as PDF"** — itu sudah memenuhi kriteria guideline *"fitur cetak/export (PDF atau Excel)"*.

Alasannya: **proses cetak tidak menjalankan Tailwind, JS app, maupun `@vite`** — jadi view laporan harus berdiri sendiri dengan **CSS inline**, tidak extends layout. Itulah kenapa di SIPRAS `admin/reports/complaints.blade.php` tidak extends layout dan punya `<style>` sendiri. Sederhanakan seperti ini:

`resources/views/admin/reports/complaints.blade.php`:

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Pengaduan Sarana</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #1c1c1e; }
        h1 { font-size: 18px; margin: 0; }
        .brand { font-size: 11px; color: #8b1d2c; font-weight: bold; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d1d6; padding: 6px 8px; text-align: left; }
        th { background: #f2f2f7; }
        .summary { margin-top: 10px; }
        @page { size: A4 landscape; margin: 12mm; }   /* arah & margin kertas */
        @media print { .no-print { display: none; } } /* sembunyikan tombol saat mencetak */
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()"
            style="margin-bottom: 10px; padding: 6px 14px; border: none; border-radius: 999px; background: #0066cc; color: #fff; cursor: pointer;">
        Cetak / Simpan PDF
    </button>
    <p class="brand">SMK NEGERI 5 SURAKARTA</p>
    <h1>Rekapitulasi Pengaduan Sarana</h1>
    <p>Dicetak: {{ now()->translatedFormat('d F Y H:i') }}</p>

    <table class="summary">
        <tr>
            <th>Total</th><th>Menunggu</th><th>Dalam Proses</th><th>Selesai</th><th>Ditolak</th>
        </tr>
        <tr>
            <td>{{ $summary['total'] }}</td>
            <td>{{ $summary['pending'] }}</td>
            <td>{{ $summary['in_progress'] }}</td>
            <td>{{ $summary['resolved'] }}</td>
            <td>{{ $summary['rejected'] }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>No. Tiket</th><th>Judul</th><th>Pelapor</th><th>Kategori</th><th>Ruang</th><th>Status</th><th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($complaints as $c)
                <tr>
                    <td>{{ $c->ticket_number }}</td>
                    <td>{{ $c->title }}</td>
                    <td>{{ $c->user->name }}</td>
                    <td>{{ $c->category->name }}</td>
                    <td>{{ $c->room->name }}</td>
                    <td>{{ \App\Models\Complaint::statusLabel($c->status) }}</td>
                    <td>{{ $c->created_at->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
```

**Cara kerjanya:** view yang sama melayani layar dan cetak — browser yang membaca `@media print`. Satu view, dua tujuan, nol dependensi. Untuk penguji, jelaskan: "PDF-nya dibuat browser lewat dialog cetak, jadi yang saya tulis di server hanya view siap-cetak + tombol pemicunya."

**Cek bagian 13:** dashboard menampilkan angka yang cocok dengan phpMyAdmin; `/admin/pengaduan/laporan` tampil rapi; tombol cetak membuka dialog cetak dan "Save as PDF" menghasilkan file PDF yang terbuka normal.

---

## Langkah 14 — Rapikan, responsif, dan backup

1. **Responsif** (kriteria penilaian): periksa setiap halaman di ukuran sempit (F12 → toggle device → 390px). Aturan praktis: `max-w-* mx-auto px-4` di kontainer; tabel dibungkus `div.overflow-x-auto`; grid selalu `grid-cols-1 sm:grid-cols-2`; nav collapse di mobile (Alpine `x-data`/`@click` untuk tombol hamburger).
2. **Judul halaman**: setiap view `@section('title', '…')` dan layout memakainya di `<title>`.
3. **Halaman error berbahasa Indonesia**: `php artisan vendor:publish --tag=laravel-errors` lalu edit `resources/views/errors/{403,404,500}.blade.php` minimal satu paragraf ramah.
4. **Uji nol-ke-jalan**: `php artisan migrate:fresh --seed` di PC/DB lain → aplikasi hidup hanya dengan itu. Ini bukti Migration+Seeder-mu benar.
5. **Backup penyerahan**:
   ```bat
   mysqldump -u root sipras_ukk > database\sipras_ukk.sql
   :: lalu zip project TANPA vendor/ dan node_modules/ (atau DENGAN vendor jika diminta)
   ```
6. **README.md singkat**: cara setup (`composer install`, copy `.env.example`, `key:generate`, `migrate --seed`, `storage:link`, akun admin). Penguji membaca ini.

**Cek akhir:** jalankan kembali checklist 1.2 dari atas ke bawah. Semua centang = siap UKK.

---

# BAGIAN 6 — CHEAT SHEET WAJIB HAFAL

## Artisan

| Perintah | Fungsi |
|---|---|
| `make:model X -mcr` | model + migration + resource controller |
| `make:request Nama` | FormRequest validasi |
| `make:middleware Nama` | middleware |
| `make:migration create_x_table` | migration baru |
| `make:seeder XSeeder` | seeder |
| `migrate` / `migrate:fresh --seed` | jalankan / reset+isi ulang |
| `storage:link` | symlink uploads |
| `route:list` | lihat semua route + nama |
| `tinker` | REPL untuk uji query |
| `serve` | server dev |

## Eloquent yang paling sering dipakai

```php
User::where('role', 'siswa')->latest()->paginate(10);   // filter + urut + paging
Complaint::with(['user','category','room'])->get();       // eager loading (hindari N+1)
Complaint::where('status','pending')->count();
Complaint::find(1); Complaint::findOrFail(99);            // null / 404
Category::create(['name' => 'X']);                        // INSERT
$category->update(['name' => 'Y']); $category->delete();
auth()->user()->complaints()->get();                      // lewat relasi = otomatis terfilter
Complaint::where(fn($q) => $q->where('title','like',"%$q%")->orWhere(...));
```

## Blade yang dipakai setiap hari

```blade
{{ $var }}                    echo aman (htmlspecialchars)
@if @else @endif / @forelse ... @empty ... @endforelse
@csrf                         WAJIB di form POST/PUT/DELETE
@method('PATCH') @method('DELETE')   override method form
@error('field') {{ $message }} @enderror
old('field')                  isi ulang setelah gagal validasi
route('nama.route', $model)   URL dari nama route (route model binding)
session('success')            flash message
@selected($cond) @checked($cond)
```

## Urutan logika form yang benar (pola baku)

```
<form POST @csrf>  →  route POST  →  FormRequest (rules+messages Indonesia)
→  Controller::store  ($request->validated(), simpan file dengan ->store())
→  flash + redirect  →  View tampilkan flash + $errors
```

---

# BAGIAN 7 — LATIHAN MANDIRI (dengan SIPRAS sebagai kunci jawaban)

Kerjakan pada salinan project latihanmu sendiri (jangan di SIPRAS). Tiap latihan punya "file contoh" untuk membandingkan.

1. **Latihan CRUD baru (30 mnt):** tambahkan master `locations` (nama + lantai) lengkap: migration → model → 2 FormRequest → controller resource → 3 views → route resource → seeder. Bandingkan: `CategoryController`, `admin/categories/*`.
2. **Latihan relasi (20 mnt):** tambahkan kolom `priority` enum (`low`,`high`) di `complaints` lewat migration baru (`php artisan make:migration add_priority_to_complaints_table`), tampilkan sebagai badge di daftar admin. Konsep: migration *penambahan kolom* memakai `Schema::table`, bukan `create`.
3. **Latihan keamanan (15 mnt):** coba akses `/admin/dashboard` sebagai siswa, dan `/siswa/pengaduan/3` (milik orang lain). Jelaskan KODE mana yang menolakmu (middleware? `abort_if`?). Kalau kamu tidak bisa menjelaskan barisnya, buka lagi bagian 2.2.
4. **Latihan query (20 mnt):** buat halaman sederhana "pengaduan saya bulan ini" (filter `whereMonth('created_at', now()->month)`), hitung totalnya.
5. **Latihan export (30 mnt):** tambahkan export **CSV** untuk laporan: header `Content-Type: text/csv` + loop `fputcsv`. (PDF-mu sudah jalan via tombol cetak; CSV menutup sisi "Excel" dari kriteria guideline — nilai plus.)

**Tanda kamu siap:** kamu bisa mengerjakan latihan 1–2 **tanpa membuka modul ini**, hanya dengan membuka SIPRAS sebagai contoh.

---

# BAGIAN 8 — SIMULASI UKK & STRATEGI WAKTU

## 8.1 Alokasi waktu (asumsi 10 × 5 JP ≈ 50 jam; simulasikan versi 1 hari untuk gladi)

| Jam | Kerjakan | Checklist keluar |
|---|---|---|
| 0:00–0:30 | Baca soal tema, tentukan entitas (pakai tabel 1.1), buat ERD kasar di kertas | 3–4 tabel, relasi jelas |
| 0:30–1:00 | create-project, .env, DB, migrate | welcome page + tabel |
| 1:00–2:00 | Auth manual (layout, AuthController, login), kolom role, RoleMiddleware, seeder akun | login 2 role jalan |
| 2:00–3:30 | Migrations master+transaksi, models, seeder data | phpMyAdmin: tabel terisi, tiket otomatis |
| 3:30–5:30 | CRUD master data (categories dulu, lalu users) | CRUD hidup + validasi Indonesia |
| 5:30–7:00 | Transaksi siswa (form + upload + daftar) + show 403 guard | siswa bisa mengajukan |
| 7:00–8:00 | Admin verifikasi status (+ `required_if` catatan tolak) | status berubah, resolved_at terisi |
| 8:00–9:00 | Dashboard + laporan cetak/PDF | angka cocok, PDF jalan |
| 9:00–10:00 | Responsif check, halaman error, README, backup .sql, **gladi demo** | checklist 1.2 penuh |

## 8.2 Skenario demo 3 menit untuk penguji (hafalkan urutannya)

1. Login **siswa** → dashboard ringkas → buat pengaduan (upload foto) → muncul di daftar "Menunggu".
2. Logout → login **admin** → dashboard (angka bertambah) → daftar pengaduan → detail → ubah status "Dalam Proses" → lalu "Selesai" → tunjukkan `resolved_at`.
3. Tolak satu pengaduan tanpa catatan → tunjukkan validasi → isi catatan → sukses.
4. Coba hapus kategori yang dipakai → pesan ramah.
5. Master data: tambah/ubah user + reset password.
6. Laporan → filter → **cetak / Save as PDF**.
7. Tutup dengan: coba URL `/admin/...` sebagai siswa → 403 (bukti keamanan).

## 8.3 Jaga-jaga saat demo

- `migrate:fresh --seed` + `npm run build` **sebelum** penguji datang; server jalan (`php artisan serve`).
- Siapkan 2 akun di layar catatan; siapkan 1 file foto kecil di Desktop untuk upload.
- Kalau ada error di depan penguji: baca pesannya, jelaskan apa yang terjadi (itu nilai, bukan aib) — `storage/logs/laravel.log` dan `APP_DEBUG=true` temanmu.

---

# BAGIAN 9 — PREDIKSI PERTANYAAN PENGUJI (CODE REVIEW)

| Pertanyaan | Inti jawaban yang diharapkan |
|---|---|
| Jelaskan MVC di aplikasimu! | Model = `app/Models` (data+relasi, mis. `Complaint` punya relasi user/category/room); View = `resources/views` (Blade); Controller = `app/Http/Controllers` (terima request → panggil model → render view). Controller tidak berisi HTML; view tidak berisi query. |
| Bagaimana alur satu request? | Route → middleware (auth, role) → FormRequest (validasi) → controller → model → redirect/view. (Lafalkan siklus 2.2.) |
| Kenapa pakai FormRequest, bukan validasi di controller? | Controller tetap tipis; aturan dan pesan Indonesia terpusat; `authorize()` + `rules()` bisa dites terpisah; dipakai ulang. |
| Apa itu middleware dan bagaimana role diterapkan? | Lapisan penyaring sebelum controller. Alias `role` didaftarkan di `bootstrap/app.php`; grup route `->middleware('role:admin')` membandingkan `$request->user()->role`; gagal → `abort(403)`. |
| Jelaskan relasi database di projectmu! | 1 user punya banyak pengaduan (`hasMany`), 1 pengaduan milik 1 user/kategori/ruang (`belongsTo`). Di DB: foreign key + `cascadeOnDelete`/`restrictOnDelete`. |
| Kenapa migration & seeder? | Skema & data awal dalam kode → siapa pun (`migrate:fresh --seed`) mendapat database identik; tercatat versinya; tanpa akses phpMyAdmin pun bisa. |
| Bagaimana nomor tiket otomatis dibuat? | Event Eloquent `creating` di model `Complaint`: ambil tiket terakhir dengan prefix hari ini, +1, `str_pad` 4 digit → `TKT-YYYYMMDD-0001`. |
| Bagaimana mencegah siswa melihat pengaduan orang lain? | Query diawali `auth()->user()->complaints()` (relasi) dan `show()` memverifikasi `abort_if($complaint->user_id !== auth()->id(), 403)`. |
| Apa itu N+1 dan bagaimana mengatasinya? | Loop yang memicu query per baris; dicegah dengan eager loading `->with(['user','category'])`. |
| Kenapa hapus kategori yang dipakai tidak error? | FK `restrictOnDelete` menolak di level DB; controller cek `->complaints()->exists()` lebih dulu supaya pesannya ramah, bukan 500. |
| Kenapa `@csrf`? `@method('DELETE')` untuk apa? | Token anti pemalsuan request (CSRF) untuk setiap POST; HTML form hanya GET/POST, `@method` menyamar-kan method agar cocok dengan route resource. |
| Bagaimana fitur cetak PDF di projectmu? | View laporan standalone dengan CSS inline (bukan Tailwind) — proses cetak tidak menjalankan Vite/Tailwind/JS. Tanpa paket: tombol `window.print()` + `@media print`, lalu pilih "Save as PDF" di dialog cetak. |

---

# BAGIAN 10 — TROUBLESHOOTING WINDOWS

| Gejala | Sebab | Obat |
|---|---|---|
| `'php' is not recognized` | PHP belum di PATH | Tambahkan `C:\php` ke PATH (Env. Variables), buka ulang CMD, `php -v` |
| `'composer' is not recognized` | Composer belum terinstal / PATH | Instal ulang Composer, arahkan ke php.exe yang benar |
| `could not find driver` saat migrate | `extension=pdo_mysql` mati | Aktifkan di `php.ini` (php.ini-development → rename `php.ini`), restart serve |
| `SQLSTATE[HY000] [1049] Unknown database` | DB belum dibuat | Buat DB di phpMyAdmin / `CREATE DATABASE` |
| `Connection refused` (XAMPP) | MySQL belum jalan | Start MySQL di panel XAMPP |
| `419 Page Expired` saat submit form | Token CSRF hilang/kadaluarsa | Pastikan `@csrf` ada di dalam `<form>` |
| `The POST method is not supported` | Route POST tidak terdaftar / salah action | `php artisan route:list`, cocokkan `action=` form |
| Halaman polos tanpa CSS | Aset Vite belum dibangun / dev server mati | `npm run build` (atau jalankan `npm run dev` di terminal kedua) |
| Gambar upload 404 | `storage:link` belum dijalankan | `php artisan storage:link` |
| `$request->file()` selalu null | Form tanpa `enctype="multipart/form-data"` | Tambahkan enctype |
| `Target class [role] does not exist` | Alias middleware belum didaftar | Daftarkan di `bootstrap/app.php` → `withMiddleware` |
| Hapus data → halaman error 500 | FK restrict menolak | Cek dulu `->exists()` di controller, flash pesan ramah |
| `Asset ... not found` di log | `npm run build` belum pernah jalan | Jalankan build sekali |
| Login langsung balik ke login | Session driver bermasalah | Pastikan `SESSION_DRIVER=database` + migrate, atau `file` |
| Waktu maju/mundur ( pagi/sore ) | Timezone | `APP_TIMEZONE=Asia/Jakarta` di `.env` |

---

## PENUTUP — KERANGKA HARI-H DI SATU PARAGRAF

> Buat project → MySQL + `.env` → kolom `role` → auth manual (Langkah 4) → RoleMiddleware → migration (2 master + 1 transaksi berstatus) → model + relasi + seeder → routes dua grup (`admin.` / `siswa.`) → CRUD master dengan FormRequest (kategori dulu, users berikutnya) → transaksi siswa (upload + 403 guard) → verifikasi admin (`required_if` tolak, `resolved_at`) → dashboard + laporan cetak/PDF → responsif + error pages + README + backup `.sql`. Setiap langkah punya padanan file di SIPRAS — kalau macet, **buka file contohnya, jangan menebak**.

*Modul ini disusun dari project SIPRAS (Sistem Pengaduan Sarana Sekolah) — SMK Negeri 5 Surakarta, kelas XII RPL, TA 2026/2027.*
