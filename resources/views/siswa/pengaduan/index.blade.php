@section('title', __('Pengaduan Saya'))
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading-row">
            <h1 class="text-2xl font-bold tracking-tight text-on-surface">
                {{ __('Pengaduan Saya') }}
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
            @if (! $hasComplaints)
                <div class="card p-10 text-center">
                    <p class="font-semibold text-on-surface">Belum ada pengaduan, {{ Auth::user()->name }}.</p>
                    <p class="mt-1.5 text-sm text-on-surface-variant">Kalau ada sarana sekolah yang rusak, sampaikan lewat form pengaduan &mdash; Tim Sarana Sekolah akan menindaklanjutinya.</p>
                    <a href="{{ route('siswa.pengaduan.create') }}" class="mt-4 btn-primary">
                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ __('Buat Pengaduan Pertama') }}
                    </a>

                    {{-- First-run affordance: the aha moment is a filed ticket
                         gaining visible status, so the card shows the journey in
                         the very chips this student will meet later — real
                         <x-status-badge> components, not illustrations. Static
                         Blade: no script, no storage, works with JS off. --}}
                    <div class="mt-6 border-t border-outline-variant pt-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">
                            {{ __('Alur pengaduanmu') }}
                        </p>
                        <div class="mt-3 grid gap-4 sm:grid-cols-3">
                            <div>
                                <x-status-badge status="pending" />
                                <p class="mt-1.5 text-xs text-on-surface-variant">
                                    {{ __('Admin memverifikasi kelengkapan laporanmu.') }}
                                </p>
                            </div>
                            <div>
                                <x-status-badge status="in_progress" />
                                <p class="mt-1.5 text-xs text-on-surface-variant">
                                    {{ __('Tim sarana menangani kerusakan yang dilaporkan.') }}
                                </p>
                            </div>
                            <div>
                                <x-status-badge status="resolved" />
                                <p class="mt-1.5 text-xs text-on-surface-variant">
                                    {{ __('Perbaikan ditutup dengan foto bukti.') }}
                                </p>
                            </div>
                        </div>
                        <p class="mt-4 text-xs text-on-surface-variant">
                            {{ __('Setiap laporan mendapat nomor tiket yang bisa dilacak sampai selesai — dan bila ditolak, admin selalu menuliskan alasannya.') }}
                        </p>
                    </div>
                </div>
            @else
                @php
                    // The admin toolbar's status vocabulary, role-blind: the menu
                    // lists STATUS_LABELS only — no counts, because the student's
                    // dashboard cards carry the tallies. "Semua" is not a status,
                    // so it is written here.
                    $statusOptions = [['value' => '', 'label' => __('Semua')]];

                    foreach (\App\Models\Complaint::STATUS_LABELS as $value => $label) {
                        $statusOptions[] = ['value' => (string) $value, 'label' => $label];
                    }

                    $activeStatus = request('status') ?: '';
                @endphp

                {{-- One card for everything below the header, exactly like the
                     staff lists: toolbar, the status picker, and the rows
                     (table on lg+, stacked cards below) share the same
                     surface. --}}
                <section class="card">
                    <div class="p-6">
                        {{-- 1. TOP: search toolbar. Searching must not silently
                             drop the status already chosen: the status
                             picker's form carries `q`, so this carries
                             `status`. --}}
                        <div class="mb-4">
                            <form method="GET" action="{{ route('siswa.pengaduan.index') }}" role="search">
                                @if (request('status'))
                                    <input type="hidden" name="status" value="{{ request('status') }}" />
                                @endif
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <div class="w-full sm:max-w-md">
                                        <label for="q" class="sr-only">{{ __('Cari pengaduan') }}</label>
                                        <input id="q" type="text" name="q" value="{{ request('q') }}"
                                            placeholder="Cari judul, nomor tiket, atau deskripsi…" class="field" />
                                    </div>
                                    <button type="submit" class="btn-outline">
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                        </svg>
                                        {{ __('Cari') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- 2. MIDDLE: the status picker — the admin toolbar's
                             capsule, naming its dimension ("Status") until a
                             status is applied, then reading the pick. The
                             student's list has one filter dimension, so it
                             takes one compact 10rem column rather than admin's
                             three; the keyword and the pick ride each other's
                             forms so neither silently drops the other. --}}
                        <div class="mt-1 mb-5 flex flex-wrap items-center gap-2.5">
                            <form method="GET" action="{{ route('siswa.pengaduan.index') }}"
                                class="w-full sm:w-[10rem]">
                                @if (request('q'))
                                    <input type="hidden" name="q" value="{{ request('q') }}" />
                                @endif
                                <label for="status" class="sr-only">{{ __('Status pengaduan') }}</label>
                                <x-select-menu
                                    form-autosubmit
                                    capsule
                                    :active="in_array(request('status'), \App\Models\Complaint::STATUSES, true)"
                                    id="status"
                                    name="status"
                                    :options="$statusOptions"
                                    :value="$activeStatus"
                                    placeholder="{{ __('Status') }}"
                                    placeholderWhenEmpty
                                    nativeLabel="{{ __('Status pengaduan') }}"
                                    class="w-full"
                                />
                            </form>

                            {{-- The global reset stays after the picker, exactly
                                 as on the staff lists: one capsule clearing
                                 status + keyword together. --}}
                            @if (request('q') || request('status'))
                                <a href="{{ route('siswa.pengaduan.index') }}" class="btn-outline ms-auto">
                                    {{ __('Hapus filter') }}
                                </a>
                            @endif
                        </div>

                        @if ($complaints->isEmpty())
                            <div class="py-10 text-center">
                                <p class="text-sm text-on-surface-variant">
                                    {{ __('Tidak ada pengaduan yang cocok dengan filter ini.') }}
                                </p>
                                <a href="{{ route('siswa.pengaduan.index') }}" class="btn-outline mt-3">
                                    {{ __('Tampilkan Semua') }}
                                </a>
                            </div>
                        @else
                            <div class="lg:hidden grid grid-cols-1 gap-3">
                                @foreach ($complaints as $complaint)
                                    {{-- Same card structure as the staff lists: one link
                                         (the title) with a stretched ::after tap target;
                                         a badge, never a status control. --}}
                                    <div class="card p-4 card-hover group relative flex items-center justify-between gap-3 {{ $complaint->ticket_number === request('created') ? 'ring-2 ring-primary bg-surface-container-high' : '' }}">
                                        <div class="min-w-0">
                                            <div class="flex items-center justify-between gap-2">
                                                {{-- Ticket numbers never wrap — a broken-across-lines
                                                     ticket reads as two different tickets. --}}
                                                <span class="text-xs font-mono font-semibold text-on-surface whitespace-nowrap">{{ $complaint->ticket_number }}</span>
                                            </div>
                                            {{-- h2, not h3: the page's only other heading is the
                                                 h1, and a jump to h3 trips heading-order. --}}
                                            <h2 class="mt-2 truncate font-medium text-on-surface">
                                                <a href="{{ route('siswa.pengaduan.show', $complaint) }}"
                                                    class="after:absolute after:inset-0 after:content-[''] after:rounded-2xl after:z-10 group-hover:text-primary">
                                                    {{ $complaint->title }}
                                                </a>
                                            </h2>
                                            <div class="mt-2">
                                                <x-status-badge :status="$complaint->status" />
                                            </div>
                                            <p class="mt-1 text-xs text-on-surface-variant truncate">{{ $complaint->category->name }} &middot; {{ $complaint->room->name }} &middot; {{ $complaint->created_at->translatedFormat('d M Y') }}</p>
                                        </div>
                                        <svg class="h-5 w-5 shrink-0 text-on-surface-variant" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </div>
                                @endforeach
                            </div>

                            <div class="hidden lg:block overflow-x-auto">
                                <table class="min-w-full divide-y divide-outline-variant">
                                    {{-- Named for AT; `sr-only` because the page heading and the
                                         filter bar already say what the list is. --}}
                                    <caption class="sr-only">Daftar pengaduan saya</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Tipe kerusakan</th>
                                            <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Ruang</th>
                                            <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Keluhan</th>
                                            <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Tiket</th>
                                            <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Tanggal</th>
                                            <th scope="col" class="px-2.5 py-3 text-right text-xs font-medium text-on-surface-variant uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-surface-container divide-y divide-outline-variant">
                                        @foreach ($complaints as $complaint)
                                            @php $isCreated = $complaint->ticket_number === request('created'); @endphp
                                            {{-- Fresh-created highlight: tint goes on each <td> because
                                                 the tbody's bg-surface-container paints over <tr> bg.
                                                 The top/bottom primary border stays on the <tr> for the
                                                 blue edge lines (border-y works with divide-y tables). --}}
                                            <tr class="transition-colors hover:bg-surface-container-high {{ $isCreated ? 'border-y-2 border-primary' : '' }}">
                                                <td class="px-2.5 py-3 whitespace-nowrap {{ $isCreated ? 'bg-primary-container' : '' }}"><x-status-badge :status="$complaint->status" /></td>
                                                <td class="px-2.5 py-3 text-sm text-on-surface-variant {{ $isCreated ? 'bg-primary-container' : '' }}">{{ $complaint->category->name }}</td>
                                                <td class="px-2.5 py-3 text-sm text-on-surface-variant {{ $isCreated ? 'bg-primary-container' : '' }}">{{ $complaint->room->name }}</td>
                                                <td class="px-2.5 py-3 text-sm font-medium text-on-surface max-w-[12rem] truncate {{ $isCreated ? 'bg-primary-container' : '' }}">{{ $complaint->title }}</td>
                                                <td class="px-2.5 py-3 text-sm font-mono font-semibold whitespace-nowrap text-on-surface {{ $isCreated ? 'bg-primary-container' : '' }}">{{ $complaint->ticket_number }}</td>
                                                <td class="px-2.5 py-3 text-sm whitespace-nowrap text-on-surface-variant {{ $isCreated ? 'bg-primary-container' : '' }}">{{ $complaint->created_at->translatedFormat('d M Y') }}</td>
                                                {{-- 44px tap target without growing the row: the cell
                                                     drops its vertical padding, the link supplies it,
                                                     and -me-2 keeps Detail aligned with Aksi. --}}
                                                <td class="px-2.5 text-right {{ $isCreated ? 'bg-primary-container' : '' }}">
                                                    <a href="{{ route('siswa.pengaduan.show', $complaint) }}"
                                                        class="relative z-10 -me-2 inline-flex min-h-11 items-center gap-1 px-2 text-sm font-semibold text-primary hover:text-on-primary-container">
                                                        Detail
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                                        </svg>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-5">
                                {{ $complaints->links() }}
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
