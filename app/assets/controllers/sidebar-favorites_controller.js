// assets/controllers/sidebar-favorites_controller.js
import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    static targets = ['list', 'item'];
    static values = {
        favoritesUrl: String
    };

    connect() {
        this.initSortable();
    }

    initSortable() {
        if (!this.hasListTarget) return;

        this.sortable = new Sortable(this.listTarget, {
            animation: 150,
            handle: '.sidebar-item',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: () => {
                this.saveFavoritesOrder();
            }
        });
    }

    async toggle(event) {
        event.preventDefault();
        event.stopPropagation();

        const button = event.currentTarget;
        const menuKey = button.dataset.menuKey;
        const isFavorite = button.classList.contains('is-favorite');

        try {
            if (isFavorite) {
                const success = await this.removeFavorite(menuKey);
                if (!success) return;
                button.classList.remove('is-favorite');
                button.querySelector('i').classList.replace('bi-star-fill', 'bi-star');
            } else {
                const success = await this.addFavorite(menuKey);
                if (!success) return;
                button.classList.add('is-favorite');
                button.querySelector('i').classList.replace('bi-star', 'bi-star-fill');
            }

            // Reload page to update favorites section
            window.location.reload();
        } catch (error) {
            // Silently fail
        }
    }

    async addFavorite(menuKey) {
        const response = await fetch(this.favoritesUrlValue, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ menuKey })
        });

        return response.ok;
    }

    async removeFavorite(menuKey) {
        const response = await fetch(`${this.favoritesUrlValue}/${menuKey}`, {
            method: 'DELETE'
        });

        return response.ok;
    }

    async saveFavoritesOrder() {
        const items = Array.from(this.listTarget.querySelectorAll('.sidebar-item')).map(
            item => item.dataset.menuKey
        );

        try {
            await fetch(`${this.favoritesUrlValue}/reorder`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items })
            });
        } catch (error) {
            // Silently fail
        }
    }
}
