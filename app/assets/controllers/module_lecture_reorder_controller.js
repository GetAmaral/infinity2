import { Controller } from '@hotwired/stimulus';

/*
 * Module Lecture Reorder Controller
 *
 * Enables drag-and-drop reordering of lectures:
 * - Within the same module
 * - Across different modules (cross-module dragging)
 */
export default class extends Controller {
    static targets = ['module'];
    static values = {
        updateUrl: String,
        courseId: String,
        savingText: String,
        successText: String,
        errorText: String
    };

    connect() {
        this.draggedLecture = null;
        this.sourceModule = null;
        this.initializeDragAndDrop();
    }

    disconnect() {
        this.cleanup();
    }

    initializeDragAndDrop() {
        // Make all lecture cards draggable
        this.moduleTargets.forEach(moduleContainer => {
            const lectureCards = moduleContainer.querySelectorAll('.lecture-card-wrapper');

            lectureCards.forEach(lecture => {
                lecture.draggable = true;
                lecture.style.cursor = 'grab';

                lecture.addEventListener('dragstart', this.handleDragStart.bind(this));
                lecture.addEventListener('dragend', this.handleDragEnd.bind(this));
            });

            // Make module containers drop zones
            moduleContainer.addEventListener('dragover', this.handleDragOver.bind(this));
            moduleContainer.addEventListener('drop', this.handleDrop.bind(this));
            moduleContainer.addEventListener('dragenter', this.handleDragEnter.bind(this));
            moduleContainer.addEventListener('dragleave', this.handleDragLeave.bind(this));
        });
    }

    handleDragStart(event) {
        this.draggedLecture = event.currentTarget;
        this.sourceModule = this.draggedLecture.closest('[data-module-lecture-reorder-target="module"]');

        this.draggedLecture.style.opacity = '0.4';
        this.draggedLecture.style.cursor = 'grabbing';

        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/html', this.draggedLecture.innerHTML);
    }

    handleDragEnd(event) {
        this.draggedLecture.style.opacity = '1';
        this.draggedLecture.style.cursor = 'grab';

        // Remove all drop indicators
        this.moduleTargets.forEach(module => {
            module.classList.remove('drag-over');

            const lectures = module.querySelectorAll('.lecture-card-wrapper');
            lectures.forEach(lecture => {
                lecture.classList.remove('drag-over-top', 'drag-over-bottom');
            });
        });

        this.draggedLecture = null;
        this.sourceModule = null;
    }

    handleDragOver(event) {
        if (event.preventDefault) {
            event.preventDefault();
        }

        event.dataTransfer.dropEffect = 'move';

        // Find the lecture card we're hovering over
        const target = event.target.closest('.lecture-card-wrapper');
        if (target && target !== this.draggedLecture) {
            // Remove previous indicators from all lectures
            const moduleContainer = target.closest('[data-module-lecture-reorder-target="module"]');
            if (moduleContainer) {
                const lectures = moduleContainer.querySelectorAll('.lecture-card-wrapper');
                lectures.forEach(l => l.classList.remove('drag-over-top', 'drag-over-bottom'));
            }

            // Calculate position - using larger hit zones (entire half of the card)
            const rect = target.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;
            const mouseY = event.clientY;

            // If mouse is in top half of target card, show indicator above
            if (mouseY < midpoint) {
                target.classList.add('drag-over-top');
            } else {
                // If mouse is in bottom half of target card, show indicator below
                target.classList.add('drag-over-bottom');
            }
        }

        return false;
    }

    handleDragEnter(event) {
        const moduleContainer = event.currentTarget;

        // Only add drag-over if entering empty module or module header area
        if (!event.target.closest('.lecture-card-wrapper')) {
            moduleContainer.classList.add('drag-over');
        }
    }

    handleDragLeave(event) {
        const moduleContainer = event.currentTarget;

        // Only remove if we're really leaving the module
        if (!moduleContainer.contains(event.relatedTarget)) {
            moduleContainer.classList.remove('drag-over');
        }
    }

    handleDrop(event) {
        if (event.stopPropagation) {
            event.stopPropagation();
        }

        const dropModule = event.currentTarget;
        const dropTarget = event.target.closest('.lecture-card-wrapper');

        if (this.draggedLecture === dropTarget) {
            return false;
        }

        // Determine drop position
        let insertBefore = null;

        if (dropTarget) {
            const rect = dropTarget.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;
            insertBefore = event.clientY < midpoint ? dropTarget : dropTarget.nextSibling;
        } else {
            // Dropped in empty area - append to end
            insertBefore = null;
        }

        // Move the element
        if (insertBefore) {
            dropModule.insertBefore(this.draggedLecture, insertBefore);
        } else {
            dropModule.appendChild(this.draggedLecture);
        }

        // Update module lecture counts and empty states immediately
        this.updateModuleCounts();

        // Update order and save
        this.updateOrder();

        return false;
    }

