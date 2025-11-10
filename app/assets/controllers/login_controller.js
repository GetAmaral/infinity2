import { Controller } from '@hotwired/stimulus';

/**
 * Login Controller with Email-First Organization Lookup
 *
 * Handles seamless subdomain redirect based on user's organization
 * Security features:
 * - Client-side rate limiting
 * - Form validation
 * - Error handling
 */
export default class extends Controller {
    static targets = ['email', 'password', 'passwordGroup', 'submitButton', 'lookupButton', 'errorMessage', 'loadingMessage']
    static values = {
        lookupUrl: String,
        maxAttempts: { type: Number, default: 5 },
        attemptWindow: { type: Number, default: 60000 } // 1 minute in milliseconds
    }

    connect() {
        console.log('🔐 Login controller connected');
        this.attempts = [];
        this.isLookingUp = false;

        // Check if email is pre-filled from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const emailParam = urlParams.get('email');

        if (emailParam && this.hasEmailTarget) {
            this.emailTarget.value = decodeURIComponent(emailParam);
            // Show password field if email is already filled
            this.showPasswordField();
        }
    }

    /**
     * Handle email lookup on button click or Enter key
     */
    async lookupOrganization(event) {
        event.preventDefault();

        // Check rate limiting
        if (!this.checkRateLimit()) {
            this.showError('Too many attempts. Please wait a minute and try again.');
            return;
        }

        const email = this.emailTarget.value.trim();

        // Validate email
        if (!email || !this.isValidEmail(email)) {
            this.showError('Please enter a valid email address');
            this.emailTarget.focus();
            return;
        }

        // Record attempt
        this.attempts.push(Date.now());

        // Disable button and show loading
        this.isLookingUp = true;
        if (this.hasLookupButtonTarget) {
            this.lookupButtonTarget.disabled = true;
            this.lookupButtonTarget.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Finding your organization...';
        }

        if (this.hasLoadingMessageTarget) {
            this.loadingMessageTarget.classList.remove('d-none');
        }

        try {
            const response = await fetch('/api/lookup-organization', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Success! Redirect to organization subdomain
                console.log('✅ Organization found:', data.organizationSlug);

                if (this.hasLoadingMessageTarget) {
                    this.loadingMessageTarget.innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            Redirecting to <strong>${data.organizationName}</strong>...
                        </div>
                    `;
                }

                // Redirect with email pre-filled
                setTimeout(() => {
                    window.location.href = `${data.redirectUrl}?email=${encodeURIComponent(email)}`;
                }, 800);

            } else {
                // Organization not found or error
                console.warn('⚠️ Organization lookup failed:', data.message);

                if (data.error === 'not_found' || data.error === 'admin_root_login') {
                    // Show password field for root domain login (admins)
                    this.showPasswordField();

                    if (data.error === 'admin_root_login') {
                        // Admin user - show success message instead of error
                        if (this.hasLoadingMessageTarget) {
                            this.loadingMessageTarget.innerHTML = `
                                <div class="alert alert-info border-0">
                                    <i class="bi bi-shield-check me-2"></i>
                                    ${data.message}
                                </div>
                            `;
                            this.loadingMessageTarget.classList.remove('d-none');
                        }
                    } else {
                        this.showError('No organization found. If you are an admin, please enter your password.');
                    }
                } else {
                    this.showError(data.message || 'An error occurred. Please try again.');
                }

                this.resetButton();
            }

        } catch (error) {
            console.error('❌ Lookup error:', error);
            this.showError('Connection error. Please check your internet and try again.');
            this.resetButton();
        }
    }

    /**
     * Show password field for root domain login
     */
    showPasswordField() {
        if (this.hasPasswordGroupTarget) {
            this.passwordGroupTarget.classList.remove('d-none');
        }
        if (this.hasLookupButtonTarget) {
            this.lookupButtonTarget.classList.add('d-none');
        }
        if (this.hasSubmitButtonTarget) {
            this.submitButtonTarget.classList.remove('d-none');
        }
        if (this.hasPasswordTarget) {
            this.passwordTarget.focus();
        }
    }

    /**
     * Validate email format
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Check rate limiting (client-side)
     */
    checkRateLimit() {
        const now = Date.now();
        // Remove old attempts outside the window
        this.attempts = this.attempts.filter(time => now - time < this.attemptWindowValue);

        return this.attempts.length < this.maxAttemptsValue;
    }

    /**
     * Show error message
     */
    showError(message) {
        if (this.hasErrorMessageTarget) {
            this.errorMessageTarget.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            this.errorMessageTarget.classList.remove('d-none');
        }
    }

    /**
     * Hide error message
     */
    hideError() {
        if (this.hasErrorMessageTarget) {
            this.errorMessageTarget.classList.add('d-none');
            this.errorMessageTarget.innerHTML = '';
        }
    }

    /**
     * Reset button to original state
     */
    resetButton() {
        this.isLookingUp = false;
        if (this.hasLookupButtonTarget) {
            this.lookupButtonTarget.disabled = false;
            this.lookupButtonTarget.innerHTML = 'Continue';
        }
        if (this.hasLoadingMessageTarget) {
            this.loadingMessageTarget.classList.add('d-none');
        }
    }

    /**
     * Handle email field keypress (context-aware Enter key)
     */
    handleEmailKeypress(event) {
        if (event.key === 'Enter' && !this.isLookingUp) {
            event.preventDefault();

            // Check if we're in lookup mode or login mode
            const isLookupMode = this.hasLookupButtonTarget && !this.lookupButtonTarget.classList.contains('d-none');
            const isLoginMode = this.hasPasswordTarget && this.hasPasswordGroupTarget && !this.passwordGroupTarget.classList.contains('d-none');

            if (isLookupMode) {
                // Lookup mode: trigger organization lookup
                this.lookupOrganization(event);
            } else if (isLoginMode) {
                // Login mode: move focus to password field
                this.passwordTarget.focus();
            }
        }
    }

    /**
     * Handle password field keypress (Enter to submit)
     */
    handlePasswordKeypress(event) {
        if (event.key === 'Enter') {
            // Let the form submit naturally - no need to prevent default
            // The form will submit via the submit button
        }
    }

    /**
     * Cleanup on disconnect
     */
    disconnect() {
        console.log('🔐 Login controller disconnected');
    }
}
