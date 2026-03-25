import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['icon'];

    connect() {
        this.applyTheme(this.currentTheme);
    }

    get currentTheme() {
        return localStorage.getItem('theme') || 'light';
    }

    toggle() {
        const next = this.currentTheme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', next);
        this.applyTheme(next);
    }

    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        this.updateIcon(theme);
    }

    updateIcon(theme) {
        if (!this.hasIconTarget) return;
        this.iconTarget.className = theme === 'dark'
            ? 'bi bi-sun-fill'
            : 'bi bi-moon-fill';
    }
}
