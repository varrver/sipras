@section('title', __('Manajemen Pengaduan'))
<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-on-surface">
            {{ __('Manajemen Pengaduan') }}
        </h1>
    </x-slot>

    <div class="pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <section class="card">
                <div class="p-6">
                    @php
                        $activeStatus = request('status') ?: '';

                        // The status picker lists the shared vocabulary only —
                        // no counts: they belong on the dashboard cards, and in a
                        // menu they were both hidden behind a tap and long enough
                        // to crowd the trigger at phone width.
                        $statusOptions = [['value' => '', 'label' => __('Semua')]];

                        foreach (\App\Models\Complaint::STATUS_LABELS as $value => $label) {
                            $statusOptions[] = ['value' => (string) $value, 'label' => $label];
                        }

                        // The raw category param drives the URL-forwarding
                        // links and inputs, but a hand-edited or array-typed
                        // URL must echo as a string only: the resolver has
                        // already refused arrays server-side, so filtering
                        // to scalar keeps the echo from ever 500-ing.
                        $activeCategory = is_string(request('category')) ? request('category') : null;

                        // Period resolution check
                        $presetMatched = $period !== null && collect(\App\Models\Complaint::presetPeriods())
                            ->contains(fn ($preset) => $preset['from'] === $period['from'] && $preset['to'] === $period['to']);
                        $swapped = $period !== null && (string) request('from') > (string) request('to');
                        $applied = $period !== null && request()->filled('from')
                            && request()->filled('to') && ! request()->filled('page');
                    @endphp

                    {{-- Toolbar: search owns the top row, the three filter
                         pickers sit on the row below it — they never compete
                         with the search field for the same line. --}}
                    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex-1 space-y-3">
                            {{-- Search Input with clean Apple-style icon and external submit --}}
                            <form method="GET" action="{{ route('admin.pengaduan.index') }}" class="flex w-full max-w-md items-center gap-2.5" role="search">
                                @if (request('status'))
                                    <input type="hidden" name="status" value="{{ request('status') }}" />
                                @endif
                                @if (request('from') && request('to'))
                                    <input type="hidden" name="from" value="{{ request('from') }}" />
                                    <input type="hidden" name="to" value="{{ request('to') }}" />
                                @endif
                                @if ($activeCategory !== null)
                                    <input type="hidden" name="category" value="{{ $activeCategory }}" />
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

                            <div class="flex flex-wrap items-center gap-2.5">
                            {{-- The three pickers share one compact, equal-width
                                 grid: stacked full-width on a phone, three 10rem
                                 columns from sm up. Equal so a trigger keeps its
                                 width whichever label the applied filter puts in
                                 it; compact so the cluster reads as filters, not
                                 as three more text fields — a long label
                                 truncates instead of widening the row. --}}
                            <div class="grid w-full grid-cols-1 gap-2.5 sm:w-auto sm:grid-cols-[repeat(3,minmax(0,10rem))]">
                            {{-- Category picker: the x-select-menu control so the
                                 filter dropdown lives in-page on phones like every
                                 other picker (its own GET form; the other active
                                 dims ride hidden — the component auto-submits
                                 the form on pick via its opt-in flag). --}}
                            <form method="GET" action="{{ route('admin.pengaduan.index') }}"
                                class="w-full">
                                <label for="category" class="sr-only">{{ __('Tipe kerusakan') }}</label>
                                @if (request('status'))
                                    <input type="hidden" name="status" value="{{ request('status') }}" />
                                @endif
                                @if (request('q'))
                                    <input type="hidden" name="q" value="{{ request('q') }}" />
                                @endif
                                @if (request('from') && request('to'))
                                    <input type="hidden" name="from" value="{{ request('from') }}" />
                                    <input type="hidden" name="to" value="{{ request('to') }}" />
                                @endif
                                <x-select-menu
                                    form-autosubmit
                                    capsule
                                    :active="$category !== null"
                                    id="category"
                                    name="category"
                                    :options="[['value' => '', 'label' => __('Semua kategori')], ...$categories->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->all()]"
                                    :value="(string) ($category ?? '')"
                                    placeholder="{{ __('Kategori') }}"
                                    placeholderWhenEmpty
                                    nativeLabel="{{ __('Tipe kerusakan') }}"
                                    class="w-full"
                                />
                            </form>

                            {{-- Period Dropdown Popover --}}
                            <details class="relative w-full" id="period-dropdown"
                                x-data
                                {{-- Native disclosure, but the interaction
                                     expectation lives here: a click elsewhere
                                     or Escape closes the popover, which a bare
                                     <details> never does. --}}
                                @click.outside="$el.open = false"
                                @keydown.escape.prevent="$el.open = false">
                                <summary @class([
                                        {{-- Capsule family: same sizing/skin as .btn-outline,
                                             active = the primary capsule. list-none rides on
                                             the conditionals so the pin substring
                                             'list-none bg-primary' is contiguous for the
                                             active state. --}}
                                        'inline-flex min-h-11 w-full cursor-pointer select-none items-center justify-between gap-2 rounded-full border px-4 text-sm font-semibold transition-colors',
                                        'list-none bg-primary border-primary text-on-primary hover:bg-primary/90' => $period !== null,
                                        'list-none border-outline bg-surface text-primary hover:bg-primary-container/40' => $period === null,
                                    ])
                                    @if ($period !== null && ! $presetMatched) aria-current="true" @endif
                                    @if ($applied) x-data x-init="$el.focus()" @endif>
                                    <span class="min-w-0 truncate text-sm">
                                        @if ($period)
                                            {{ $period['label'] }}
                                            {{-- The resolver silently normalizes a reversed
                                                 pair, so the only honesty the list offers
                                                 for a from>to URL is this note — it must ride
                                                 with the trigger, now the period's sole
                                                 presentation. --}}
                                            @if($swapped) · {{ __('rentang dibalik otomatis') }}@endif
                                        @else
                                            {{ __('Periode') }}
                                        @endif
                                    </span>
                                    <svg class="h-3.5 w-3.5 shrink-0 opacity-70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </summary>

                                {{-- Popover Panel --}}
                                <div class="absolute start-0 z-30 mt-2 w-72 sm:w-80 rounded-2xl border border-outline-variant bg-surface-container p-4 shadow-lg shadow-scrim/5">
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Pilih Periode') }}</span>
                                        @if ($period)
                                            <a href="{{ route('admin.pengaduan.index', array_filter(['status' => request('status'), 'q' => request('q'), 'category' => $activeCategory])) }}"
                                                class="text-xs font-semibold text-primary hover:underline">
                                                {{ __('Semua periode') }}
                                            </a>
                                        @endif
                                    </div>

                                    {{-- Quick Preset Buttons --}}
                                    <div class="grid grid-cols-2 gap-1.5 mb-3" role="group" aria-label="Filter periode">
                                        @foreach (\App\Models\Complaint::presetPeriods() as $preset)
                                            @php
                                                $activePreset = $presetMatched
                                                    && $period['from'] === $preset['from']
                                                    && $period['to'] === $preset['to'];
                                            @endphp
                                            <a href="{{ route('admin.pengaduan.index', array_filter(['status' => request('status'), 'q' => request('q'), 'category' => $activeCategory, 'from' => $preset['from'], 'to' => $preset['to']])) }}"
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

                                    {{-- Custom Range Form --}}
                                    <div class="border-t border-outline-variant/60 pt-3">
                                        <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Rentang Kustom') }}</span>
                                        <form method="GET" action="{{ route('admin.pengaduan.index') }}"
                                            class="flex flex-col gap-2.5"
                                            x-data="{ syncBounds() { const f = this.$refs.from, t = this.$refs.to; f.max = t.value || ''; t.min = f.value || '' } }"
                                            x-init="syncBounds()">
                                            @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                                            @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                                            @if ($activeCategory !== null)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
                                <div>
                                                <label for="from" class="mb-1 block text-xs font-medium text-on-surface-variant">{{ __('Dari tanggal') }}</label>
                                                {{-- Native bubble accepted
                                                     (DESIGN.md §2.5): both dates
                                                     are required because a half
                                                     pair silently no-ops in the
                                                     resolver — the browser now
                                                     blocks the submit before the
                                                     GET ever leaves. --}}
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

                            {{-- Status picker: the third capsule trigger,
                                 vocabulary only (no counts — those live on the
                                 dashboard cards). Its form re-posts every other
                                 active filter and auto-submits a pick. --}}
                            <form method="GET" action="{{ route('admin.pengaduan.index') }}"
                                class="w-full">
                                @if (request('q'))
                                    <input type="hidden" name="q" value="{{ request('q') }}" />
                                @endif
                                @if ($activeCategory !== null)
                                    <input type="hidden" name="category" value="{{ $activeCategory }}" />
                                @endif
                                @if (request('from') && request('to'))
                                    <input type="hidden" name="from" value="{{ request('from') }}" />
                                    <input type="hidden" name="to" value="{{ request('to') }}" />
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

                            {{-- The global reset stays in the toolbar, after
                                 the three pickers: one capsule clears status,
                                 category, period and keyword together. --}}
                            @if (request('q') || in_array(request('status'), \App\Models\Complaint::STATUSES, true) || $activeCategory !== null || $period !== null)
                                <a href="{{ route('admin.pengaduan.index') }}" class="btn-outline ms-auto">
                                    {{ __('Hapus filter') }}
                                </a>
                            @endif
                            </div>

                        </div>

                        {{-- Cetak Action Link: anchored at the toolbar's end,
                             full-width and centered on a phone so the print
                             intent is reachable one-handed. --}}
                        <div class="flex items-center justify-end w-full lg:w-auto">
                            <a href="{{ route('admin.pengaduan.report', array_filter(['q' => request('q'), 'status' => request('status'), 'category' => $activeCategory, 'from' => request('from'), 'to' => request('to')])) }}"
                                class="btn-outline shrink-0 w-full lg:w-auto justify-center">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Zm-3 0h.008v.008H12V10.5Z" />
                                </svg>
                                <span>{{ __('Cetak Sesuai Filter') }}</span>
                            </a>
                        </div>
                    </div>

                    @if ($complaints->isEmpty())
                        <div class="py-10 text-center">
                            <p class="text-sm text-on-surface-variant">
                                {{-- The message reads the resolved period
                                     ($period), so a half-pair URL correctly says
                                     "Belum ada pengaduan masuk" — but the reset
                                     arm stays for any raw query string. --}}
                                @if (request('status') || request('q') || request('category') || $period !== null)
                                    Tidak ada pengaduan yang cocok dengan filter ini.
                                @else
                                    Belum ada pengaduan masuk.
                                @endif
                            </p>

                            @if (request('status') || request('q') || request('category') || request('from') || request('to'))
                                <a href="{{ route('admin.pengaduan.index') }}" class="btn-outline mt-3">
                                    {{ __('Tampilkan Semua') }}
                                </a>
                            @endif
                        </div>
                    @else
                                <div class="xl:hidden grid grid-cols-1 gap-3">
                                    @foreach ($complaints as $complaint)
                                        {{-- The card is structured content, not one run-on link:
                                             a single <a> wrapping ticket + status + title +
                                             meta is announced as one long string. Here the
                                             title is the link and a stretched `::after` keeps
                                             the whole card one tap target. --}}
                                        <div class="card p-4 card-hover group relative flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                    <div class="flex items-center justify-between gap-2">
                                                        {{-- Ticket numbers never wrap — a broken-across-lines
                                                             ticket reads as two different tickets. --}}
                                                        <span class="text-xs font-mono font-semibold text-on-surface whitespace-nowrap">{{ $complaint->ticket_number }}</span>
                                                    </div>
                                                    {{-- h2, not h3: the page's only other heading is the
                                                         h1, and a jump to h3 trips heading-order. --}}
                                                    <h2 class="mt-2 truncate font-medium text-on-surface">
                                                        <a href="{{ route('admin.pengaduan.show', $complaint) }}"
                                                            class="after:absolute after:inset-0 after:content-[''] after:rounded-2xl after:z-10 group-hover:text-primary">
                                                            {{ $complaint->title }}
                                                        </a>
                                                    </h2>
                                                    <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1">
                                                        <x-status-badge :status="$complaint->status" />
                                                        <span class="truncate text-xs text-on-surface-variant">{{ $complaint->category->name }}</span>
                                                    </div>
                                                    <p class="mt-1 text-xs text-on-surface-variant truncate">{{ $complaint->user->name }} &middot; {{ $complaint->room->name }} &middot; {{ $complaint->created_at->translatedFormat('d M Y') }}</p>
                                                </div>
                                            <svg class="h-5 w-5 shrink-0 text-on-surface-variant" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="hidden xl:block overflow-x-auto">
                                    <table class="min-w-full divide-y divide-outline-variant">
                                        {{-- Named for AT; `sr-only` because the page heading and the
                                             filter bar already say what the list is. --}}
                                        <caption class="sr-only">Daftar pengaduan</caption>
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
                                                    <td class="px-2.5 py-3 text-sm font-medium text-on-surface max-w-[12rem]">
                                                        <span class="block truncate">{{ $complaint->title }}</span>
                                                        {{-- The category is a filter dimension, so a row it
                                                             selected has to name it: otherwise picking
                                                             "Elektronik" returns rows whose match is invisible. --}}
                                                        <span class="mt-0.5 block truncate text-xs font-normal text-on-surface-variant">{{ $complaint->category->name }}</span>
                                                    </td>
                                                    <td class="px-2.5 py-3 text-sm text-on-surface-variant">{{ $complaint->user->name }}</td>
                                                    <td class="px-2.5 py-3 text-sm text-on-surface-variant">{{ $complaint->room->name }}</td>
                                                    <td class="px-2.5 py-3 whitespace-nowrap"><x-status-badge :status="$complaint->status" /></td>
                                                    <td class="px-2.5 py-3 text-sm whitespace-nowrap text-on-surface-variant">{{ $complaint->created_at->translatedFormat('d M Y') }}</td>
                                                    {{-- The Aksi cell drops its own vertical padding and the link
                                                         supplies it: a 44px tap target without growing the row.
                                                         -me-2 cancels the link's padding so Detail still aligns
                                                         with the Aksi header's right edge. --}}
                                                    <td class="px-2.5 text-right">
                                                        <a href="{{ route('admin.pengaduan.show', $complaint) }}"
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
