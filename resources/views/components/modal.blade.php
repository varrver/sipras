@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
    // `name` is the dialog's label *and* its event contract: `aria-labelledby` and
    // the `open-modal`/`close-modal` listeners are both built from it. Blank, it
    // renders `aria-labelledby="-title"` — a dialog that points at nothing. An
    // omitted prop is left undefined by `@props`, so the alternative failure is a
    // bare "Undefined variable $name" inside the Alpine directives.
    if (trim((string) ($name ?? '')) === '') {
        throw new LogicException(
            'The route ['.(request()->route()?->getName() ?? 'unnamed route').'] rendered '.
            'x-modal without a name. `aria-labelledby` is built from it, so the dialog '.
            'would point at nothing — see DESIGN.md §3.'
        );
    }
@endphp

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    x-data="{
        show: @js($show),
        // What opened the dialog — remembered on open so dismissal (confirm,
        // cancel or Escape) hands focus back to it instead of dropping it on
        // the page root, where keyboard users restart from the top.
        opener: null,
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            // Focus has not moved yet at watch time, so activeElement is
            // still the trigger — snapshot it before anything else.
            opener = document.activeElement;
            document.body.classList.add('overflow-y-hidden');
            {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
        } else {
            document.body.classList.remove('overflow-y-hidden');
            opener?.focus();
            opener = null;
        }
    })"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50 flex min-h-full items-center justify-center"
    style="display: {{ $show ? 'block' : 'none' }};"
>
    <div
        x-show="show"
        class="fixed inset-0 transform transition-all"
        x-on:click="show = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-scrim/50"></div>
    </div>

    {{-- `relative z-10` is load-bearing, not decoration. The scrim above is a
         `fixed` element, so it paints in the positioned layer; this panel was
         static and painted below it, meaning every click inside the dialog hit
         the scrim and closed it (the delete-account form was mouse-unusable).
         Tailwind 3's `transform` utility used to give the panel a stacking
         context and hide this, but in Tailwind 4 it is a no-op.

         `role="dialog"` (with `aria-modal`) is what tells assistive tech this is
         a dialog and the rest of the page is inert — x-trap only stops the Tab
         key, it says nothing to the accessibility tree. The panel is labelled by
         the *consumer's* title, so every `<x-modal>` usage must give its heading
         `id="{{ $name }}-title"`; a dangling `aria-labelledby` is itself an axe
         violation, so do not drop that id. --}}
    <div
        x-show="show"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $name }}-title"
        class="relative z-10 my-auto whitespace-normal text-left bg-surface-container rounded-2xl overflow-hidden transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto border border-outline-variant"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        {{ $slot }}
    </div>
</div>
