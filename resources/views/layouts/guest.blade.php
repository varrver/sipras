<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Same tab-branding pair as the app shell. --}}
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Same `X — SIPRAS` shape as the app shell, so the tab and the window
             list name the page instead of just the product. There is no
             fallback: `GuestLayout` refuses a missing or empty `:title` before
             this view is ever rendered. --}}
        <title>{{ $title }} — {{ config('app.name', 'SIPRAS') }}</title>

        <!-- Fonts: Apple system stack (defined in app.css @theme) — no external download -->
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        {{-- The primary tokens are the app-wide Apple action blue (app.css
             @theme), so no scoped override is needed here anymore — this shell
             and the logged-in app share one brand. The hairline bar stays as
             the identity cue on auth pages. --}}
        <main class="relative min-h-screen flex flex-col sm:justify-center items-center px-4 pt-6 sm:pt-0 bg-surface">
            {{-- Slim brand bar: identity from the school site's red, at iOS
                 restraint — a hairline, not an Elementor banner. --}}
            <div aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-[#0066cc]"></div>

            {{-- min-h-11: the lockup is a link home, held to the same 44px touch
                 floor as the buttons inside the cell. --}}
            <a href="/" class="flex min-h-11 items-center gap-2.5">
                <x-application-logo class="h-10 w-10" />
                <span class="text-2xl font-bold tracking-tight text-on-surface">SIPRAS</span>
            </a>

            {{-- The same `.card` cell the landing's sign-in panel uses: one
                 surface (radius, hairline, fill), not a third hand-rolled
                 variant. Padding matches the landing panel's rhythm, so a visitor
                 signing in at `/` and a visitor arriving at an auth page meet the
                 same cell, not two lookalikes. --}}
            <div class="card w-full sm:max-w-md mt-6 p-6 sm:p-8">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
