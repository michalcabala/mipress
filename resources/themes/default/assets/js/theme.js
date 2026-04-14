(() => {
    const storageKey = 'mipress-theme';
    const root = document.documentElement;
    const body = document.body;
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

    const setMenuState = (isOpen) => {
        const overlay = document.querySelector('[data-mobile-overlay]');

        if (!overlay) {
            return;
        }

        overlay.hidden = !isOpen;
        overlay.classList.toggle('is-open', isOpen);
        overlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        body.classList.toggle('mp-menu-lock', isOpen);

        document.querySelectorAll('[data-menu-open]').forEach((toggle) => {
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
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

    document.querySelectorAll('[data-menu-open]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            setMenuState(true);
        });
    });

    document.querySelectorAll('[data-menu-close]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            setMenuState(false);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMenuState(false);
        }
    });

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
