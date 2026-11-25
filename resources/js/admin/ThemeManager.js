/**
 * ALERTO APP — Shared Dual Theme Manager (Light & Dark)
 * Persists user preference across all operational portals while respecting per-portal defaults.
 */
class ThemeManagerClass {
    constructor() {
        this.storageKey = 'alerto_portal_theme';
        this.legacyKey = 'alerto_admin_theme';
        this.theme = this.getStoredTheme();
    }

    getDefaultTheme() {
        if (typeof document !== 'undefined') {
            const shell = this.getShellElement();
            const attr = shell?.dataset?.defaultTheme;
            if (attr === 'light' || attr === 'dark') {
                return attr;
            }
        }
        return 'dark';
    }

    getShellElement() {
        if (typeof document === 'undefined') return null;
        return document.querySelector('.alerto-shell') || document.getElementById('eoc-admin-shell');
    }

    getStoredTheme() {
        try {
            const saved = localStorage.getItem(this.storageKey) || localStorage.getItem(this.legacyKey);
            if (saved === 'light' || saved === 'dark') {
                return saved;
            }
        } catch {
            /* ignore storage errors */
        }
        return this.getDefaultTheme();
    }

    isDark() {
        return this.theme === 'dark';
    }

    applyTheme(theme, persist = true) {
        this.theme = theme === 'light' ? 'light' : 'dark';
        if (persist) {
            try {
                localStorage.setItem(this.storageKey, this.theme);
                localStorage.setItem(this.legacyKey, this.theme);
            } catch {
                /* ignore storage errors */
            }
        }

        const shell = this.getShellElement();
        if (shell) {
            shell.classList.remove('theme-dark', 'theme-light');
            shell.classList.add(this.theme === 'dark' ? 'theme-dark' : 'theme-light');
        }

        if (typeof document !== 'undefined') {
            document.documentElement.classList.toggle('dark', this.theme === 'dark');
        }

        window.dispatchEvent(new CustomEvent('alerto:theme-changed', {
            detail: { theme: this.theme, isDark: this.isDark() },
        }));
        window.dispatchEvent(new CustomEvent('eoc:theme-changed', {
            detail: { theme: this.theme, isDark: this.isDark() },
        }));

        return this.theme;
    }

    toggle() {
        return this.applyTheme(this.isDark() ? 'light' : 'dark', true);
    }

    getChartTokens() {
        if (this.isDark()) {
            return {
                textColor: '#D4D0C9',
                titleColor: '#F5F3EF',
                gridColor: 'rgba(255, 255, 255, 0.08)',
                tooltipBg: '#17191C',
                tooltipBorder: 'rgba(232, 117, 36, 0.45)',
            };
        }

        return {
            textColor: '#454A50',
            titleColor: '#17191C',
            gridColor: 'rgba(23, 25, 28, 0.07)',
            tooltipBg: '#17191C',
            tooltipBorder: 'rgba(232, 117, 36, 0.45)',
        };
    }
}

export const ThemeManager = new ThemeManagerClass();
export default ThemeManager;
