import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["overlay", "form", "submitButton"];

    connect() {
        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Track if form has been modified
        this.formModified = false;
        this.initialFormData = null;

        // Capture initial form state after a short delay
        setTimeout(() => {
            this.captureInitialFormState();
            this.setupFormChangeTracking();
        }, 200);

        // Focus first available input field
        this.focusFirstField();

        // ESC key handler
        this.boundHandleEscape = this.handleEscape.bind(this);
        document.addEventListener('keydown', this.boundHandleEscape);
    }

    disconnect() {
        document.body.style.overflow = '';
        document.removeEventListener('keydown', this.boundHandleEscape);
    }

    /**
     * Focus on the first available input field
     */
    focusFirstField() {
        setTimeout(() => {
            if (!this.hasFormTarget) return;

            // Find all focusable elements
            const focusableSelectors = [
                'input:not([type=hidden]):not([disabled]):not([readonly])',
                'textarea:not([disabled]):not([readonly])',
                'select:not([disabled])'
            ];

            const focusableElements = this.formTarget.querySelectorAll(focusableSelectors.join(', '));

            // Find the first visible and enabled field
            for (let element of focusableElements) {
                if (this.isVisible(element)) {
                    element.focus();
                    // Also select text if it's a text input
                    if (element.tagName === 'INPUT' && (element.type === 'text' || element.type === 'email' || element.type === 'tel')) {
                        element.select();
                    }
                    break;
                }
            }
        }, 150);
    }

    /**
     * Check if element is visible
     */
    isVisible(element) {
        return element.offsetWidth > 0 &&
               element.offsetHeight > 0 &&
               getComputedStyle(element).visibility !== 'hidden' &&
               getComputedStyle(element).display !== 'none';
    }

    /**
     * Capture the initial state of the form
     */
    captureInitialFormState() {
        if (!this.hasFormTarget) return;

        const formData = new FormData(this.formTarget);
        this.initialFormData = {};

        for (let [key, value] of formData.entries()) {
            this.initialFormData[key] = value;
        }
    }

    /**
     * Setup form change tracking
     */
    setupFormChangeTracking() {
        if (!this.hasFormTarget) return;

        // Track changes on all inputs
        const inputs = this.formTarget.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', () => this.checkFormChanges());
            input.addEventListener('change', () => this.checkFormChanges());
        });
    }

    /**
     * Check if form has been modified
     */
    checkFormChanges() {
        if (!this.hasFormTarget || !this.initialFormData) return;

        const formData = new FormData(this.formTarget);
        let hasChanges = false;

        // Check if any field has changed
        for (let [key, value] of formData.entries()) {
            if (this.initialFormData[key] !== value) {
                hasChanges = true;
                break;
            }
        }

        // Also check if fields were removed
        for (let key in this.initialFormData) {
            if (!formData.has(key)) {
                hasChanges = true;
                break;
            }
        }

        this.formModified = hasChanges;
    }

    async submit(event) {
        event.preventDefault();

        // Mark as not modified since we're submitting
        this.formModified = false;

        const submitBtn = this.hasSubmitButtonTarget ? this.submitButtonTarget : null;
        const originalContent = submitBtn?.innerHTML;

        // Show loading state
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        }

        try {
            const formData = new FormData(this.formTarget);
            const response = await fetch(this.formTarget.action, {
                method: this.formTarget.method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.redirected) {
                // Success - navigate to the redirected URL
                if (typeof Turbo !== 'undefined') {
                    Turbo.visit(response.url);
                } else {
                    window.location.href = response.url;
                }
                return;
            }

            const html = await response.text();

            // Check for validation errors
            if (html.includes('input-error') || html.includes('invalid-feedback')) {
                // Re-render the modal with errors
                const container = document.getElementById('global-modal-container');
                if (container) {
                    container.innerHTML = html;
                    // Focus on first error field or first input
                    setTimeout(() => {
                        const firstError = container.querySelector('.input-error, .is-invalid');
                        if (firstError) {
                            firstError.focus();
                            firstError.select?.();
                        }
                    }, 100);
                }
                // Mark as modified again since save failed
                this.formModified = true;
            } else if (response.ok) {
                // Success - redirect to index
                if (typeof Turbo !== 'undefined') {
                    Turbo.visit('/organization');
                } else {
                    window.location.href = '/organization';
                }
            }
        } catch (error) {
            console.error('Form submission error:', error);
            alert('An error occurred while saving. Please try again.');
            // Mark as modified again since save failed
            this.formModified = true;
        } finally {
            if (submitBtn && document.body.style.overflow === 'hidden') {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            }
        }
    }

    async close(event) {
        event?.preventDefault();

        // Check if form has unsaved changes
        if (this.formModified) {
            // Show inline confirmation in footer instead of another modal
            this.showInlineConfirmation();
            return; // Don't close yet
        }

        // Clear the modal container
        const container = document.getElementById('global-modal-container');
        if (container) {
            container.innerHTML = '';
        }
    }

    /**
     * Actually close the modal (called after user confirms)
     */
    forceClose() {
        const container = document.getElementById('global-modal-container');
        if (container) {
            container.innerHTML = '';
        }
    }

    backdropClick(event) {
        // Only close if clicking directly on the overlay
        if (event.target === this.overlayTarget) {
            this.close(event);
        }
    }

    stopPropagation(event) {
        // Prevent clicks inside modal from bubbling to backdrop
        event.stopPropagation();
    }

    handleEscape(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }

    /**
     * Show inline confirmation by replacing footer buttons
     */
    showInlineConfirmation() {
        // Find the footer
        const footer = this.element.querySelector('.modal-footer-bar');
        if (!footer) return;

        // Store original footer HTML
        if (!this.originalFooterHTML) {
            this.originalFooterHTML = footer.innerHTML;
        }

        // Replace with confirmation buttons
        footer.innerHTML = `
            <div class="w-100">
                <div class="alert alert-warning d-flex align-items-center mb-3" style="background: rgba(251, 146, 60, 0.15); border: 1px solid rgba(251, 146, 60, 0.4); border-radius: 10px; padding: 0.875rem 1rem;">
                    <i class="bi bi-exclamation-triangle me-2" style="font-size: 1.25rem; color: #f97316; flex-shrink: 0;"></i>
                    <span style="color: #1a1a1a; font-weight: 600; line-height: 1.5;">You have unsaved changes. Are you sure you want to discard them?</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn-modal-secondary flex-fill" data-action="click->crud-modal#cancelClose">
                        <i class="bi bi-arrow-left me-2"></i>
                        Continue Editing
                    </button>
                    <button type="button" class="btn-modal-danger flex-fill" data-action="click->crud-modal#confirmClose">
                        <i class="bi bi-trash me-2"></i>
                        Discard Changes
                    </button>
                </div>
            </div>
        `;

        // Add danger button style
        const style = document.createElement('style');
        style.textContent = `
            .btn-modal-danger {
                padding: 0.75rem 1.75rem;
                border-radius: 10px;
                font-weight: 600;
                font-size: 0.9375rem;
                border: none;
                cursor: pointer;
                transition: all 0.2s;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #ef4444, #dc2626);
                color: white;
                box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            }

            .btn-modal-danger:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
            }

            .btn-modal-danger:active {
                transform: translateY(0);
            }
        `;
        if (!document.getElementById('modal-danger-style')) {
            style.id = 'modal-danger-style';
            document.head.appendChild(style);
        }
    }

    /**
     * Cancel close - restore original footer
     */
    cancelClose(event) {
        event?.preventDefault();

        const footer = this.element.querySelector('.modal-footer-bar');
        if (footer && this.originalFooterHTML) {
            footer.innerHTML = this.originalFooterHTML;
        }
    }

    /**
     * Confirm close - discard changes and close modal
     */
    confirmClose(event) {
        event?.preventDefault();

        // Mark as not modified so we don't show confirmation again
        this.formModified = false;

        // Close the modal
        this.forceClose();
    }
}