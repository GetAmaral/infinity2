/**
 * Few-Shot Collection Handler
 * Manages adding/removing few-shot examples in forms
 */

class FewShotHandler {
    constructor() {
        console.log('FewShotHandler initialized');
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Use event delegation for dynamically loaded modals
        document.addEventListener('click', (e) => {
            // Handle add button clicks
            if (e.target.classList.contains('fewshot-add') || e.target.closest('.fewshot-add')) {
                e.preventDefault();
                const button = e.target.classList.contains('fewshot-add') ? e.target : e.target.closest('.fewshot-add');
                this.addFewShotItem(button);
                return;
            }

            // Handle remove button clicks
            if (e.target.classList.contains('fewshot-remove') || e.target.closest('.fewshot-remove')) {
                e.preventDefault();
                const button = e.target.classList.contains('fewshot-remove') ? e.target : e.target.closest('.fewshot-remove');
                this.removeFewShotItem(button);
                return;
            }
        });
    }

    addFewShotItem(button) {
        const container = button.closest('.fewshot-collection-container');
        if (!container) {
            console.error('Few-shot container not found');
            return;
        }

        const itemsContainer = container.querySelector('.fewshot-items');
        const prototype = container.getAttribute('data-prototype');
        let index = parseInt(container.getAttribute('data-index')) || 0;

        console.log('Adding few-shot item with index:', index);

        // Create new item
        const newItem = document.createElement('div');
        newItem.classList.add('fewshot-item', 'mb-2');

        // Replace __name__ placeholder with actual index
        const itemHtml = prototype.replace(/__name__/g, index);

        newItem.innerHTML = `
            ${itemHtml}
            <button type="button" class="btn btn-sm btn-danger fewshot-remove mt-1">
                <i class="bi bi-trash"></i> Remove
            </button>
        `;

        // Append to container
        itemsContainer.appendChild(newItem);

        // Update index
        container.setAttribute('data-index', index + 1);

        // Focus on the new textarea
        const textarea = newItem.querySelector('textarea');
        if (textarea) {
            textarea.focus();
        }
    }

    removeFewShotItem(button) {
        const item = button.closest('.fewshot-item');
        if (item) {
            item.remove();
            console.log('Few-shot item removed');
        }
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new FewShotHandler());
} else {
    new FewShotHandler();
}
