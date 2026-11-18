@props(['as' => 'a', 'type' => 'submit'])

@php
// The row skin mirrors <x-select-menu>'s option rows: rounded-xl, 1.5-unit
// padding, and the neutral secondary-container hover wash — in the flat
// white token set the old `surface` hover was white-on-white.
$classes = 'block w-full rounded-xl px-3 py-2.5 text-start text-sm text-on-surface-variant transition hover:bg-secondary-container hover:text-on-surface active:bg-secondary-container/80';
@endphp

@if ($as === 'button')
    {{-- No `role="menuitem"`: the popup is disclosure-themed (Plain links +
         buttons keep native TAB semantics — see dropdown.blade.php). --}}
    <button {{ $attributes->merge(['type' => $type, 'class' => $classes]) }}>{{ $slot }}</button>
@else
    <a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@endif