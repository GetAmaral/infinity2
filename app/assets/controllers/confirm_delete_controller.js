import { Controller } from "@hotwired/stimulus";

// Confirmation controller for delete operations
export default class extends Controller {
    static values = {
        message: String,
        hasUsers: Boolean
    };

    connect() {
        // Store bound handler so we can remove it properly
        this.boundConfirmDelete = this.confirmDelete.bind(this);
        this.element.addEventListener('submit', this.boundConfirmDelete);
    }

    disconnect() {
        if (this.boundConfirmDelete) {
            this.element.removeEventListener('submit', this.boundConfirmDelete);
        }
    }

    confirmDelete(event) {
        // If this submission has been confirmed, allow it to proceed
        if (this.allowSubmit) {
            this.allowSubmit = false; // Reset flag
            console.log('Delete confirmed, allowing form submission');
            return; // Let the form submit naturally
        }

        event.preventDefault();
        event.stopPropagation();

        // Show warning if organization has users
        if (this.hasUsersValue) {
            this.showCannotDeleteWarning();
            return;
        }

        // Show confirmation dialog
        const message = this.messageValue || 'Are you sure you want to delete this item?';

        this.showConfirmationModal(message, () => {
            // User confirmed - we need to allow this specific submission
            this.allowSubmit = true;

            // Use requestSubmit to trigger the form submission properly
            // This will re-trigger the submit event, but allowSubmit flag will let it through
            try {
                if (typeof this.element.requestSubmit === 'function') {
                    this.element.requestSubmit();
                } else {
                    this.element.submit();
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                // Fallback: try direct submission
                this.element.submit();
            }
        });
    }

    showCannotDeleteWarning() {
        const modalHtml = `
            <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content infinity-modal-content">
                        <div class="modal-header border-bottom infinity-modal-header">
                            <div class="d-flex align-items-center">
                                <div class="p-3 rounded-3 me-3" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                    <i class="bi bi-exclamation-triangle text-white fs-4"></i>
                                </div>
                                <h5 class="modal-title infinity-modal-title mb-0">Cannot Delete Organization</h5>
                            </div>
                        </div>
                        <div class="modal-body p-4">
                            <div class="alert alert-warning border-0 rounded-3 infinity-alert-warning">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-info-circle me-2 mt-1"></i>
                                    <div>
                                        <strong>This organization has users assigned to it.</strong>
                                        <br><small>Please remove or reassign all users before deleting this organization.</small>
                                    </div>
                                </div>
                            </div>
                            <p class="text-secondary mb-0">
                                You can view and manage users by clicking the "Manage Users" option in the organization menu.
                            </p>
                        </div>
                        <div class="modal-footer border-top infinity-modal-footer">
                            <button type="button" class="btn btn-modal-primary" data-dismiss-modal>
                                <i class="bi bi-check-circle me-2"></i>I Understand
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Pass null as onConfirm since this is just a warning modal
        this.showModal(modalHtml, null);
    }

    showConfirmationModal(message, onConfirm) {
        const modalHtml = `
            <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content infinity-modal-content">
                        <div class="modal-header border-bottom infinity-modal-header">
                            <div class="d-flex align-items-center">
                                <div class="p-3 rounded-3 me-3" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                                    <i class="bi bi-exclamation-triangle text-white fs-4"></i>
                                </div>
                                <h5 class="modal-title infinity-modal-title mb-0">Confirm Deletion</h5>
                            </div>
                        </div>
                        <div class="modal-body p-4">
                            <div class="alert alert-danger border-0 rounded-3 infinity-alert-danger">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
                                    <div>
                                        <strong>This action cannot be undone.</strong>
                                        <br><small>${message}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top infinity-modal-footer">
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

        this.showModal(modalHtml, onConfirm);
    }

    showModal(html, onConfirm = null) {
        // Create backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.style.zIndex = '1050';
        document.body.appendChild(backdrop);

        // Create modal
        const modalElement = document.createElement('div');
        modalElement.innerHTML = html;
        const modal = modalElement.firstElementChild;
        modal.style.zIndex = '1055';

        document.body.appendChild(modal);

        // Store the escape handler so we can remove it later
        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                this.hideModal(modal, backdrop);
                document.removeEventListener('keydown', handleEscape);
            }
        };

