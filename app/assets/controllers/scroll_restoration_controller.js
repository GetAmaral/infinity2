import { Controller } from "@hotwired/stimulus";

/**
 * Scroll Restoration Controller
 *
 * Restores scroll position after Turbo navigations triggered by modal saves.
 * Works in conjunction with crud_modal_controller which saves the position.
 *
 * Usage: Add to body element
 * <body data-controller="scroll-restoration">
 */
export default class extends Controller {
    connect() {
        // Scroll restoration controller connected

        // Listen for turbo:load to restore scroll position
        this.boundRestoreScroll = this.restoreScrollPosition.bind(this);
        document.addEventListener('turbo:load', this.boundRestoreScroll);

        // Also restore on initial connect (page load)
        this.restoreScrollPosition();
    }

    disconnect() {
        if (this.boundRestoreScroll) {
            document.removeEventListener('turbo:load', this.boundRestoreScroll);
        }
    }

    restoreScrollPosition() {
        // Check if we have a saved scroll position from modal save
        const scrollY = sessionStorage.getItem('modalSaveScrollY');
        const scrollX = sessionStorage.getItem('modalSaveScrollX');

        if (scrollY !== null && scrollX !== null) {
            // Use requestAnimationFrame to ensure DOM is ready
            requestAnimationFrame(() => {
                window.scrollTo(parseInt(scrollX), parseInt(scrollY));
                console.log(`📍 Scroll position restored: x=${scrollX}, y=${scrollY}`);

                // Clear the saved position
                sessionStorage.removeItem('modalSaveScrollY');
                sessionStorage.removeItem('modalSaveScrollX');
            });
        }
    }
}
