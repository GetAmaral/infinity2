import { Controller } from '@hotwired/stimulus';

/**
 * Controller for managing dynamic form collections (add/remove items)
 * Used for CollectionType fields in Symfony forms
 */
export default class extends Controller {
    static targets = ['container', 'item'];
    static values = {
        prototypeName: { type: String, default: '__name__' },
        index: { type: Number, default: 0 }
    };

    connect() {
        // Set initial index based on existing items
        this.indexValue = this.itemTargets.length;

        // Add index to existing items for proper deletion
        this.itemTargets.forEach((item, index) => {
            item.dataset.index = index;
        });
    }

    addItem(event) {
        event.preventDefault();

        // Get the prototype HTML from the data attribute
        const container = this.element.querySelector('[data-prototype]');
        if (!container) {
            console.error('No prototype found');
            return;
        }

        let prototype = container.dataset.prototype;

        // Replace the placeholder with the current index
        const newForm = prototype.replace(
            new RegExp(this.prototypeNameValue, 'g'),
            this.indexValue
        );

        // Create a wrapper div
        const wrapper = document.createElement('div');
        wrapper.classList.add('fewshot-item', 'mb-3', 'p-3', 'border', 'rounded');
        wrapper.dataset.collectionTarget = 'item';
        wrapper.dataset.index = this.indexValue;
        wrapper.innerHTML = newForm;

        // Add remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.classList.add('btn', 'btn-sm', 'btn-danger', 'mt-2');
        removeBtn.innerHTML = '<i class="bi bi-trash"></i> Remove Example';
        removeBtn.dataset.action = 'click->collection#removeItem';
        wrapper.appendChild(removeBtn);

        // Insert before the add button
        const addButton = this.element.querySelector('.add-item-btn');
        addButton.parentNode.insertBefore(wrapper, addButton);

        // Increment index
        this.indexValue++;
    }

    removeItem(event) {
        event.preventDefault();
        const item = event.target.closest('[data-collection-target="item"]');
        if (item) {
            item.remove();
        }
    }
}
