<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @auth
            <meta name="auth-user-id" content="{{ auth()->id() }}">
        @endauth
        <title>{{ $title ?? 'TA Cloud' }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|manrope:700,800|jetbrains-mono:700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="ta-body">
        <div class="app-shell {{ $sidebarMode ?? 'with-sidebar' }}">
            @if (($sidebarMode ?? 'with-sidebar') === 'with-sidebar')
                @include('partials.sidebar', [
                    'navRole' => $navRole ?? 'global',
                    'navItems' => $navItems ?? [],
                    'navFooterItems' => $navFooterItems ?? [],
                    'primaryCta' => $primaryCta ?? null,
                ])
            @endif

            <div class="main-shell">
                @include('partials.topbar', [
                    'heading' => $heading ?? null,
                    'title' => $title ?? null,
                    'crumbs' => $crumbs ?? null,
                ])

                <main class="page-content">
                    @yield('content')
                </main>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
