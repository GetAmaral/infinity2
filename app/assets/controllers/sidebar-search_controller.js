// assets/controllers/sidebar-search_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'results'];

    connect() {
        this.setupKeyboardShortcut();
    }

    setupKeyboardShortcut() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K: Focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.inputTarget.focus();
            }

            // Escape: Clear search
            if (e.key === 'Escape' && document.activeElement === this.inputTarget) {
                this.inputTarget.value = '';
                this.hideResults();
            }
        });
    }

    async search(event) {
        const query = event.target.value.trim();

        if (query.length < 2) {
            this.hideResults();
            return;
        }

        try {
            const response = await fetch(`/api/sidebar/search?q=${encodeURIComponent(query)}`);

            if (!response.ok) {
                this.hideResults();
                return;
            }

            const data = await response.json();
            this.renderResults(data.results);
        } catch (error) {
            this.hideResults();
        }
    }

    renderResults(results) {
        if (results.length === 0) {
            this.resultsTarget.innerHTML = `
                <div class="search-result-item" style="cursor: default;">
                    <span style="color: var(--luminai-text-muted);">No results found</span>
                </div>
            `;
        } else {
            this.resultsTarget.innerHTML = results.map(item => `
                <a href="${item.url}" class="search-result-item" data-turbo="true">
                    <i class="${item.icon}"></i>
                    <span class="search-result-label">${this.escapeHtml(item.label)}</span>
                    <span class="search-result-section">${item.section || ''}</span>
                </a>
            `).join('');
        }

        this.showResults();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showResults() {
        this.resultsTarget.classList.add('visible');
    }

    hideResults() {
        setTimeout(() => {
            this.resultsTarget.classList.remove('visible');
        }, 200);
    }
}