    async updateOrder() {
        const lectures = [];

        // Collect new order from all modules
        this.moduleTargets.forEach(moduleContainer => {
            const moduleId = moduleContainer.dataset.moduleId;
            const lectureCards = moduleContainer.querySelectorAll('.lecture-card-wrapper');

            lectureCards.forEach((lecture, index) => {
                const lectureId = lecture.dataset.lectureId;
                const newOrder = index + 1;

                lectures.push({
                    id: lectureId,
                    moduleId: moduleId,
                    viewOrder: newOrder
                });

                // Update badge display
                const badge = lecture.querySelector('.lecture-order-badge span');
                if (badge) {
                    badge.textContent = newOrder;
                }
            });
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

                // Reload page after successful cross-module move to refresh module stats
                if (this.draggedLecture && this.sourceModule) {
                    const targetModule = this.draggedLecture.closest('[data-module-lecture-reorder-target="module"]');
                    if (targetModule !== this.sourceModule) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                }
            } else {
                throw new Error(result.message || 'Update failed');
            }
        } catch (error) {
            console.error('Error updating lecture order:', error);
            this.showErrorIndicator();
        }
    }

    updateModuleCounts() {
        // Update lecture counts and empty states for all modules
        this.moduleTargets.forEach(moduleContainer => {
            const moduleId = moduleContainer.dataset.moduleId;
            const lectureCards = moduleContainer.querySelectorAll('.lecture-card-wrapper');
            const lectureCount = lectureCards.length;

            // Update count in module header (accordion button)
            const headerCount = document.querySelector(`.module-header-summary[data-module-id="${moduleId}"] .module-header-lecture-count`);
            if (headerCount) {
                // Get translation for "lectures" - use the first word from the existing text
                const lecturesText = headerCount.textContent.split(' ').slice(1).join(' ');
                headerCount.textContent = `${lectureCount} ${lecturesText}`;
            }

            // Update count in body header
            const bodyCount = document.querySelector(`.module-lecture-count[data-module-id="${moduleId}"]`);
            if (bodyCount) {
                bodyCount.textContent = lectureCount;
            }

            // Show/hide empty state
            const emptyState = document.querySelector(`.module-empty-state[data-module-id="${moduleId}"]`);
            if (emptyState) {
                if (lectureCount === 0) {
                    emptyState.style.display = 'block';
                } else {
                    emptyState.style.display = 'none';
                }
            } else if (lectureCount === 0) {
                // Create empty state if it doesn't exist
                const emptyStateHtml = `
                    <div class="module-empty-state luminai-card p-4 text-center" data-module-id="${moduleId}">
                        <i class="bi bi-collection text-muted" style="font-size: 2.5rem;"></i>
                        <p class="text-secondary mt-3 mb-0">${this.getEmptyStateText()}</p>
                    </div>
                `;
                moduleContainer.insertAdjacentHTML('beforeend', emptyStateHtml);
            }
        });
    }

    getEmptyStateText() {
        // Try to get the translation from an existing empty state, or use default
        const existingEmptyState = document.querySelector('.module-empty-state p');
        if (existingEmptyState) {
            return existingEmptyState.textContent;
        }
        return 'No lectures in this module'; // Fallback
    }

    showSavingIndicator() {
        this.removeIndicators();

        const indicator = document.createElement('div');
        indicator.className = 'lecture-reorder-indicator saving';
        indicator.innerHTML = `<i class="bi bi-arrow-repeat spin me-2"></i>${this.savingTextValue}...`;

        const container = this.element.querySelector('.bento-item.large');
        if (container) {
            container.insertBefore(indicator, container.firstChild);
        }
    }

    showSuccessIndicator() {
        this.removeIndicators();

        const indicator = document.createElement('div');
        indicator.className = 'lecture-reorder-indicator success';
        indicator.innerHTML = `<i class="bi bi-check-circle me-2"></i>${this.successTextValue}`;

        const container = this.element.querySelector('.bento-item.large');
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
        indicator.className = 'lecture-reorder-indicator error';
        indicator.innerHTML = `<i class="bi bi-exclamation-circle me-2"></i>${this.errorTextValue}`;

        const container = this.element.querySelector('.bento-item.large');
        if (container) {
            container.insertBefore(indicator, container.firstChild);
        }

        setTimeout(() => {
            indicator.remove();
        }, 3000);
    }

    removeIndicators() {
        const indicators = document.querySelectorAll('.lecture-reorder-indicator');
        indicators.forEach(ind => ind.remove());
    }

    cleanup() {
        this.moduleTargets.forEach(moduleContainer => {
            const lectureCards = moduleContainer.querySelectorAll('.lecture-card-wrapper');

            lectureCards.forEach(lecture => {
                lecture.draggable = false;
                lecture.style.cursor = 'default';
            });

            moduleContainer.removeEventListener('dragover', this.handleDragOver);
            moduleContainer.removeEventListener('drop', this.handleDrop);
            moduleContainer.removeEventListener('dragenter', this.handleDragEnter);
            moduleContainer.removeEventListener('dragleave', this.handleDragLeave);
        });
    }
}
