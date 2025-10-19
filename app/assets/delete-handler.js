/**
 * Global Delete Handler
 * Unified delete confirmation and AJAX deletion for all entities
 */

class DeleteHandler {
    constructor() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Delegate click events for all delete buttons - USE CAPTURE PHASE to intercept before other handlers
        document.addEventListener('click', (e) => {
            // Handle modal delete confirm buttons (check first to prevent double handling)
            const deleteConfirm = e.target.closest('.delete-confirm-btn');
            if (deleteConfirm) {
                e.preventDefault();
                e.stopPropagation();
                this.handleModalDelete(deleteConfirm);
                return;
            }

            // Handle modal delete trigger buttons
            const deleteTrigger = e.target.closest('.delete-trigger-btn');
            if (deleteTrigger) {
                e.preventDefault();
                const entityType = deleteTrigger.getAttribute('data-entity-type');
                this.showModalDeleteConfirmation(entityType);
                return;
            }

            // Handle modal delete cancel buttons
            const deleteCancel = e.target.closest('.delete-cancel-btn');
            if (deleteCancel) {
                e.preventDefault();
                const entityType = deleteCancel.getAttribute('data-entity-type');
                this.hideModalDeleteConfirmation(entityType);
                return;
            }

            // Handle regular delete buttons (for list/card items)
            const deleteButton = e.target.closest('[data-delete-url]');
            if (deleteButton && !deleteButton.classList.contains('delete-confirm-btn')) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                this.handleDelete(deleteButton);
                return;
            }
        }, true); // USE CAPTURE PHASE
    }

    async handleModalDelete(button) {
        const url = button.getAttribute('data-delete-url');
        const csrfToken = button.getAttribute('data-csrf-token');
        const entityType = button.getAttribute('data-entity-type');

        if (!url || !csrfToken) {
            console.error('Missing required attributes for delete');
            return;
        }

        // Disable button and show loading
        button.disabled = true;
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> Deleting...';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `_token=${encodeURIComponent(csrfToken)}`
            });

            if (response.ok) {
                this.showToast('Item deleted successfully', 'success');

                // Close the modal
                const modal = button.closest('.modal-fullscreen-overlay');
                if (modal) {
                    // Try crud-modal close first
                    const closeButton = modal.querySelector('[data-action*="crud-modal#close"]');
                    if (closeButton) {
                        closeButton.click();
                    } else {
                        // Fallback: for canvas modals, just remove the modal from DOM
                        modal.remove();
                    }
                }

                // Dispatch custom event for canvas to refresh
                setTimeout(() => {
                    if (entityType !== 'treeflow') {
                        // Dispatch event for canvas refresh
                        document.dispatchEvent(new CustomEvent('treeflow-entity-deleted', {
                            detail: { entityType }
                        }));
                    } else {
                        // For treeflow deletion, redirect to list
                        if (typeof Turbo !== 'undefined') {
                            Turbo.visit('/treeflow');
                        } else {
                            window.location.href = '/treeflow';
                        }
                    }
                }, 300);
            } else {
                const data = await response.json().catch(() => ({}));
                this.showToast(data.message || 'Failed to delete item', 'error');
                button.disabled = false;
                button.innerHTML = originalHtml;
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showToast('Network error occurred', 'error');
            button.disabled = false;
            button.innerHTML = originalHtml;
        }
    }

    showModalDeleteConfirmation(entityType) {
        // Find the footer elements based on entity type
        let defaultActions, deleteConfirmation;

        if (entityType === 'treeflow') {
            defaultActions = document.getElementById('modal-default-actions-treeflow');
            deleteConfirmation = document.getElementById('modal-delete-confirmation-treeflow');
        } else if (entityType === 'step') {
            defaultActions = document.getElementById('modal-default-actions');
            deleteConfirmation = document.getElementById('modal-delete-confirmation');
        } else if (entityType === 'question') {
            defaultActions = document.getElementById('modal-default-actions-question');
            deleteConfirmation = document.getElementById('modal-delete-confirmation-question');
        } else if (entityType === 'input') {
            defaultActions = document.getElementById('modal-default-actions-input');
            deleteConfirmation = document.getElementById('modal-delete-confirmation-input');
        } else if (entityType === 'output') {
            defaultActions = document.getElementById('modal-default-actions-output');
            deleteConfirmation = document.getElementById('modal-delete-confirmation-output');
        }

        if (defaultActions && deleteConfirmation) {
            defaultActions.style.display = 'none';
            deleteConfirmation.style.display = 'flex';
        }
    }

    hideModalDeleteConfirmation(entityType) {
        // Find the footer elements based on entity type
        let defaultActions, deleteConfirmation;

        if (entityType === 'treeflow') {
            defaultActions = document.getElementById('modal-default-actions-treeflow');
            deleteConfirmation = document.getElementById('modal-delete-confirmation-treeflow');
        } else if (entityType === 'step') {
            defaultActions = document.getElementById('modal-default-actions');
            deleteConfirmation = document.getElementById('modal-delete-confirmation');
        } else if (entityType === 'question') {
            defaultActions = document.getElementById('modal-default-actions-question');
            deleteConfirmation = document.getElementById('modal-delete-confirmation-question');
        } else if (entityType === 'input') {
            defaultActions = document.getElementById('modal-default-actions-input');
            deleteConfirmation = document.getElementById('modal-delete-confirmation-input');
        } else if (entityType === 'output') {
            defaultActions = document.getElementById('modal-default-actions-output');
            deleteConfirmation = document.getElementById('modal-delete-confirmation-output');
        }

        if (defaultActions && deleteConfirmation) {
            deleteConfirmation.style.display = 'none';
            defaultActions.style.display = 'flex';
        }
    }

    handleDelete(button) {
        const url = button.getAttribute('data-delete-url');
        const entityName = button.getAttribute('data-entity-name') || 'this item';
        const csrf = button.getAttribute('data-csrf-token');

        if (!url || !csrf) {
            console.error('Missing required attributes: data-delete-url or data-csrf-token');
            return;
        }

        this.showConfirmModal(entityName, () => {
            this.performDelete(url, csrf, button);
        });
    }

    showConfirmModal(entityName, onConfirm) {
        const modalHtml = `
            <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" id="deleteConfirmModal">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content luminai-modal-content">
                        <div class="modal-header border-bottom luminai-modal-header">
                            <div class="d-flex align-items-center">
                                <div class="p-3 rounded-3 me-3" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                                    <i class="bi bi-exclamation-triangle text-white fs-4"></i>
                                </div>
                                <h5 class="modal-title luminai-modal-title mb-0">Confirm Deletion</h5>
                            </div>
                        </div>
                        <div class="modal-body p-4">
                            <div class="alert alert-danger border-0 rounded-3 luminai-alert-danger">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
                                    <div>
                                        <strong>This action cannot be undone.</strong>
                                        <br><small>Are you sure you want to delete ${entityName}?</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top luminai-modal-footer">
                            <div class="d-flex gap-2 justify-content-end w-100">
                                <button type="button" class="btn btn-modal-secondary" data-dismiss-modal>
                                    <i class="bi bi-x-circle me-2"></i>Cancel
                                </button>
                                <button type="button" class="btn btn-modal-danger" data-confirm-delete>
                                    <i class="bi bi-trash me-2"></i>Yes, Delete It
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Create backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.style.zIndex = '1050';
        backdrop.id = 'deleteModalBackdrop';
        document.body.appendChild(backdrop);

        // Create modal
        const modalElement = document.createElement('div');
        modalElement.innerHTML = modalHtml;
        const modal = modalElement.firstElementChild;
        modal.style.zIndex = '1055';
        document.body.appendChild(modal);

        const hideModal = () => {
            modal.classList.add('fade-out');
            backdrop.classList.add('fade-out');
            setTimeout(() => {
                modal.remove();
                backdrop.remove();
            }, 150);
        };

        // Handle dismiss
        modal.querySelector('[data-dismiss-modal]')?.addEventListener('click', hideModal);

        // Handle confirm
        modal.querySelector('[data-confirm-delete]')?.addEventListener('click', (e) => {
            e.preventDefault();
            const confirmBtn = e.currentTarget;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Deleting...';

            hideModal();
            setTimeout(() => onConfirm(), 250);
        });

        // Handle ESC and backdrop click
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                hideModal();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
        backdrop.addEventListener('click', () => {
            hideModal();
            document.removeEventListener('keydown', handleEscape);
        });
    }

    async performDelete(url, csrfToken, button) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'text/vnd.turbo-stream.html',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `_token=${encodeURIComponent(csrfToken)}`
            });

            if (response.ok) {
                // Check if response is Turbo Stream
                const contentType = response.headers.get('Content-Type');
                if (contentType && contentType.includes('turbo-stream')) {
                    // Let Turbo handle the stream response
                    const html = await response.text();
                    if (typeof Turbo !== 'undefined') {
                        Turbo.renderStreamMessage(html);
                    }
                    this.showToast('Item deleted successfully', 'success');
                } else {
                    this.handleSuccess(button);
                }
            } else {
                const data = await response.json().catch(() => ({}));
                this.handleError(data.message || 'Failed to delete item');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.handleError('Network error occurred');
        }
    }

    handleSuccess(button) {
        // Show success message
        this.showToast('Item deleted successfully', 'success');

        // Remove the item from DOM
        const row = button.closest('tr');
        const card = button.closest('.bento-item');

        if (row) {
            row.style.transition = 'opacity 0.3s ease';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        } else if (card) {
            card.style.transition = 'opacity 0.3s ease';
            card.style.opacity = '0';
            setTimeout(() => card.remove(), 300);
        }

        // Trigger a custom event for other components to react
        document.dispatchEvent(new CustomEvent('entity-deleted', {
            detail: { button }
        }));

        // Reload the page after a short delay to refresh the list
        setTimeout(() => {
            if (typeof Turbo !== 'undefined') {
                Turbo.cache.clear();
                Turbo.visit(window.location, { action: 'replace' });
            } else {
                window.location.reload();
            }
        }, 800);
    }

    handleError(message) {
        this.showToast(message, 'error');
    }

    showToast(message, type = 'info') {
        const toastHtml = `
            <div class="toast-notification toast-${type}" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    <span>${message}</span>
                </div>
            </div>
        `;

        const toast = document.createElement('div');
        toast.innerHTML = toastHtml;
        const toastElement = toast.firstElementChild;

        document.body.appendChild(toastElement);

        // Show toast
        setTimeout(() => toastElement.classList.add('show'), 10);

        // Hide and remove toast
        setTimeout(() => {
            toastElement.classList.remove('show');
            setTimeout(() => toastElement.remove(), 300);
        }, 3000);
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new DeleteHandler());
} else {
    new DeleteHandler();
}

// Add CSS for toasts
const style = document.createElement('style');
style.textContent = `
    .toast-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        color: white;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        opacity: 0;
        transform: translateX(400px);
        transition: all 0.3s ease;
    }

    .toast-notification.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast-success {
        background: linear-gradient(135deg, #22c55e, #16a34a);
    }

    .toast-error {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .fade-out {
        opacity: 0;
        transition: opacity 0.15s ease-out;
    }
`;
document.head.appendChild(style);
