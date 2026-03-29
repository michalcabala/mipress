<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Mason Entry</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|sora:400,500,600,700" rel="stylesheet" />
        <script>
            (() => {
                const preference = localStorage.getItem('mipress-theme') || 'system';
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const active = preference === 'system' ? (systemDark ? 'dark' : 'light') : preference;

                document.documentElement.dataset.themePreference = preference;
                document.documentElement.dataset.theme = active;
                document.documentElement.classList.toggle('dark', active === 'dark');
            })();
        </script>
        <link rel="stylesheet" href="{{ theme_asset('css/theme.css') }}">
        @masonEntryStyles
    </head>
    <body class="mp-editor-shell">
        <main class="mp-editor-stage">
            @include('mason::iframe-entry-content', ['blocks' => $blocks])
        </main>
    </body>
</html>
