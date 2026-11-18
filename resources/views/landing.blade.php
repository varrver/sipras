<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        {{-- Title: the page's h1 + " — SIPRAS" (PageTitleTest contract). --}}
        <title>{{ __('Laporkan kerusakan sarana sekolah, pantau sampai selesai.') }} — {{ config('app.name', 'SIPRAS') }}</title>
        <meta name="description" content="Sistem Informasi Pengaduan Sarana Sekolah untuk warga SMK Negeri 5 Surakarta.">

        <link rel="canonical" href="{{ url('/') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-surface text-on-surface font-sans antialiased min-h-screen flex flex-col justify-between">
        {{-- Primary accent bar: 1-px stripe across the top --}}
        <div aria-hidden="true" class="h-1 bg-primary shrink-0"></div>

        <div class="w-full flex-1">
            {{-- Wordmark-only top bar. The school is already named by the logo
                 beside it, the hero sentence and the footer — a second identity
                 chip here only repeated it in a pill that looked clickable. --}}
            <header class="mx-auto flex max-w-5xl items-center px-4 sm:px-6 py-5">
                {{-- min-h-11: the wordmark link is the page's way home, so it carries
                     the same 44px touch floor as every button on it. --}}
                <a href="/" class="flex min-h-11 items-center gap-2.5 text-on-surface transition hover:opacity-90" aria-label="SIPRAS">
                    <x-application-logo class="h-9 w-9" />
                    <span class="text-xl font-bold tracking-tight">SIPRAS</span>
                </a>
            </header>

            <main class="mx-auto max-w-5xl px-4 sm:px-6 pt-4 pb-12">
                {{-- Hero & Sign-in: Balanced 50/50 two-column grid on desktop --}}
                <div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-2 lg:gap-12">

                    {{-- Left column: Intro & highlights --}}
                    <div class="flex flex-col justify-center max-w-xl mx-auto lg:max-w-none lg:mx-0">
                        <h1 class="text-3xl font-bold leading-tight tracking-tight sm:text-4xl lg:text-[40px] lg:leading-[1.16]">
                            {{ __('Laporkan kerusakan sarana sekolah,') }}
                            <span class="text-primary block mt-1">{{ __('pantau sampai selesai.') }}</span>
                        </h1>

                        <p class="mt-4 text-base leading-relaxed text-on-surface-variant">
                            {{ __('SIPRAS adalah sistem pengaduan sarana SMK Negeri 5 Surakarta. Petugas membuka tiket, teknisi memproses di lapangan, dan status tiap laporan terpantau sampai tuntas — semua dalam satu tempat.') }}
                        </p>

                        <div class="mt-6 space-y-2.5 border-t border-outline-variant pt-5">
                            <div class="flex items-center gap-3 text-sm text-on-surface">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-container text-on-primary-container">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <span>{{ __('Unggah foto bukti langsung saat melapor') }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-on-surface">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-tertiary-container text-on-tertiary-container">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                                    </svg>
                                </div>
                                <span>{{ __('Penanganan tepat oleh teknisi spesialis kategori') }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-on-surface">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-success-container text-on-success-container">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span>{{ __('Pantau status pengerjaan transparan hingga tuntas') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Right column: Login card (constrained on mobile/tablet, expands in column on lg) --}}
                    {{-- `.card` is the same cell the guest shell and every app page
                         wear: radius, hairline and fill come from one class, so the
                         public surfaces cannot drift from the signed-in ones. --}}
                    <div class="card w-full max-w-md mx-auto p-6 sm:p-8 lg:max-w-none">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight text-on-surface">{{ __('Masuk') }}</h2>
                            <p class="mt-1 text-sm text-on-surface-variant">{{ __('Gunakan akun SIPRAS yang telah didaftarkan admin.') }}</p>
                        </div>

                        {{-- Session status (e.g. password-reset confirmation) --}}
                        <x-auth-session-status class="mt-4" :status="session('status')" />

                        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                            @csrf

                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                {{-- No autofocus: this is an introduction page, not a
                                     dedicated form — focusing the field on load puts a
                                     ring in the card and opens the phone keyboard
                                     before the hero is read. A failed submit still
                                     lands the caret on the flagged field (app.js). --}}
                                <x-text-input id="email" class="mt-1.5 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="password" :value="__('Kata Sandi')" />
                                <x-text-input id="password" class="mt-1.5 w-full"
                                    type="password"
                                    name="password"
                                    required autocomplete="current-password" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            {{-- min-h-11: the row is a label, so the whole 44px band is
                                 the target — a bare checkbox plus its text was a ~20px
                                 hit area, the one control on the page under the app's
                                 touch floor. It supplies its own vertical padding now,
                                 so the old `pt-1` wrapper is gone. --}}
                            <div>
                                <label for="remember_me" class="-ms-2 inline-flex min-h-11 w-fit items-center px-2">
                                    <input id="remember_me" type="checkbox" class="rounded accent-primary text-primary" name="remember">
                                    <span class="ms-2 text-sm text-on-surface-variant">{{ __('Ingat saya') }}</span>
                                </label>
                            </div>

                            <div class="pt-2">
                                {{-- Closed registration/reset policy: no registration or password-reset link. --}}
                                <x-primary-button class="w-full justify-center">{{ __('Masuk') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Cara kerjanya: 3-step symmetrical grid across full width --}}
                <section class="mt-14 sm:mt-18" aria-labelledby="cara-kerja">
                    <div class="text-center max-w-xl mx-auto mb-6">
                        <h2 id="cara-kerja" class="text-xl sm:text-2xl font-bold tracking-tight text-on-surface">
                            {{ __('Cara kerjanya') }}
                        </h2>
                        <p class="mt-1.5 text-sm text-on-surface-variant">
                            {{ __('Alur penanganan pengaduan fasilitas di lingkungan sekolah yang terukur dan transparan.') }}
                        </p>
                    </div>

                    <ol class="grid gap-4 sm:grid-cols-3">
                        @foreach ([
                            ['1. Buka tiket', 'pending', 'Siswa atau admin mencatat kerusakan lengkap dengan foto bukti dan lokasi.'],
                            ['2. Diproses', 'in_progress', 'Teknisi mengambil pengaduan dan menangani kerusakannya di lapangan.'],
                            ['3. Selesai', 'resolved', 'Status berganti Selesai beserta foto perbaikan yang bisa dipantau langsung.'],
                        ] as $step)
                            <li class="card p-5 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-sm font-bold text-on-surface">{{ __($step[0]) }}</span>
                                        <x-status-badge :status="$step[1]" />
                                    </div>
                                    <p class="mt-2.5 text-sm leading-relaxed text-on-surface-variant">{{ __($step[2]) }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>

                {{-- Untuk siapa: Symmetrical info card across full width --}}
                <section class="card mt-6 p-5 sm:p-6" aria-labelledby="untuk-siapa">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                        <div class="max-w-2xl">
                            <h2 id="untuk-siapa" class="text-base font-bold text-on-surface">{{ __('Untuk siapa') }}</h2>
                            <p class="mt-1 text-sm leading-relaxed text-on-surface-variant">
                                {{ __('Untuk seluruh warga SMK Negeri 5 Surakarta: siswa melapor, admin memverifikasi, dan teknisi menangani. Akun dibagikan oleh admin — bila belum punya atau lupa sandi, hubungi admin sekolah.') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold shrink-0">
                            <span class="inline-flex items-center rounded-full bg-secondary-container px-3.5 py-1.5 text-on-secondary-container">{{ __('Siswa') }}</span>
                            <span class="inline-flex items-center rounded-full bg-secondary-container px-3.5 py-1.5 text-on-secondary-container">{{ __('Teknisi') }}</span>
                            <span class="inline-flex items-center rounded-full bg-secondary-container px-3.5 py-1.5 text-on-secondary-container">{{ __('Admin') }}</span>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <footer class="border-t border-outline-variant bg-surface shrink-0 py-6">
            <div class="mx-auto flex max-w-5xl flex-col sm:flex-row items-center justify-between gap-2 px-4 sm:px-6 text-xs text-on-surface-variant">
                <span>{{ __('SMK Negeri 5 Surakarta') }} · {{ __('Sistem Informasi Pengaduan Sarana Sekolah') }}</span>
                <span>SIPRAS</span>
            </div>
        </footer>
    </body>
</html>