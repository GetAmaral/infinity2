import { Controller } from '@hotwired/stimulus';

/*
 * Module Reorder Controller
 *
 * Enables drag-and-drop reordering of course modules
 */
export default class extends Controller {
    static targets = ['module'];
    static values = {
        updateUrl: String,
        courseId: String
    };

    connect() {
        this.draggedModule = null;
        this.initializeDragAndDrop();
    }

    disconnect() {
        this.cleanup();
    }

    initializeDragAndDrop() {
        // Make all module wrappers draggable
        this.moduleTargets.forEach(moduleWrapper => {
            moduleWrapper.draggable = true;
            moduleWrapper.style.cursor = 'grab';

            moduleWrapper.addEventListener('dragstart', this.handleDragStart.bind(this));
            moduleWrapper.addEventListener('dragend', this.handleDragEnd.bind(this));
            moduleWrapper.addEventListener('dragover', this.handleDragOver.bind(this));
            moduleWrapper.addEventListener('drop', this.handleDrop.bind(this));
        });
    }

    handleDragStart(event) {
        this.draggedModule = event.currentTarget;
        this.draggedModule.style.opacity = '0.4';

        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/html', this.draggedModule.innerHTML);
    }

    handleDragEnd(event) {
        this.draggedModule.style.opacity = '1';

        // Remove all drop indicators
        this.moduleTargets.forEach(module => {
            module.classList.remove('drag-over-top', 'drag-over-bottom');
        });

        this.draggedModule = null;
    }

    handleDragOver(event) {
        if (event.preventDefault) {
            event.preventDefault();
        }

        event.dataTransfer.dropEffect = 'move';

        const target = event.currentTarget;
        if (target && target !== this.draggedModule) {
            // Remove previous indicators from all modules
            this.moduleTargets.forEach(m => m.classList.remove('drag-over-top', 'drag-over-bottom'));

            // Calculate position - using larger hit zones (entire half of the module)
            const rect = target.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;
            const mouseY = event.clientY;

            // If mouse is in top half, show indicator above
            if (mouseY < midpoint) {
                target.classList.add('drag-over-top');
            } else {
                // If mouse is in bottom half, show indicator below
                target.classList.add('drag-over-bottom');
            }
        }

        return false;
    }

    handleDrop(event) {
        if (event.stopPropagation) {
            event.stopPropagation();
        }

        const dropTarget = event.currentTarget;

        if (this.draggedModule === dropTarget) {
            return false;
        }

        // Determine drop position
        const rect = dropTarget.getBoundingClientRect();
        const midpoint = rect.top + rect.height / 2;
        const insertBefore = event.clientY < midpoint ? dropTarget : dropTarget.nextSibling;

        // Move the element
        if (insertBefore) {
            this.element.insertBefore(this.draggedModule, insertBefore);
        } else {
            this.element.appendChild(this.draggedModule);
        }

        // Update order and save
        this.updateOrder();

        return false;
    }

    async updateOrder() {
        const modules = [];

        // Collect new order from all modules
        this.moduleTargets.forEach((moduleWrapper, index) => {
            const moduleId = moduleWrapper.dataset.moduleId;
            const newOrder = index + 1;

            modules.push({
                id: moduleId,
                viewOrder: newOrder
            });

            // Update badge display
            const badge = moduleWrapper.querySelector('.module-order-badge span');
            if (badge) {
                badge.textContent = newOrder;
            }
        });

        // Show saving indicator
        this.showSavingIndicator();

        try {
            const response = await fetch(this.updateUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    courseId: this.courseIdValue,
                    modules: modules
                })
            });

            if (!response.ok) {
                throw new Error('Failed to update order');
            }

            const result = await response.json();

            if (result.success) {
                this.showSuccessIndicator();
            } else {
                throw new Error(result.message || 'Update failed');
            }
        } catch (error) {
            console.error('Error updating module order:', error);
            this.showErrorIndicator();
        }
    }

    showSavingIndicator() {
        this.removeIndicators();

        const indicator = document.createElement('div');
        indicator.className = 'module-reorder-indicator saving';
        indicator.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Saving order...';

        const container = this.element.closest('.bento-item.large');
        if (container) {
            container.insertBefore(indicator, container.firstChild);
        }
    }

    showSuccessIndicator() {
        this.removeIndicators();

        const indicator = document.createElement('div');
        indicator.className = 'module-reorder-indicator success';
        indicator.innerHTML = '<i class="bi bi-check-circle me-2"></i>Module order updated successfully';

        const container = this.element.closest('.bento-item.large');
        if (container) {
            container.insertBefore(indicator, container.firstChild);
        }

        setTimeout(() => {
            indicator.remove();
        }, 2000);
    }

    showErrorIndicator() {
        this.removeIndicators();

        const indicator = document.createElement('div');
        indicator.className = 'module-reorder-indicator error';
        indicator.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Failed to update module order';

        const container = this.element.closest('.bento-item.large');
        if (container) {
            container.insertBefore(indicator, container.firstChild);
        }

        setTimeout(() => {
            indicator.remove();
        }, 3000);
    }

    removeIndicators() {
        const indicators = document.querySelectorAll('.module-reorder-indicator');
        indicators.forEach(ind => ind.remove());
    }

    cleanup() {
        this.moduleTargets.forEach(moduleWrapper => {
            moduleWrapper.draggable = false;
            moduleWrapper.style.cursor = 'default';
            moduleWrapper.removeEventListener('dragstart', this.handleDragStart);
            moduleWrapper.removeEventListener('dragend', this.handleDragEnd);
            moduleWrapper.removeEventListener('dragover', this.handleDragOver);
            moduleWrapper.removeEventListener('drop', this.handleDrop);
        });
    }
}
