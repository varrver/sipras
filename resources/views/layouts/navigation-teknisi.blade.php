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
                <a href="{{ route('teknisi.pengaduan.index') }}" class="flex items-center gap-2 transition hover:opacity-75">
                    <x-application-logo class="h-8 w-8 text-primary" />
                    <span class="text-lg font-bold tracking-tight text-on-surface">SIPRAS</span>
                </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:items-center sm:gap-1.5">
                    <x-nav-link :href="route('teknisi.pengaduan.index')" :active="request()->routeIs('teknisi.pengaduan.*')">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.165-.296 1.936-.296a7.5 7.5 0 1 0-7.5 7.5c.716 0 1.31-.084 1.88-.245" />
                        </svg>
                        {{ __('Pengaduan Teknisi') }}
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
    <div id="responsive-menu" class="sm:hidden">
        <div x-cloak x-show="open" class="menu-panel"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2">
        <div class="px-4 py-3 space-y-1">
            <x-responsive-nav-link :href="route('teknisi.pengaduan.index')" :active="request()->routeIs('teknisi.pengaduan.*')">
                {{ __('Pengaduan Teknisi') }}
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
