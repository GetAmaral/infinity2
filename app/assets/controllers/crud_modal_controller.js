import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["overlay", "form", "submitButton"];

    connect() {
        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Focus first input
        setTimeout(() => {
            const firstInput = this.formTarget?.querySelector('input:not([type=hidden]), textarea');
            firstInput?.focus();
        }, 100);

        // ESC key handler
        this.boundHandleEscape = this.handleEscape.bind(this);
        document.addEventListener('keydown', this.boundHandleEscape);
    }

    disconnect() {
        document.body.style.overflow = '';
        document.removeEventListener('keydown', this.boundHandleEscape);
    }

    async submit(event) {
        event.preventDefault();

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
                window.location.href = response.url;
                return;
            }

            const html = await response.text();

            // Check for validation errors
            if (html.includes('input-error') || html.includes('invalid-feedback')) {
                // Re-render the modal with errors
                const container = document.getElementById('global-modal-container');
                if (container) {
                    container.innerHTML = html;
                }
            } else if (response.ok) {
                // Success - redirect to index
                window.location.href = '/organization';
            }
        } catch (error) {
            console.error('Form submission error:', error);
            alert('An error occurred while saving. Please try again.');
        } finally {
            if (submitBtn && document.body.style.overflow === 'hidden') {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            }
        }
    }

    close(event) {
        event?.preventDefault();
        // Clear the modal container
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
}