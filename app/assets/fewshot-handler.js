/**
 * Few-Shot Collection Handler
 * Manages adding/removing few-shot examples in forms
 */

class FewShotHandler {
    constructor() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Use event delegation for dynamically loaded modals
        // Use capture phase to ensure events are caught before Bootstrap tooltip
        document.addEventListener('click', (e) => {
            // Handle add button clicks
            const addButton = e.target.closest('.fewshot-add');
            if (addButton) {
                e.preventDefault();
                e.stopPropagation();
                this.addFewShotItem(addButton);
                return;
            }

            // Handle remove button clicks
            const removeButton = e.target.closest('.fewshot-remove-btn-permanent');
            if (removeButton) {
                e.preventDefault();
                e.stopPropagation();
                this.removeFewShotItem(removeButton);
                return;
            }
        }, true);
    }

    addFewShotItem(button) {
        const container = button.closest('.fewshot-collection-container');
        if (!container) {
            return;
        }

        const itemsContainer = container.querySelector('.fewshot-items');
        const prototype = container.getAttribute('data-prototype');
        let index = parseInt(container.getAttribute('data-index')) || 0;

        // Create new item with position relative for absolute button positioning
        const newItem = document.createElement('div');
        newItem.classList.add('fewshot-item', 'mb-2');
        newItem.style.position = 'relative';

        // Replace __name__ placeholder with actual index
        const itemHtml = prototype.replace(/__name__/g, index);
        newItem.innerHTML = itemHtml;

        // Update textarea class to include form-input-modern
        const textarea = newItem.querySelector('textarea');
        if (textarea) {
            textarea.classList.add('form-input-modern', 'fewshot-entry');
        }

        // Create icon-only remove button with same style as fullscreen button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'fewshot-remove-btn-permanent';
        removeBtn.setAttribute('title', 'Remove');
        removeBtn.setAttribute('data-bs-toggle', 'tooltip');
        removeBtn.innerHTML = '<i class="bi bi-trash"></i>';

        newItem.appendChild(removeBtn);

        // Append to container
        itemsContainer.appendChild(newItem);

        // Update index
        container.setAttribute('data-index', index + 1);

        // Focus on the new textarea
        if (textarea) {
            textarea.focus();
        }
    }

    removeFewShotItem(button) {
        const item = button.closest('.fewshot-item');
        if (item) {
            item.remove();
        }
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new FewShotHandler());
} else {
    new FewShotHandler();
}
