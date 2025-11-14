import { Controller } from '@hotwired/stimulus';

/**
 * Entity Created Controller
 *
 * This controller is instantiated by Turbo Stream responses when an entity is created
 * in a nested modal. It dispatches an event and cleans up the modal.
 */
export default class extends Controller {
    static values = {
        type: String,
        id: String,
        display: String
    }

    connect() {
        console.log('🎬 Entity created controller connected!', {
            type: this.typeValue,
            id: this.idValue,
            display: this.displayValue
        });

        // Dispatch the entity:created event
        const event = new CustomEvent('entity:created', {
            detail: {
                entityType: this.typeValue,
                id: this.idValue,
                displayText: this.displayValue
            },
            bubbles: true
        });

        console.log('📢 Dispatching entity:created event', event.detail);
        document.dispatchEvent(event);

        // Give the relation-select controller time to process the event
        setTimeout(() => {
            console.log('⏱️ Timeout fired, closing nested modal');

            // Close the nested modal
            const nestedContainer = document.getElementById('nested-modal-container');
            if (nestedContainer) {
                console.log('🗑️ Removing nested container');
                nestedContainer.remove();
            } else {
                console.log('⚠️ Nested container not found');
            }

            // Show the original modal again
            const originalModal = document.querySelector('.modal-fullscreen-overlay[style*="display: none"]');
            if (originalModal) {
                console.log('👁️ Showing original modal');
                originalModal.style.display = 'flex';
            } else {
                console.log('⚠️ Original modal not found');
            }

            // Remove this element
            console.log('🧹 Cleaning up entity-created element');
            this.element.remove();
        }, 100);
    }
}
