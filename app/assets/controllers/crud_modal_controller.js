import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["overlay", "form", "submitButton"];

    connect() {
        console.log('🔌 crud-modal controller connected', {
            hasFormTarget: this.hasFormTarget,
            hasSubmitButtonTarget: this.hasSubmitButtonTarget,
            formAction: this.hasFormTarget ? this.formTarget.action : 'N/A'
        });

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

        // Turbo event handlers for form submission
        this.boundTurboSubmitEnd = this.handleTurboSubmitEnd.bind(this);
        this.boundTurboBeforeStreamRender = this.handleTurboBeforeStreamRender.bind(this);
        this.boundTurboSubmitStart = this.handleTurboSubmitStart.bind(this);
        this.boundTurboBeforeFetchResponse = this.handleTurboBeforeFetchResponse.bind(this);

        document.addEventListener('turbo:submit-start', this.boundTurboSubmitStart);
        document.addEventListener('turbo:before-fetch-response', this.boundTurboBeforeFetchResponse);
        document.addEventListener('turbo:submit-end', this.boundTurboSubmitEnd);
        document.addEventListener('turbo:before-stream-render', this.boundTurboBeforeStreamRender);
    }

    disconnect() {
        document.body.style.overflow = '';
        document.removeEventListener('keydown', this.boundHandleEscape);
        document.removeEventListener('turbo:submit-start', this.boundTurboSubmitStart);
        document.removeEventListener('turbo:before-fetch-response', this.boundTurboBeforeFetchResponse);
        document.removeEventListener('turbo:submit-end', this.boundTurboSubmitEnd);
        document.removeEventListener('turbo:before-stream-render', this.boundTurboBeforeStreamRender);
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
        // Check if form uses Turbo - if yes, let Turbo handle it
        if (this.formTarget.dataset.turbo === 'true') {
            // Mark as not modified since we're submitting
            this.formModified = false;

            // Let Turbo handle the submission - don't prevent default
            return;
        }

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
                },
                redirect: 'follow'
            });

            const html = await response.text();

            // Try to parse as JSON first
            let jsonResponse = null;
            try {
                jsonResponse = JSON.parse(html);
            } catch (e) {
                // Not JSON, continue with HTML handling
            }

            // If JSON response with success
            if (jsonResponse && jsonResponse.success) {
                // Dispatch success event for canvas to listen to
                window.dispatchEvent(new CustomEvent('modal:success', {
                    detail: {
                        type: this.formTarget.dataset.entityType || 'entity',
                        response: jsonResponse
                    }
                }));

                // Close the modal
                this.forceClose();
                return;
            }

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
                // Success - dispatch event and close
                window.dispatchEvent(new CustomEvent('modal:success', {
                    detail: { type: 'generic' }
                }));
                this.forceClose();
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

        // Check if this modal is inside a nested container
        const nestedContainer = document.getElementById('nested-modal-container');
        const isNestedModal = nestedContainer && nestedContainer.contains(this.element);

        // Dispatch modal closed event before clearing
        document.dispatchEvent(new CustomEvent('modal:closed', {
            detail: { isNested: isNestedModal }
        }));

        if (isNestedModal) {
            // For nested modals, just clear the nested container
            // The relation-select controller will handle showing the original modal
            nestedContainer.innerHTML = '';
        } else {
            // For regular modals, clear the global container
            const container = document.getElementById('global-modal-container');
            if (container) {
                container.innerHTML = '';
            }
        }
    }

    /**
     * Actually close the modal (called after user confirms)
     */
    forceClose() {
        // Dispatch modal closed event before clearing
        document.dispatchEvent(new CustomEvent('modal:closed'));

        const container = document.getElementById('global-modal-container');
        if (container) {
            container.innerHTML = '';
        }
    }

    backdropClick(event) {
        // Only close if clicking directly on the overlay
        if (event.target === this.overlayTarget) {
            // Check if there's a nested modal
            const nestedContainer = document.getElementById('nested-modal-container');
            const hasNestedModal = nestedContainer && nestedContainer.children.length > 0;

            // Check if THIS modal is the nested one
            const isThisNested = nestedContainer && nestedContainer.contains(this.element);

            // If there's a nested modal and this is the ORIGINAL modal, don't close
            if (hasNestedModal && !isThisNested) {
                return;
            }

            this.close(event);
        }
    }

    stopPropagation(event) {
        // Prevent clicks inside modal from bubbling to backdrop
        event.stopPropagation();
    }

    handleEscape(event) {
        if (event.key === 'Escape') {
            // Check if there's a nested modal
            const nestedContainer = document.getElementById('nested-modal-container');
            const hasNestedModal = nestedContainer && nestedContainer.children.length > 0;

            // Check if THIS modal is the nested one
            const isThisNested = nestedContainer && nestedContainer.contains(this.element);

            // If there's a nested modal and this is the ORIGINAL modal, don't close
            // Let the nested modal handle the ESC key
            if (hasNestedModal && !isThisNested) {
                return;
            }

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

    /**
     * Handle Turbo before fetch response - intercept responses BEFORE Turbo processes them
     */
    async handleTurboBeforeFetchResponse(event) {
        // Check if we have a form target
        if (!this.hasFormTarget) {
            return;
        }

        const response = event.detail.fetchResponse.response;

        // Check if this is a redirect response (3xx status codes or redirected flag)
        const isRedirect = (response.status >= 300 && response.status < 400) || response.redirected;

        if (isRedirect) {
            // PREVENT Turbo from following the redirect
            event.preventDefault();

            // Extract entity ID from redirect URL
            const entityId = this.extractEntityIdFromRedirect(response.url);

            // Dispatch success event for the page to handle
            window.dispatchEvent(new CustomEvent('modal:success', {
                detail: {
                    type: this.formTarget.dataset.entityType || 'entity',
                    entityId: entityId
                }
            }));

            // Mark form as clean and close modal
            this.formModified = false;
            this.forceClose();
            return;
        }

        // Check if this is a 422 validation error from our modal form
        if (response.status === 422) {
            // Prevent Turbo from navigating away
            event.preventDefault();

            try {
                // Clone the response so we can read it
                const clonedResponse = response.clone();
                const html = await clonedResponse.text();

                const container = document.getElementById('global-modal-container');
                if (container) {
                    container.innerHTML = html;

                    // Focus on first error field
                    setTimeout(() => {
                        const firstError = container.querySelector('.input-error, .is-invalid');
                        if (firstError) {
                            firstError.focus();
                            firstError.select?.();
                        }
                    }, 100);
                }
            } catch (error) {
                console.error('❌ Error re-rendering modal with errors:', error);
            }
        }
    }

    /**
     * Handle Turbo form submission start
     */
    handleTurboSubmitStart(event) {
        console.log('🚀 turbo:submit-start fired', {
            formElement: event.detail.formSubmission.formElement,
            isOurForm: event.detail.formSubmission.formElement === this.formTarget,
            formAction: event.detail.formSubmission.formElement.action
        });

        // Check if this is our form
        if (event.detail.formSubmission.formElement !== this.formTarget) {
            console.log('⏭️ Not our form, skipping');
            return;
        }

        console.log('✅ This is our form, disabling submit button');

        // Disable submit button to prevent double submission
        if (this.hasSubmitButtonTarget) {
            const originalContent = this.submitButtonTarget.innerHTML;
            this.submitButtonTarget.disabled = true;
            this.submitButtonTarget.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            // Store original content to restore it later if needed
            this.submitButtonTarget.dataset.originalContent = originalContent;
            console.log('🔘 Submit button disabled');
        } else {
            console.log('⚠️ No submit button target found');
        }
    }

    /**
     * Handle Turbo form submission end
     */
    handleTurboSubmitEnd(event) {
        console.log('🏁 turbo:submit-end fired', {
            formElement: event.detail.formSubmission.formElement,
            isOurForm: event.detail.formSubmission.formElement === this.formTarget,
            success: event.detail.success,
            fetchResponse: event.detail.fetchResponse
        });

        // Check if this is our form
        if (event.detail.formSubmission.formElement !== this.formTarget) {
            console.log('⏭️ Not our form, skipping');
            return;
        }

        console.log('✅ This is our form');

        const fetchResponse = event.detail.fetchResponse;

        // Check if response is a Turbo Stream
        if (fetchResponse) {
            const contentType = fetchResponse.response.headers.get('Content-Type');
            console.log('📦 Response Content-Type:', contentType);

            // If it's a Turbo Stream response, mark form as clean
            // The modal will be closed by the Turbo Stream, so don't re-enable button
            if (contentType && contentType.includes('turbo-stream')) {
                console.log('🎬 Turbo Stream detected, keeping button disabled');
                this.formModified = false;
                return;
            }
        } else {
            console.log('❌ No fetchResponse - request may have failed');
        }

        // If there was an error or validation failed, re-enable the submit button
        if (this.hasSubmitButtonTarget && this.submitButtonTarget.dataset.originalContent) {
            console.log('🔄 Re-enabling submit button');
            this.submitButtonTarget.disabled = false;
            this.submitButtonTarget.innerHTML = this.submitButtonTarget.dataset.originalContent;
            delete this.submitButtonTarget.dataset.originalContent;
        }

        // If submission was successful but not a stream or redirect (re-render with errors)
        if (event.detail.success) {
            console.log('✅ Submission successful');
            this.formModified = false;
        } else {
            console.log('⚠️ Submission not successful');
        }
    }

    /**
     * Handle Turbo stream render - just close the modal, let stream process
     */
    handleTurboBeforeStreamRender(event) {
        // Check if it's a refresh action
        const streamElement = event.target;

        if (streamElement.getAttribute('action') === 'refresh') {
            // Close modal but DON'T prevent the stream from rendering
            // The stream will still execute after this
            this.formModified = false;
            this.forceClose();
        }
    }

    /**
     * Save current scroll position to restore after Turbo navigation
     */
    saveScrollPosition() {
        const scrollY = window.scrollY || window.pageYOffset;
        const scrollX = window.scrollX || window.pageXOffset;

        sessionStorage.setItem('modalSaveScrollY', scrollY.toString());
        sessionStorage.setItem('modalSaveScrollX', scrollX.toString());
    }

    /**
     * Extract entity ID from redirect URL
     */
    extractEntityIdFromRedirect(url) {
        // Try to extract UUID from URL like /talk/019a0982-0e5f-7b80-a0dd-0bdff8e14d57
        const uuidMatch = url.match(/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i);
        if (uuidMatch) {
            return uuidMatch[1];
        }
        return null;
    }
}