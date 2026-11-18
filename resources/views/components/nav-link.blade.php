@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center gap-2 rounded-full bg-secondary-container px-4 py-3 text-sm font-semibold text-on-secondary-container transition active:bg-primary/10'
            : 'inline-flex items-center gap-2 rounded-full px-4 py-3 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface active:bg-surface-container-highest';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if (($active ?? false)) aria-current="page" @endif>
    {{ $slot }}
</a>