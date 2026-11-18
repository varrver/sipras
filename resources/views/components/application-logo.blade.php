{{-- The school's brand mark. A real <img>, not an inline SVG: the JPEG is a
     git-tracked static asset under public/images, so it is cacheable by the
     browser and swappable without touching a single view. `object-contain`
     keeps whatever logo is dropped in later inside its box instead of
     stretching. Alt is empty because the wordmark beside it already says
     "SIPRAS" — a duplicated name would be announced twice. --}}
<img src="{{ asset('images/smk-logo.png') }}" alt="" {{ $attributes->merge(['class' => 'object-contain']) }}>