        // Handle close button
        const dismissButton = modal.querySelector('[data-dismiss-modal]');
        if (dismissButton) {
            dismissButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Dismiss button clicked');
                document.removeEventListener('keydown', handleEscape);
                this.hideModal(modal, backdrop);
            });
        }

        // Handle confirm button (only if onConfirm callback is provided)
        if (onConfirm) {
            const confirmButton = modal.querySelector('[data-confirm-delete]');
            if (confirmButton) {
                console.log('Confirm button found, attaching click handler');
                console.log('Button element:', confirmButton);
                console.log('Button is visible:', confirmButton.offsetParent !== null);
                console.log('Button disabled:', confirmButton.disabled);

                confirmButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('✅ Confirm button clicked!');
                    document.removeEventListener('keydown', handleEscape);

                    // Disable button to prevent double-clicks
                    confirmButton.disabled = true;
                    confirmButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Deleting...';

                    this.hideModal(modal, backdrop);
                    setTimeout(() => {
                        console.log('Executing onConfirm callback');
                        onConfirm();
                    }, 250); // Small delay for better UX
                });

                // Add additional debug listener
                confirmButton.addEventListener('mousedown', (e) => {
                    console.log('Mousedown detected on confirm button');
                });
            } else {
                console.warn('Confirm button not found but onConfirm was provided');
            }
        }

        // Handle backdrop click - close modal
        backdrop.addEventListener('click', () => {
            console.log('Backdrop clicked, closing modal');
            document.removeEventListener('keydown', handleEscape);
            this.hideModal(modal, backdrop);
        });

        // Handle modal wrapper click (for areas outside dialog)
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                console.log('Modal wrapper clicked, closing modal');
                document.removeEventListener('keydown', handleEscape);
                this.hideModal(modal, backdrop);
            }
        });

        // Handle ESC key
        document.addEventListener('keydown', handleEscape);

        // Focus the dismiss button by default
        setTimeout(() => {
            const focusButton = onConfirm ? modal.querySelector('[data-confirm-delete]') : dismissButton;
            focusButton?.focus();
        }, 100);
    }

    hideModal(modal, backdrop) {
        modal.classList.add('fade-out');
        backdrop.classList.add('fade-out');
        setTimeout(() => {
            modal.remove();
            backdrop.remove();
        }, 150);
    }
}

// Add CSS for fade-out animation and modal buttons
const style = document.createElement('style');
style.textContent = `
    .fade-out {
        opacity: 0;
        transition: opacity 0.15s ease-out;
    }

    /* Modal Backdrop - Theme Responsive */
    .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.7);
    }

    [data-theme="light"] .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.4);
    }

    /* Modal Content - Theme Responsive */
    .infinity-modal-content {
        background: var(--infinity-dark-bg);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    [data-theme="light"] .infinity-modal-content {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    }

    /* Modal Header - Theme Responsive */
    .infinity-modal-header {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-theme="light"] .infinity-modal-header {
        border-color: rgba(0, 0, 0, 0.1) !important;
    }

    /* Modal Footer - Theme Responsive */
    .infinity-modal-footer {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-theme="light"] .infinity-modal-footer {
        border-color: rgba(0, 0, 0, 0.1) !important;
    }

    /* Modal Title - Theme Responsive */
    .infinity-modal-title {
        color: #ffffff;
    }

    [data-theme="light"] .infinity-modal-title {
        color: #1a1a1a;
    }

    /* Alert Danger - Theme Responsive */
    .infinity-alert-danger {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3) !important;
        color: #ef4444;
    }

    [data-theme="light"] .infinity-alert-danger {
        background: rgba(220, 38, 38, 0.1);
        border: 1px solid rgba(220, 38, 38, 0.3) !important;
        color: #b91c1c;
    }

    /* Alert Warning - Theme Responsive */
    .infinity-alert-warning {
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.3) !important;
        color: #f59e0b;
    }

    [data-theme="light"] .infinity-alert-warning {
        background: rgba(217, 119, 6, 0.1);
        border: 1px solid rgba(217, 119, 6, 0.3) !important;
        color: #d97706;
    }

    /* Modal Button Styles - matching form modal */
    .btn-modal-secondary,
    .btn-modal-primary,
    .btn-modal-danger {
        padding: 0.75rem 1.75rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9375rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Secondary Button - Theme Responsive */
    .btn-modal-secondary {
        background: var(--infinity-btn-secondary-bg, rgba(255, 255, 255, 0.08));
        color: var(--infinity-text-primary, rgba(255, 255, 255, 0.9));
    }

    .btn-modal-secondary:hover {
        background: var(--infinity-btn-secondary-hover, rgba(255, 255, 255, 0.12));
        transform: translateY(-1px);
    }

    [data-theme="light"] .btn-modal-secondary {
        background: rgba(0, 0, 0, 0.08);
        color: #1a1a1a;
    }

    [data-theme="light"] .btn-modal-secondary:hover {
        background: rgba(0, 0, 0, 0.15);
    }

    /* Primary Button - Always gradient (theme-independent) */
    .btn-modal-primary {
        background: linear-gradient(135deg, #00f5ff 0%, #8a2be2 100%);
        color: white;
    }

    .btn-modal-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 245, 255, 0.4);
    }

    /* Danger Button - Always red gradient (theme-independent) */
    .btn-modal-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .btn-modal-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    }

    /* Disabled State - Theme Responsive */
    .btn-modal-primary:disabled,
    .btn-modal-secondary:disabled,
    .btn-modal-danger:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
`;
document.head.appendChild(style);