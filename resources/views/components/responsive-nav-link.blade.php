@props(['active', 'as' => 'a', 'type' => 'submit'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg bg-secondary-container px-4 py-2.5 text-start text-base font-semibold text-on-secondary-container transition active:bg-primary/10'
            // Same white-token trap as dropdown-link: the panel behind these
            // items is white, so the hover wash must be the surface gray.
            : 'block w-full rounded-lg px-4 py-2.5 text-start text-base font-medium text-on-surface-variant transition hover:bg-surface hover:text-on-surface active:bg-secondary-container';
@endphp

@if ($as === 'button')
    <button {{ $attributes->merge(['type' => $type, 'class' => $classes]) }}>{{ $slot }}</button>
@else
    <a {{ $attributes->merge(['class' => $classes]) }} @if (($active ?? false)) aria-current="page" @endif>{{ $slot }}</a>
@endif