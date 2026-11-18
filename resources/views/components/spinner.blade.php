{{-- The app's one in-flight spinner. Inline SVG in the platform's own line
     style (matching the error-card icon set: 24px grid, rounded caps,
     quarter-arc + track), colored by `currentColor` so it sits inside any
     button fill — including the primary blue — without a token flip.
     Hidden from assistive tech: the button's own label swap carries the
     state, and that swap is the aria-live region. --}}
@props(['class' => 'h-4 w-4'])
<svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none']) }} aria-hidden="true">
    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
</svg>
