@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'p-1.5 bg-surface-container', 'menuId' => null])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    default => $width,
};
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false" {{-- The focus restore must stay guarded: unguarded, every page-wide ESC --}} {{-- steals focus from a dialog's own opener-restore (x-modal). --}} @keydown.escape.window="if (open) { open = false; $nextTick(() => ($refs.trigger ?? document.querySelector('button[aria-haspopup=true]'))?.focus()) }">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            {{-- Pass `menu-id` from the consumer, then quote the same id in
                 the trigger's `aria-controls`; without one, the popup omits
                 the id rather than render empty `id=""` (and with several
                 dropdowns on one page, each names its own popup). --}}
            {{-- Same in-page menu shell as <x-select-menu>: 2xl corners,
                 a hairline border and the soft scrim shadow. --}}
            class="absolute z-50 mt-2 {{ $width }} rounded-2xl bg-surface-container border border-outline-variant shadow-lg shadow-scrim/5 {{ $alignmentClasses }}"
            x-cloak
            @if ($menuId) id="{{ $menuId }}" @endif
            {{-- Disclosure semantics, not `role="menu"`: items are plain links
                 and buttons, so AT keeps TAB-nav instead of switching into the
                 arrow-key menu mode we do not implement. --}}
            x-trap="open"
            @click="open = false">
        <div class="rounded-2xl {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
