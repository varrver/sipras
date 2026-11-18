<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Browser-tab branding: a multi-size .ico from the school mark plus
             the 180px PNG iOS homescreen wants. Legacy link rel shortcut is
             included because some older crawlers ignore the ico-only form. --}}
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- The shell names the page it is rendering. A page that forgets
             `@section('title', __('…'))` used to fall back to a tab that said only
             "SIPRAS" — a title naming the product, not the page (WCAG 2.4.2), and
             axe cannot see the omission (`document-title` passes on any non-empty
             string). Refusing to render is safe: the only way to arrive here is a
             page someone just added or renamed, and every existing page is pinned
             by tests/Feature/PageTitleTest.php. --}}
        @php
            if (empty(trim($__env->yieldContent('title')))) {
                $route = request()->route()?->getName() ?? 'unnamed route';

                throw new LogicException(
                    "The route [{$route}] rendered x-app-layout without a title. Add ".
                    '@section(\'title\', __(\'…\')) as the first line of the page — see DESIGN.md §2.6.'
                );
            }
        @endphp

        <title>@yield('title') — {{ config('app.name', 'SIPRAS') }}</title>

        <!-- Fonts: Apple system stack (defined in app.css @theme) — no external download -->
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @if (Auth::user()->isAdmin())
            <div class="flex min-h-screen flex-col lg:flex-row">
                @include('layouts.navigation-admin')

                <div class="flex min-w-0 flex-1 flex-col">
                    @include('layouts.partials.main-content')
                </div>
            </div>
        @elseif (Auth::user()->role === 'teknisi')
            <div class="min-h-screen">
                @include('layouts.navigation-teknisi')
                @include('layouts.partials.main-content')
            </div>
        @else
            <div class="min-h-screen">
                @include('layouts.navigation')
                @include('layouts.partials.main-content')
            </div>
        @endif
    </body>
</html>