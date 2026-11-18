@section('title', __('Pengaduan Teknisi'))
<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-on-surface">
            {{ __('Pengaduan Teknisi') }}
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            {{ __('Spesialisasi') }}: <span class="font-semibold text-on-surface">{{ $specialty?->name ?? '—' }}</span>
        </p>
    </x-slot>

    <div class="pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <section class="card">
                <div class="p-6">
                    @php
                        // The admin list's filter grammar, role-blind: the status
                        // vocabulary (no counts — the picker lists the shared
                        // STATUS_LABELS only) and the period presentation state.
                        // The queue is already one category (the technician's
                        // specialty), so no category echo exists here.
                        $statusOptions = [['value' => '', 'label' => __('Semua')]];

                        foreach (\App\Models\Complaint::STATUS_LABELS as $value => $label) {
                            $statusOptions[] = ['value' => (string) $value, 'label' => $label];
                        }

                        $activeStatus = request('status') ?: '';

                        $presetMatched = $period !== null && collect(\App\Models\Complaint::presetPeriods())
                            ->contains(fn ($preset) => $preset['from'] === $period['from'] && $preset['to'] === $period['to']);
                        $swapped = $period !== null && (string) request('from') > (string) request('to');
                        $applied = $period !== null && request()->filled('from')
                            && request()->filled('to') && ! request()->filled('page');
                    @endphp

                    {{-- Toolbar: search owns the top row, the period and status
                         pickers sit on the row below it — the admin list's
                         arrangement, minus the category picker: the queue is
                         already one field. No print action either, because the
                         report is an admin capability. --}}
                    <div class="mb-5 flex flex-col gap-3">
                        <form method="GET" action="{{ route('teknisi.pengaduan.index') }}"
                            class="flex w-full max-w-md items-center gap-2.5" role="search">
                                @if (request('status'))
                                    <input type="hidden" name="status" value="{{ request('status') }}" />
                                @endif
                                @if (request('from') && request('to'))
                                    <input type="hidden" name="from" value="{{ request('from') }}" />
                                    <input type="hidden" name="to" value="{{ request('to') }}" />
                                @endif
                                <div class="relative flex-1">
                                    <label for="q" class="sr-only">{{ __('Cari pengaduan') }}</label>
                                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-on-surface-variant">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                        </svg>
                                    </div>
                                    <input id="q" type="text" name="q" value="{{ request('q') }}"
                                        placeholder="Cari judul, nomor tiket, deskripsi, pelapor, atau ruang…"
                                        class="field w-full ps-10" />
                                </div>
                                <button type="submit" class="btn-outline shrink-0">
                                    {{ __('Cari') }}
                                </button>
                        </form>

                        {{-- The two pickers share the admin toolbar's compact,
                             equal-width grid: stacked full-width on a phone,
                             two 10rem columns from sm up. Status keeps the
                             trailing slot it holds in admin, before the
                             reset. --}}
                        <div class="flex flex-wrap items-center gap-2.5">
                            <div class="grid w-full grid-cols-1 gap-2.5 sm:w-auto sm:grid-cols-[repeat(2,minmax(0,10rem))]">
                            <details class="relative w-full" id="period-dropdown"
                                x-data
                                @click.outside="$el.open = false"
                                @keydown.escape.prevent="$el.open = false">
                                <summary @class([
                                        'inline-flex min-h-11 w-full cursor-pointer select-none items-center justify-between gap-2 rounded-full border px-4 text-sm font-semibold transition-colors',
                                        'list-none bg-primary border-primary text-on-primary hover:bg-primary/90' => $period !== null,
                                        'list-none border-outline bg-surface text-primary hover:bg-primary-container/40' => $period === null,
                                    ])
                                    @if ($period !== null && ! $presetMatched) aria-current="true" @endif
                                    @if ($applied) x-data x-init="$el.focus()" @endif>
                                    <span class="min-w-0 truncate text-sm">
                                        @if ($period)
                                            {{ $period['label'] }}
                                            @if($swapped) · {{ __('rentang dibalik otomatis') }}@endif
                                        @else
                                            {{ __('Periode') }}
                                        @endif
                                    </span>
                                    <svg class="h-3.5 w-3.5 shrink-0 opacity-70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </summary>

                                <div class="absolute start-0 z-30 mt-2 w-72 sm:w-80 rounded-2xl border border-outline-variant bg-surface-container p-4 shadow-lg shadow-scrim/5">
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Pilih Periode') }}</span>
                                        @if ($period)
                                            <a href="{{ route('teknisi.pengaduan.index', array_filter(['status' => request('status'), 'q' => request('q')])) }}"
                                                class="text-xs font-semibold text-primary hover:underline">
                                                {{ __('Semua periode') }}
                                            </a>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-2 gap-1.5 mb-3" role="group" aria-label="Filter periode">
                                        @foreach (\App\Models\Complaint::presetPeriods() as $preset)
                                            @php
                                                $activePreset = $presetMatched
                                                    && $period['from'] === $preset['from']
                                                    && $period['to'] === $preset['to'];
                                            @endphp
                                            <a href="{{ route('teknisi.pengaduan.index', array_filter(['status' => request('status'), 'q' => request('q'), 'from' => $preset['from'], 'to' => $preset['to']])) }}"
                                                @class([
                                                    'chip-filter border justify-center text-center',
                                                    'border-primary bg-primary text-on-primary hover:bg-primary/90 active:bg-primary/90' => $activePreset,
                                                    'border-outline bg-surface text-primary hover:bg-primary-container/40' => ! $activePreset,
                                                ])
                                                @if ($activePreset) aria-current="true" @endif>
                                                {{ __($preset['name']) }}
                                            </a>
                                        @endforeach
                                    </div>

                                    <div class="border-t border-outline-variant/60 pt-3">
                                        <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Rentang Kustom') }}</span>
                                        <form method="GET" action="{{ route('teknisi.pengaduan.index') }}"
                                            class="flex flex-col gap-2.5"
                                            x-data="{ syncBounds() { const f = this.$refs.from, t = this.$refs.to; f.max = t.value || ''; t.min = f.value || '' } }"
                                            x-init="syncBounds()">
                                            @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                                            @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                                                                                        <div>
                                                <label for="from" class="mb-1 block text-xs font-medium text-on-surface-variant">{{ __('Dari tanggal') }}</label>
                                                <input id="from" name="from" type="date" value="{{ $period['from'] ?? '' }}" required
                                                    class="field w-full text-xs py-1.5" x-ref="from" @change="syncBounds()" />
                                            </div>
                                            <div>
                                                <label for="to" class="mb-1 block text-xs font-medium text-on-surface-variant">{{ __('Sampai tanggal') }}</label>
                                                <input id="to" name="to" type="date" value="{{ $period['to'] ?? '' }}" required
                                                    class="field w-full text-xs py-1.5" x-ref="to" @change="syncBounds()" />
                                            </div>
                                            <button type="submit" class="btn-primary mt-1 w-full justify-center">{{ __('Terapkan') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </details>

                            {{-- Status picker: the trailing capsule, vocabulary
                                 only — counts were removed from every list's
                                 menu (they belong to dashboard cards, and in a
                                 picker they were hidden behind a tap while
                                 making the trigger long). Its form re-posts the
                                 keyword and the applied period, so a pick never
                                 silently drops either. --}}
                            <form method="GET" action="{{ route('teknisi.pengaduan.index') }}"
                                class="w-full">
                                @if (request('q'))
                                    <input type="hidden" name="q" value="{{ request('q') }}" />
                                @endif
                                @if ($period !== null)
                                    <input type="hidden" name="from" value="{{ $period['from'] }}" />
                                    <input type="hidden" name="to" value="{{ $period['to'] }}" />
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

                            </div>

                            {{-- The global reset rides the filter row, after
                                 both pickers: one capsule clears status, period
                                 and keyword together. --}}
                            @if (request('q') || request('status') || request('from') || request('to'))
                                <a href="{{ route('teknisi.pengaduan.index') }}" class="btn-outline ms-auto">
                                    {{ __('Hapus filter') }}
                                </a>
                            @endif
                        </div>
                    </div>

                    @if ($complaints->isEmpty())
                        <div class="py-10 text-center">
                            <p class="text-sm text-on-surface-variant">
                                @if (request('status') || request('q') || $period !== null)
                                    Tidak ada pengaduan yang cocok dengan filter ini.
                                @else
                                    Belum ada pengaduan masuk.
                                @endif
                            </p>

                            @if (request('status') || request('q') || request('from') || request('to'))
                                <a href="{{ route('teknisi.pengaduan.index') }}" class="btn-outline mt-3">
                                    {{ __('Tampilkan Semua') }}
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="lg:hidden grid grid-cols-1 gap-3">
                            @foreach ($complaints as $complaint)
                                {{-- Same card structure as the admin list: one link
                                     (the title) with a stretched ::after tap target;
                                     a badge, never a status control. --}}
                                <div class="card p-4 card-hover group relative flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-xs font-mono font-semibold text-on-surface whitespace-nowrap">{{ $complaint->ticket_number }}</span>
                                        </div>
                                        <h2 class="mt-2 truncate font-medium text-on-surface">
                                            <a href="{{ route('teknisi.pengaduan.show', $complaint) }}"
                                                class="after:absolute after:inset-0 after:content-[''] after:rounded-2xl after:z-10 group-hover:text-primary">
                                                {{ $complaint->title }}
                                            </a>
                                        </h2>
                                        <div class="mt-2">
                                            <x-status-badge :status="$complaint->status" />
                                        </div>
                                        <p class="mt-1 text-xs text-on-surface-variant truncate">{{ $complaint->user->name }} &middot; {{ $complaint->room->name }} &middot; {{ $complaint->created_at->translatedFormat('d M Y') }}</p>
                                    </div>
                                    <svg class="h-5 w-5 shrink-0 text-on-surface-variant" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </div>
                            @endforeach
                        </div>

                        <div class="hidden lg:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-outline-variant">
                                <caption class="sr-only">Daftar pengaduan teknisi</caption>
                                <thead>
                                    <tr>
                                        <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">No. Tiket</th>
                                        <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Judul</th>
                                        <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Pelapor</th>
                                        <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Ruang</th>
                                        <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-2.5 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Tanggal</th>
                                        <th scope="col" class="px-2.5 py-3 text-right text-xs font-medium text-on-surface-variant uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-surface-container divide-y divide-outline-variant">
                                    @foreach ($complaints as $complaint)
                                        <tr class="transition-colors hover:bg-surface-container-high">
                                            <td class="px-2.5 py-3 text-sm font-mono font-semibold whitespace-nowrap text-on-surface">{{ $complaint->ticket_number }}</td>
                                            <td class="px-2.5 py-3 text-sm font-medium text-on-surface max-w-[12rem] truncate">{{ $complaint->title }}</td>
                                            <td class="px-2.5 py-3 text-sm text-on-surface-variant">{{ $complaint->user->name }}</td>
                                            <td class="px-2.5 py-3 text-sm text-on-surface-variant">{{ $complaint->room->name }}</td>
                                            <td class="px-2.5 py-3 whitespace-nowrap"><x-status-badge :status="$complaint->status" /></td>
                                            <td class="px-2.5 py-3 text-sm whitespace-nowrap text-on-surface-variant">{{ $complaint->created_at->translatedFormat('d M Y') }}</td>
                                            {{-- 44px tap target without growing the row: the cell
                                                 drops its vertical padding, the link supplies it,
                                                 and -me-2 keeps Detail aligned with Aksi. --}}
                                            <td class="px-2.5 text-right">
                                                <a href="{{ route('teknisi.pengaduan.show', $complaint) }}"
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
        </div>
    </div>
</x-app-layout>
