/**
 * ALERTO EOC — Persistent Dual Theme Manager
 * Defaults to Dark Command Center mode; supports smooth switching to Executive Light Mode.
 */
class ThemeManagerClass {
    constructor() {
        this.storageKey = 'alerto_admin_theme';
        this.theme = this.getStoredTheme();
    }

    getStoredTheme() {
        try {
            const saved = localStorage.getItem(this.storageKey);
            return saved === 'light' ? 'light' : 'dark';
        } catch {
            return 'dark';
        }
    }

    isDark() {
        return this.theme === 'dark';
    }

    applyTheme(theme) {
        this.theme = theme === 'light' ? 'light' : 'dark';
        try {
            localStorage.setItem(this.storageKey, this.theme);
        } catch {
            /* ignore storage errors */
        }

        const shell = document.getElementById('eoc-admin-shell');
        if (shell) {
            shell.classList.remove('theme-dark', 'theme-light');
            shell.classList.add(this.theme === 'dark' ? 'theme-dark' : 'theme-light');
        }

        window.dispatchEvent(new CustomEvent('eoc:theme-changed', {
            detail: { theme: this.theme, isDark: this.isDark() },
        }));

        return this.theme;
    }

    toggle() {
        return this.applyTheme(this.isDark() ? 'light' : 'dark');
    }

    getChartTokens() {
        if (this.isDark()) {
            return {
                textColor: '#93A4B8',
                titleColor: '#EDF4FC',
                gridColor: 'rgba(147, 164, 184, 0.12)',
                tooltipBg: '#0B1726',
                tooltipBorder: 'rgba(52, 185, 212, 0.35)',
            };
        }

        return {
            textColor: '#475569',
            titleColor: '#0F172A',
            gridColor: 'rgba(23, 57, 87, 0.08)',
            tooltipBg: '#173957',
            tooltipBorder: 'rgba(49, 135, 216, 0.35)',
        };
    }
}

export const ThemeManager = new ThemeManagerClass();
export default ThemeManager;

