@php
    $seoResource = $page ?? $entry ?? null;

    if (! $seoResource instanceof \MiPress\Core\Models\Entry && ! $seoResource instanceof \MiPress\Core\Models\Page) {
        $seoResource = null;
    }

    $mipressSeo = mipress_seo([
        'resource' => $seoResource,
        'collection' => $collection ?? null,
        'title' => trim((string) $__env->yieldContent('title')),
        'description' => trim((string) $__env->yieldContent('meta_description')),
        'isPreview' => (bool) ($isPreview ?? false),
    ]);

    $siteName = filled($mipressSeo['site_name'] ?? null)
        ? (string) $mipressSeo['site_name']
        : config('app.name');
@endphp

<!DOCTYPE html>
<html lang="{{ $mipressSeo['html_lang'] ?? str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('mipress::seo.head', ['seo' => $mipressSeo])
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f8fafc" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|space-grotesk:500,600,700" rel="stylesheet" />
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
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ theme_asset('css/theme.css') }}">
    <script src="{{ theme_asset('js/theme.js') }}" defer></script>
    @stack('styles')
</head>
<body class="mp-shell">
    @include('mipress::seo.body-start', ['seo' => $mipressSeo])

    <div class="mp-site-shell">
        <div class="mp-shell-noise" aria-hidden="true"></div>
        <div class="mp-shell-glow mp-shell-glow--one" aria-hidden="true"></div>
        <div class="mp-shell-glow mp-shell-glow--two" aria-hidden="true"></div>

        @include('partials.header')

        <main class="mp-main">
            @yield('content')
        </main>

        @include('partials.footer')
    </div>

    @stack('scripts')
</body>
</html>
