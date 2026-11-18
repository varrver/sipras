@props([
    'id',
    'src',
    'alt',
    'title',
    'fallback',
    'loading' => false,
])

{{-- One frame for every detail photo: the whole picture (never a center crop),
     the neutral placeholder fill, one expand control, and a labelled callout
     instead of the browser's broken-image glyph. The loading cue is opt-in —
     the student view reserves the photo's box while it decodes; the staff
     views keep the plain framed preview. --}}
<div {{ $attributes->class('mt-6') }} x-data="{ expanded: false, failed: false, loading: {{ $loading ? 'true' : 'false' }} }">
    <h2 class="font-medium text-on-surface mb-2">{{ $title }}</h2>
    <div class="rounded-xl border border-outline-variant bg-outline-variant overflow-hidden">
        <img id="{{ $id }}" src="{{ $src }}" alt="{{ $alt }}"
            class="w-full object-contain"
            x-bind:class="expanded ? '' : 'max-h-52 sm:max-h-96 object-contain'"
            loading="lazy" decoding="async"
            onerror="this.style.display='none';this.nextElementSibling?.removeAttribute('hidden')"
            x-on:error="failed = true; loading = false"
            x-on:load="loading = false"
            @if ($loading)
                x-init="if ($el.complete) { if ($el.naturalWidth === 0) { failed = true; loading = false } else { loading = false } }"
            @endif />
        <p hidden class="bg-secondary-container p-4 text-center text-sm text-on-secondary-container">
            {{ $fallback }}
        </p>
        @if ($loading)
            {{-- Reserves the photo's box while it decodes; x-cloak keeps the
                 cue out of the no-JS render, where it could never clear. --}}
            <span aria-hidden="true" x-show="loading" x-cloak
                class="flex aspect-[4/3] max-h-52 w-full items-center justify-center text-xs font-medium text-on-surface-variant sm:max-h-96">Memuat foto…</span>
        @endif
    </div>
    <button type="button" class="btn-secondary mt-3" x-show="!failed"
        @click="expanded = !expanded"
        aria-expanded="false"
        x-bind:aria-expanded="expanded ? 'true' : 'false'"
        aria-controls="{{ $id }}"
        x-text="expanded ? 'Sembunyikan foto lengkap' : 'Tampilkan foto lengkap'">Tampilkan foto lengkap</button>
</div>
