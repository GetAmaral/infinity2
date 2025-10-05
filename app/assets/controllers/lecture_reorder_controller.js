import { Controller } from '@hotwired/stimulus';

/*
 * Lecture Reorder Controller
 *
 * Enables drag-and-drop reordering of lectures with async batch updates
 */
export default class extends Controller {
    static targets = ['container', 'item'];
    static values = {
        updateUrl: String,
        courseId: String
    };

    connect() {
        this.draggedElement = null;
        this.placeholder = null;
        this.initializeDragAndDrop();
    }

    disconnect() {
        this.cleanup();
    }

    initializeDragAndDrop() {
        this.itemTargets.forEach((item, index) => {
            item.draggable = true;
            item.dataset.order = index + 1;

            item.addEventListener('dragstart', this.handleDragStart.bind(this));
            item.addEventListener('dragend', this.handleDragEnd.bind(this));
            item.addEventListener('dragover', this.handleDragOver.bind(this));
            item.addEventListener('drop', this.handleDrop.bind(this));
            item.addEventListener('dragenter', this.handleDragEnter.bind(this));
            item.addEventListener('dragleave', this.handleDragLeave.bind(this));
        });
    }

    handleDragStart(event) {
        this.draggedElement = event.currentTarget;
        this.draggedElement.style.opacity = '0.4';

        // Create placeholder
        this.placeholder = this.draggedElement.cloneNode(true);
        this.placeholder.style.opacity = '0.3';
        this.placeholder.style.border = '2px dashed var(--luminai-neon)';
        this.placeholder.style.background = 'rgba(0, 245, 255, 0.05)';
        this.placeholder.classList.add('dragging-placeholder');

        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/html', this.draggedElement.innerHTML);
    }

    handleDragEnd(event) {
        this.draggedElement.style.opacity = '1';

        // Remove placeholder
        if (this.placeholder && this.placeholder.parentNode) {
            this.placeholder.remove();
        }

        // Remove all drag-over indicators
        this.itemTargets.forEach(item => {
            item.classList.remove('drag-over', 'drag-over-top', 'drag-over-bottom');
        });

        this.draggedElement = null;
        this.placeholder = null;
    }

    handleDragOver(event) {
        if (event.preventDefault) {
            event.preventDefault();
        }

        event.dataTransfer.dropEffect = 'move';
        return false;
    }

    handleDragEnter(event) {
        const item = event.currentTarget;

        if (item === this.draggedElement) {
            return;
        }

        // Remove previous indicators
        this.itemTargets.forEach(i => {
            i.classList.remove('drag-over', 'drag-over-top', 'drag-over-bottom');
        });

        // Add indicator
        const rect = item.getBoundingClientRect();
        const midpoint = rect.top + rect.height / 2;

        if (event.clientY < midpoint) {
            item.classList.add('drag-over-top');
        } else {
            item.classList.add('drag-over-bottom');
        }
    }

    handleDragLeave(event) {
        const item = event.currentTarget;

        // Only remove if we're really leaving (not entering a child)
        if (!item.contains(event.relatedTarget)) {
            item.classList.remove('drag-over', 'drag-over-top', 'drag-over-bottom');
        }
    }

    handleDrop(event) {
        if (event.stopPropagation) {
            event.stopPropagation();
        }

        const dropTarget = event.currentTarget;

        if (this.draggedElement === dropTarget) {
            return false;
        }

        // Determine drop position
        const rect = dropTarget.getBoundingClientRect();
        const midpoint = rect.top + rect.height / 2;
        const insertBefore = event.clientY < midpoint;

        // Move the element
        const container = this.containerTarget;
        if (insertBefore) {
            container.insertBefore(this.draggedElement, dropTarget);
        } else {
            container.insertBefore(this.draggedElement, dropTarget.nextSibling);
        }

        // Update order and save
        this.updateOrder();

        return false;
    }

    async updateOrder() {
        const lectures = [];

        // Collect new order
        this.itemTargets.forEach((item, index) => {
            const lectureId = item.dataset.lectureId;
            const newOrder = index + 1;

            lectures.push({
                id: lectureId,
                viewOrder: newOrder
            });

            // Update badge display
            const badge = item.querySelector('.lecture-order-badge');
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
                    lectures: lectures
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
            console.error('Error updating lecture order:', error);
            this.showErrorIndicator();

            // Revert order on error (optional - you could keep the visual order)
            // For now, just show error
        }
    }

    showSavingIndicator() {
        this.removeIndicators();

        const indicator = document.createElement('div');
        indicator.className = 'lecture-reorder-indicator saving';
        indicator.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Saving order...';
        this.containerTarget.parentNode.insertBefore(indicator, this.containerTarget);
    }

    showSuccessIndicator() {
        this.removeIndicators();

        const indicator = document.createElement('div');
        indicator.className = 'lecture-reorder-indicator success';
        indicator.innerHTML = '<i class="bi bi-check-circle me-2"></i>Order updated successfully';
        this.containerTarget.parentNode.insertBefore(indicator, this.containerTarget);

        setTimeout(() => {
            indicator.remove();
        }, 2000);
    }

    showErrorIndicator() {
        this.removeIndicators();

        const indicator = document.createElement('div');
        indicator.className = 'lecture-reorder-indicator error';
        indicator.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Failed to update order';
        this.containerTarget.parentNode.insertBefore(indicator, this.containerTarget);

        setTimeout(() => {
            indicator.remove();
        }, 3000);
    }

    removeIndicators() {
        const indicators = document.querySelectorAll('.lecture-reorder-indicator');
        indicators.forEach(ind => ind.remove());
    }

    cleanup() {
        this.itemTargets.forEach(item => {
            item.draggable = false;
            item.removeEventListener('dragstart', this.handleDragStart);
            item.removeEventListener('dragend', this.handleDragEnd);
            item.removeEventListener('dragover', this.handleDragOver);
            item.removeEventListener('drop', this.handleDrop);
            item.removeEventListener('dragenter', this.handleDragEnter);
            item.removeEventListener('dragleave', this.handleDragLeave);
        });
    }
}
