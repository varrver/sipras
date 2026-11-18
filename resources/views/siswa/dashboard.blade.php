@section('title', __('Dashboard Siswa'))
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading-row">
            <h1 class="text-2xl font-bold tracking-tight text-on-surface">
                {{ __('Dashboard Siswa') }}
            </h1>
            <a href="{{ route('siswa.pengaduan.create') }}" class="btn-primary">
                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('Buat Pengaduan') }}
            </a>
        </div>
    </x-slot>

    <div class="pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @php
                // Same one map as the chips, the status badge and the printed recap.
                $labels = \App\Models\Complaint::STATUS_LABELS;
            @endphp

            {{-- Total row — full-width, with icon chip. --}}
            <a href="{{ route('siswa.pengaduan.index') }}"
                class="card p-5 card-hover flex items-center gap-4 mb-4"
                aria-label="Total — {{ $totalPengaduan }} pengaduan">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" /></svg>
                </span>
                <div>
                    <p class="text-sm font-medium text-on-surface-variant">Total Pengaduan</p>
                    <p class="text-3xl font-bold tracking-tight text-on-surface" aria-hidden="true">{{ $totalPengaduan }}</p>
                </div>
            </a>

            {{-- 4 status cards — 2×2 on sm, 4-up on lg. --}}
            <ul class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <li>
                    <a href="{{ route('siswa.pengaduan.index', ['status' => 'pending']) }}" class="card p-5 card-hover flex items-center gap-3 h-full">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-warning-container text-on-warning-container" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-on-surface-variant truncate">{{ $labels['pending'] }}</p>
                            <p class="text-3xl font-bold tracking-tight text-on-warning-container" aria-hidden="true">{{ $menunggu }}</p>
                            <span class="sr-only">— {{ $menunggu }} pengaduan</span>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('siswa.pengaduan.index', ['status' => 'in_progress']) }}" class="card p-5 card-hover flex items-center gap-3 h-full">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-tertiary-container text-on-tertiary-container" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-on-surface-variant truncate">{{ $labels['in_progress'] }}</p>
                            <p class="text-3xl font-bold tracking-tight text-on-tertiary-container" aria-hidden="true">{{ $proses }}</p>
                            <span class="sr-only">— {{ $proses }} pengaduan</span>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('siswa.pengaduan.index', ['status' => 'resolved']) }}" class="card p-5 card-hover flex items-center gap-3 h-full">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-success-container text-on-success-container" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-on-surface-variant truncate">{{ $labels['resolved'] }}</p>
                            <p class="text-3xl font-bold tracking-tight text-on-success-container" aria-hidden="true">{{ $selesai }}</p>
                            <span class="sr-only">— {{ $selesai }} pengaduan</span>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('siswa.pengaduan.index', ['status' => 'rejected']) }}" class="card p-5 card-hover flex items-center gap-3 h-full">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-error-container text-on-error-container" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-on-surface-variant truncate">{{ $labels['rejected'] }}</p>
                            <p class="text-3xl font-bold tracking-tight text-on-error-container" aria-hidden="true">{{ $ditolak }}</p>
                            <span class="sr-only">— {{ $ditolak }} pengaduan</span>
                        </div>
                    </a>
                </li>
            </ul>

            <section class="card mt-6">
                <div class="p-6">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-lg font-semibold text-on-surface">Pengaduan Terbaru</h2>
                        @if (!$recentComplaints->isEmpty())
                            <a href="{{ route('siswa.pengaduan.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary/80">
                                {{ __('Lihat semua') }}
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @endif
                    </div>

                    @if ($recentComplaints->isEmpty())
                        <p class="mt-3 text-sm text-on-surface-variant">
                            Belum ada pengaduan. Laporan yang kamu buat lewat form
                            akan tampil di sini — lengkap dengan nomor tiket dan
                            statusnya.
                        </p>
                    @else
                        <div class="mt-3 divide-y divide-outline-variant">
                            @foreach ($recentComplaints as $complaint)
                                <a href="{{ route('siswa.pengaduan.show', $complaint) }}"
                                    class="flex items-center justify-between gap-4 py-3 -mx-2 rounded-lg px-2 card-hover">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-on-surface">{{ $complaint->title }}</p>
                                        <p class="text-xs font-mono font-semibold text-on-surface">{{ $complaint->ticket_number }}</p>
                                    </div>
                                    <x-status-badge :status="$complaint->status" />
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>