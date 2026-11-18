{{-- Pinned on mobile too: the wrapper is the sticky element (the drawer and
     backdrop inside it are `fixed`, so the header is its only in-flow child at
     this width). Sticking the header itself would not work — a sticky child
     can only travel inside its containing block, and that block would be just
     the height of the header. The whole container spans the page, so the
     hamburger stays reachable however far the list is scrolled.
     z-30 at mobile only: the drawer (z-50) and its backdrop (z-40) must stay on
     top, and at lg the raised stacking context is reset so page dialogs/menus
     keep their own layering. --}}
<div x-data="{ open: false }" class="bg-surface-container-low sticky top-0 z-30 lg:z-auto lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 lg:border-r lg:border-outline-variant">
    {{-- No-JS drawer switch — the same hidden-checkbox technique the siswa and
         teknisi menus use. The checkbox is the single source of truth for the
         mobile drawer: the top-bar hamburger, the scrim and the drawer's close
         are all labels for it, so with scripts off a tap opens and closes the
         drawer through CSS (app.css), and a keyboard user can focus the control
         itself and press Space. Alpine only mirrors it (`x-init` catches clicks
         that landed before boot) to add ARIA state, Escape and the focus return.
         `lg:hidden`: the control exists only where the drawer does, so the
         desktop sidebar never carries a stray tab stop. --}}
    <input type="checkbox" id="admin-nav-check" x-ref="navtoggle"
        class="sr-only lg:hidden"
        x-init="open = $el.checked"
        @change="open = $el.checked; if (!open) $nextTick(() => $refs.toggle.focus())"
        aria-label="{{ __('Menu navigasi admin') }}"
        aria-controls="admin-drawer"
        :aria-expanded="open ? 'true' : 'false'">

    <!-- Mobile top bar -->
    {{-- h-16 / h-5: the same bar height and trigger glyph as the siswa and
         teknisi top bars, so switching roles never shifts the bar by 4px or
         changes the hamburger's weight. --}}
    <header class="flex h-16 items-center justify-between border-b border-outline-variant px-4 lg:hidden">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 transition hover:opacity-75">
            <x-application-logo class="h-8 w-8 text-primary" />
            <span class="text-lg font-bold tracking-tight text-on-surface">SIPRAS</span>
        </a>
        {{-- A label, not a button: the native checkbox behind it is the control,
             so the hamburger works before (and without) Alpine. --}}
        <label for="admin-nav-check" x-ref="toggle" tabindex="-1"
            class="admin-nav-trigger inline-flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-on-surface-variant transition hover:bg-surface-container-high">
            <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </label>
    </header>

    <!-- Mobile backdrop -->
    {{-- Also a label: tapping the scrim toggles the same checkbox, so closing by
         tapping outside needs no script. Decorative, hence aria-hidden — the
         dialog below carries the name. --}}
    <label for="admin-nav-check" aria-hidden="true" x-show="open" x-transition.opacity
        class="admin-nav-backdrop fixed inset-0 z-40 bg-scrim/50 lg:hidden" style="display: none;"></label>

    <!-- Mobile drawer -->
    {{-- A `role="dialog"` may not sit on <aside>: its implicit `complementary`
         role conflicts with it, so axe flags aria-allowed-role. Use a plain
         <div>, which has no implicit role to clash with the modal dialog.
         The scrim covers the page in both modes, so the dialog is honestly
         modal in the scriptless state too — its close control and every link
         live inside it. --}}
    <div id="admin-drawer" role="dialog" aria-modal="true" aria-label="{{ __('Navigasi admin') }}" x-trap.noscroll="open" x-show="open" @keydown.escape.window="if (open) { $refs.navtoggle.checked = false; open = false; $nextTick(() => $refs.toggle.focus()) }" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-full" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-full" class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-surface-container-low lg:hidden" style="display: none;">
            {{-- Same 64px row as the bar it covers: opening the drawer must not
                 move the logo or the close glyph down by 4px. --}}
            <div class="flex h-16 items-center justify-between px-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 transition hover:opacity-75">
                    <x-application-logo class="h-8 w-8 text-primary" />
                    <span class="text-lg font-bold tracking-tight text-on-surface">SIPRAS</span>
                </a>
                {{-- A label (so the tap toggles the checkbox in either mode) that
                     also presents itself as a button: with Alpine on, Enter/Space
                     close it like any button; without Alpine it is still
                     pointer-operable, and Escape (script) or the hidden checkbox
                     (no script) remain the keyboard paths. --}}
                <label for="admin-nav-check" role="button" tabindex="0"
                    aria-label="{{ __('Tutup menu') }}"
                    @keydown.enter.prevent="$refs.navtoggle.checked = false; open = false; $nextTick(() => $refs.toggle.focus())"
                    @keydown.space.prevent="$refs.navtoggle.checked = false; open = false; $nextTick(() => $refs.toggle.focus())"
                    class="inline-flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-on-surface-variant transition hover:bg-surface-container-high">
                <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </label>
        </div>
        @include('layouts.partials.sidebar-nav')
    </div>

    <!-- Desktop sidebar -->
    <aside class="hidden h-full flex-col lg:flex">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 border-b border-outline-variant px-5 py-4 transition hover:opacity-75">
            <x-application-logo class="h-8 w-8 text-primary" />
            <span class="text-lg font-bold tracking-tight text-on-surface">SIPRAS</span>
        </a>
        @include('layouts.partials.sidebar-nav')
    </aside>
</div>
