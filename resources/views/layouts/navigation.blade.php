<nav x-data="{ open: false, scrolled: false }" aria-label="{{ __('Navigasi utama') }}" @scroll.window="scrolled = window.scrollY > 8" @keydown.escape.window="if ($refs.navtoggle?.checked) { $refs.navtoggle.checked = false; open = false; $nextTick(() => $refs.navtrigger?.focus()) }" :class="scrolled ? 'border-b border-outline-variant' : ''" class="bg-surface-container/80 backdrop-blur-md sticky top-0 z-30">
    {{-- No-JS hamburger: this hidden checkbox is the single source of truth
         for the mobile menu — a sibling of both the trigger label (below)
         and the #responsive-menu panel, so plain CSS can toggle them with
         sentinel-checked selectors (app.css, mobile-only media query).
         Scripts-off, clicking the label still opens the panel and flips the
         icon; Alpine adds only the enhancement layer: ARIA state and ESC.
         x-init catches clicks that landed before Alpine booted. --}}
    <input type="checkbox" id="nav-toggle" class="sr-only" x-ref="navtoggle" x-init="open = $el.checked" @change="open = $event.target.checked" :aria-label="open ? 'Tutup menu' : 'Buka menu'">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-4">
                <!-- Logo -->
                <div class="shrink-0 flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 transition hover:opacity-75">
                    <x-application-logo class="h-8 w-8 text-primary" />
                    <span class="text-lg font-bold tracking-tight text-on-surface">SIPRAS</span>
                </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:items-center sm:gap-1.5">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs(['dashboard', 'admin.dashboard', 'siswa.dashboard'])">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="route('siswa.pengaduan.index')" :active="request()->routeIs('siswa.pengaduan.*')">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        {{ __('Pengaduan Saya') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48" menu-id="account-menu">
                    <x-slot name="trigger">
                        <button type="button" x-ref="trigger" class="flex min-h-11 items-center gap-2.5 rounded-full py-1.5 ps-1.5 pe-3 text-sm font-semibold text-on-surface transition hover:bg-surface-container-high active:bg-surface-container-highest" aria-haspopup="true" aria-controls="account-menu" :aria-expanded="open ? 'true' : 'false'" :class="open ? 'bg-surface-container-high' : ''">
                            <x-account-chip />
                            <svg class="fill-current h-4 w-4 text-on-surface-variant transition-transform" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                        <button type="submit" class="block w-full rounded-xl px-3 py-2.5 text-start text-sm text-on-surface-variant transition hover:bg-error-container hover:text-on-error-container active:bg-error-container/80">
                            {{ __('Keluar') }}
                        </button>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="flex items-center sm:hidden">
                {{-- A label for the hidden checkbox, not a button with a JS
                     click handler: the toggle works with scripts off. ARIA
                     stays Alpine-mirrored from the checkbox's checked state. --}}
                <label for="nav-toggle" x-ref="navtrigger" tabindex="-1" aria-controls="responsive-menu" :aria-expanded="open ? 'true' : 'false'" class="nav-trigger-label inline-flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container-high transition">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path class="nav-icon-open inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path class="nav-icon-close hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </label>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    {{-- Motion note: the outer div keeps the sm-visibility boundary
         (a bare `sm:hidden`) and the inner div owns open/close with an
         x-transition — the same enter/leave the account dropdown and the
         admin drawer run (200 ms ease-out in, 150 ms ease-out back), so all
         three menus in the shell move alike. `x-cloak` keeps the panel
         closed pre-boot, standing in for x-show's own initial hide. --}}
    <div id="responsive-menu" class="sm:hidden">
        <div x-cloak x-show="open" class="menu-panel"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2">
        <div class="px-4 py-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs(['dashboard', 'admin.dashboard', 'siswa.dashboard'])">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('siswa.pengaduan.index')" :active="request()->routeIs('siswa.pengaduan.*')">
                {{ __('Pengaduan Saya') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="mt-2 rounded-xl bg-surface-container-low px-4 py-3">
            <div class="pb-2">
                <div class="flex items-center gap-2.5">
                    <x-account-chip variant="detailed" />
                </div>
            </div>

            <div class="space-y-1">
                {{-- Same row skin as the desktop account menu: the drawer is a
                     second entry point to the same two rows, so they must not
                     change shape with the viewport. --}}
                <x-dropdown-link :href="route('profile.edit')">
                    {{ __('Profil') }}
                </x-dropdown-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="block w-full rounded-xl px-3 py-2.5 text-start text-sm text-on-surface-variant transition hover:bg-error-container hover:text-on-error-container active:bg-error-container/80">
                        {{ __('Keluar') }}
                    </button>
                </form>
            </div>
        </div>
        </div>
    </div>
</nav>