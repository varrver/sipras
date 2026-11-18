{{-- The single home of the account avatar token block (bg-primary-container
     circle + initial) so a token migration edits one file, not three navs.
     Variants:
       inline   — dropdown trigger: avatar + name, name revealed from `md`.
       detailed — identity block: avatar, name and email, truncating. --}}
@props(['variant' => 'inline'])

@php $user = auth()->user(); @endphp

{{-- The initial letter is decoration — the readable name carries the
     accessible name, so the circle stays out of the reading order. --}}
<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-container text-sm font-bold text-on-primary-container" aria-hidden="true">
    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
</span>

@if ($variant === 'detailed')
    <div class="min-w-0">
        <p class="truncate text-sm font-semibold text-on-surface">{{ $user->name }}</p>
        <p class="truncate text-xs text-on-surface-variant">{{ $user->email }}</p>
    </div>
@else
    <span class="hidden md:block">{{ $user->name }}</span>
@endif
