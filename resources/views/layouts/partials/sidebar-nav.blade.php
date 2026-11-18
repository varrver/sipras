@php
    // Active pill: the primary TINT with the deep container ink. A primary-
    // on-wash pair at 10% stays only barely AA on the current blue (4.82:1);
    // the container/ink pair (11.2:1) is guaranteed AA for whatever hue the
    // primary token later becomes.
    $navClass = static function (bool $active): string {
        return $active
                ? 'flex w-full items-center gap-3 rounded-full px-3 py-3 text-sm font-semibold bg-primary-container text-on-primary-container transition active:bg-primary/20'
            // Hover wash is `surface`, not the elevation steps: the sidebar
            // panel is white and all three container steps are #ffffff, so
            // the old hover was white-on-white (invisible). Ladder: white →
            // surface hover → secondary-container press.
            : 'flex w-full items-center gap-3 rounded-full px-3 py-3 text-sm font-medium text-on-surface-variant transition hover:bg-surface hover:text-on-surface active:bg-secondary-container';
    };

    $isDashboard = request()->routeIs(['dashboard', 'admin.dashboard']);
    $isComplaints = request()->routeIs('admin.pengaduan.index', 'admin.pengaduan.show', 'admin.pengaduan.status');
    
    $isCategories = request()->routeIs('admin.master.categories.*');
    $isRooms = request()->routeIs('admin.master.rooms.*');
    $isUsers = request()->routeIs('admin.master.users.*');
@endphp

<nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="{{ __('Navigasi admin') }}">
    <a href="{{ route('dashboard') }}" class="{{ $navClass($isDashboard) }}" @if ($isDashboard) aria-current="page" @endif>
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
        </svg>
        {{ __('Dashboard') }}
    </a>

    <p class="pt-5 pb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Pelayanan') }}</p>

    <a href="{{ route('admin.pengaduan.index') }}" class="{{ $navClass($isComplaints) }}" @if ($isComplaints) aria-current="page" @endif>
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        </svg>
        {{ __('Pengaduan') }}
    </a>

    <p class="pt-5 pb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Master Data') }}</p>

    <a href="{{ route('admin.master.categories.index') }}" class="{{ $navClass($isCategories) }}" @if ($isCategories) aria-current="page" @endif>
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
        </svg>
        {{ __('Kategori') }}
    </a>

    <a href="{{ route('admin.master.rooms.index') }}" class="{{ $navClass($isRooms) }}" @if ($isRooms) aria-current="page" @endif>
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
        </svg>
        {{ __('Ruang') }}
    </a>

    <a href="{{ route('admin.master.users.index') }}" class="{{ $navClass($isUsers) }}" @if ($isUsers) aria-current="page" @endif>
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>
        {{ __('Pengguna') }}
    </a>
</nav>

<div class="border-t border-outline-variant p-3">
    <div class="flex items-center gap-3 rounded-xl px-3 py-2">
        <x-account-chip variant="detailed" />
    </div>

    <a href="{{ route('profile.edit') }}" class="mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-on-surface-variant transition hover:bg-surface hover:text-on-surface active:bg-secondary-container">
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
        </svg>
        {{ __('Profil') }}
    </a>

    <form method="POST" action="{{ route('logout') }}" class="mt-2">
        @csrf
        <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-on-surface-variant transition hover:bg-error-container hover:text-on-error-container active:bg-error-container/80">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
            </svg>
            {{ __('Keluar') }}
        </button>
    </form>
</div>