@props([
    'complaint',
    'backHref',
    'backLabel',
])

{{-- The page's name is the record's own name: the tab and the <h1> carry the
     ticket's title — the nav names the section, and the card itself names the
     ticket. Only the back destination changes per role, so it is passed in. --}}
<div class="page-heading-row">
    <div class="w-full max-w-3xl mx-auto flex items-center gap-3">
        <x-back-button :href="$backHref" :label="$backLabel" />
        <h1 class="text-2xl font-bold tracking-tight text-on-surface">
            {{ $complaint->title }}
        </h1>
    </div>
</div>
