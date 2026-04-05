<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', '')">
    <meta name="color-scheme" content="light dark">
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
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-28 -top-44 h-104 w-104 rounded-full bg-blue-300/35 blur-3xl dark:bg-blue-500/25"></div>
        <div class="absolute -right-24 top-36 h-80 w-80 rounded-full bg-cyan-200/45 blur-3xl dark:bg-cyan-500/20"></div>
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(148,163,184,0.12)_1px,transparent_1px),linear-gradient(to_bottom,rgba(148,163,184,0.12)_1px,transparent_1px)] bg-size-[28px_28px] mask-[radial-gradient(circle_at_center,black_30%,transparent_78%)] dark:bg-[linear-gradient(to_right,rgba(100,116,139,0.12)_1px,transparent_1px),linear-gradient(to_bottom,rgba(100,116,139,0.12)_1px,transparent_1px)]"></div>
    </div>

    @include('partials.header')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    <script>
        (() => {
            const storageKey = 'mipress-theme';
            const root = document.documentElement;
            const media = window.matchMedia('(prefers-color-scheme: dark)');

            const resolveTheme = (preference) => {
                if (preference === 'dark' || preference === 'light') {
                    return preference;
                }

                return media.matches ? 'dark' : 'light';
            };

            const applyTheme = (preference) => {
                const resolved = resolveTheme(preference);

                root.dataset.themePreference = preference;
                root.dataset.theme = resolved;
                root.classList.toggle('dark', resolved === 'dark');

                document.querySelectorAll('[data-theme-option]').forEach((button) => {
                    const isActive = button.dataset.themeOption === preference;

                    button.classList.toggle('bg-blue-600', isActive);
                    button.classList.toggle('text-white', isActive);
                    button.classList.toggle('shadow-sm', isActive);
                    button.classList.toggle('text-slate-600', !isActive);
                    button.classList.toggle('dark:text-slate-300', !isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
            };

            const storedPreference = localStorage.getItem(storageKey) || root.dataset.themePreference || 'system';
            applyTheme(storedPreference);

            document.querySelectorAll('[data-theme-option]').forEach((button) => {
                button.addEventListener('click', () => {
                    const preference = button.dataset.themeOption || 'system';
                    localStorage.setItem(storageKey, preference);
                    applyTheme(preference);
                });
            });

            const menu = document.querySelector('[data-site-menu-mobile]');
            const menuToggle = document.querySelector('[data-menu-toggle]');

            if (menu && menuToggle) {
                menuToggle.addEventListener('click', () => {
                    const isOpen = menu.classList.toggle('hidden');
                    menuToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                });
            }

            const handleSystemChange = () => {
                if ((localStorage.getItem(storageKey) || 'system') === 'system') {
                    applyTheme('system');
                }
            };

            if (typeof media.addEventListener === 'function') {
                media.addEventListener('change', handleSystemChange);
            } else if (typeof media.addListener === 'function') {
                media.addListener(handleSystemChange);
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>
