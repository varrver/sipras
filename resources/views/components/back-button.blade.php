@props(['href', 'label' => __('Kembali')])

{{-- Icon-only circular return: the same 44 pt rounded-full neutral glyph
     button the drawer toggle and the paginator's chevrons wear, so going back
     reads as chrome rather than as another line of text — or another filled
     action — beside the heading. The destination still names the link for
     assistive tech and search-in-page, but it is never printed on screen. --}}
<a href="{{ $href }}"
   aria-label="{{ $label }}"
   {{ $attributes->merge(['class' => 'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface active:bg-surface-container-highest focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary']) }}>
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
    </svg>
</a>
