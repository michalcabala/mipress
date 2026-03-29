<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', '')">
    <meta name="color-scheme" content="light dark">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|sora:400,500,600,700" rel="stylesheet" />
    <script>
        (() => {
            const storageKey = 'mipress-theme';
            const root = document.documentElement;
            const stored = localStorage.getItem(storageKey) || 'system';
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const active = stored === 'system' ? (systemDark ? 'dark' : 'light') : stored;

            root.dataset.themePreference = stored;
            root.dataset.theme = active;
            root.classList.toggle('dark', active === 'dark');
        })();
    </script>
    <link rel="stylesheet" href="{{ theme_asset('css/theme.css') }}">
    @stack('styles')
</head>
<body class="mp-shell">
    @include('partials.header')

    <main class="mp-main">
        @yield('content')
    </main>

    @include('partials.footer')

    <script src="{{ theme_asset('js/theme.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
