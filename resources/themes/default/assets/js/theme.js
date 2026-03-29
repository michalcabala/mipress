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

            button.classList.toggle('is-active', isActive);
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

    const menu = document.querySelector('[data-site-menu]');
    const menuToggle = document.querySelector('[data-menu-toggle]');

    if (menu && menuToggle) {
        menuToggle.addEventListener('click', () => {
            const isOpen = menu.classList.toggle('is-open');

            menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
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
