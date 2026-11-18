@props([
    'code',
    'title',
    'message',
    'tone' => 'neutral',
])

@php
    // This card *is* the error page, so a blank `title` or `message` ships a page
    // whose heading says nothing — and `@props` leaves an unpassed prop undefined,
    // so without this the failure would be a bare "Undefined variable $title" far
    // from the cause. WCAG 2.4.2, and axe cannot see it: a blank title still yields
    // the non-empty `<title> — SIPRAS</title>`, which `document-title` accepts.
    $missing = collect(['code' => $code ?? '', 'title' => $title ?? '', 'message' => $message ?? ''])
        ->filter(fn ($value) => trim((string) $value) === '')
        ->keys()
        ->all();

    if ($missing !== []) {
        throw new LogicException(
            'The route ['.(request()->route()?->getName() ?? 'unnamed route').'] rendered '.
            'x-error-card without '.implode(' and ', $missing).'. Every error page names '.
            'itself (WCAG 2.4.2) — see DESIGN.md §2.6.'
        );
    }

    // Recovery is role-aware: whoever lands here gets the shortest way back into
    // their own part of the app, and a guest gets the sign-in form. Teknisi has
    // no dashboard of its own — the queue is its home (same dispatch as the
    // shared /dashboard route) — so sending it to the student dashboard would
    // bounce it straight into another 403.
    $role = auth()->user()?->role;

    $homeHref = match ($role) {
        null => route('login'),
        'admin' => route('admin.dashboard'),
        'teknisi' => route('teknisi.pengaduan.index'),
        default => route('siswa.dashboard'),
    };

    $homeLabel = match ($role) {
        null => __('Masuk ke SIPRAS'),
        'teknisi' => __('Ke Antrean'),
        default => __('Ke Dashboard'),
    };

    // Tone carries the kind of failure, not decoration: a permission wall and an
    // expired session are warnings, a server fault is an error, "not found" is neutral.
    $tiles = [
        'neutral' => 'bg-secondary-container text-on-secondary-container',
        'warning' => 'bg-warning-container text-on-warning-container',
        'error' => 'bg-error-container text-on-error-container',
    ];

    $icons = ['403' => 'lock', '404' => 'search', '419' => 'clock', '500' => 'alert'];
    $icon = $icons[$code] ?? 'alert';
@endphp

<div class="text-center">
    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl {{ $tiles[$tone] ?? $tiles['neutral'] }}">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            @switch($icon)
                @case('lock')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    @break
                @case('search')
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    @break
                @case('clock')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    @break
                @default
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            @endswitch
        </svg>
    </span>

    <p class="mt-5 text-4xl font-bold tracking-tight text-on-surface">{{ $code }}</p>
    <h1 class="mt-1.5 text-lg font-semibold text-on-surface">{{ $title }}</h1>
    <p class="mt-2 text-sm text-on-surface-variant">{{ $message }}</p>

    <a href="{{ $homeHref }}" class="mt-6 btn-primary w-full sm:w-auto">{{ $homeLabel }}</a>
</div>
