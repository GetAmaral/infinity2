import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['panel', 'content', 'overlay'];
    static values = {
        dealId: String
    };

    connect() {
        console.log('💼 Deal Detail Controller Connected');

        // Listen for escape key
        this.escapeHandler = this.handleEscape.bind(this);
        document.addEventListener('keydown', this.escapeHandler);
    }

    async open(event) {
        event?.preventDefault();
        event?.stopPropagation();

        // Get deal ID from event or value
        const dealId = event?.currentTarget?.dataset?.dealId ||
                      event?.currentTarget?.closest('[data-deal-id]')?.dataset?.dealId ||
                      this.dealIdValue;

        if (!dealId) {
            console.error('No deal ID found');
            return;
        }

        try {
            // Show panel with loading state
            this.showPanel();
            this.showLoading();

            // Fetch deal details
            const response = await fetch(`/deal/${dealId}`, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load deal details');
            }

            const html = await response.text();

            // Update content
            if (this.hasContentTarget) {
                this.contentTarget.innerHTML = html;
            }

            // Prevent body scroll
            document.body.style.overflow = 'hidden';

        } catch (error) {
            console.error('Failed to load deal details:', error);
            this.showError('Failed to load deal details. Please try again.');
        }
    }

    showPanel() {
        if (this.hasOverlayTarget) {
            this.overlayTarget.classList.add('show');
        }

        if (this.hasPanelTarget) {
            this.panelTarget.classList.add('open');

            // Animate in
            setTimeout(() => {
                this.panelTarget.classList.add('show');
            }, 10);
        }
    }

    close(event) {
        event?.preventDefault();
        event?.stopPropagation();

        if (this.hasPanelTarget) {
            this.panelTarget.classList.remove('show');

            // Wait for animation
            setTimeout(() => {
                this.panelTarget.classList.remove('open');
            }, 300);
        }

        if (this.hasOverlayTarget) {
            this.overlayTarget.classList.remove('show');
        }

        // Restore body scroll
        document.body.style.overflow = '';
    }

    handleEscape(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }

    handleOverlayClick(event) {
        // Close if clicking directly on overlay (not on panel content)
        if (event.target === event.currentTarget) {
            this.close();
        }
    }

    showLoading() {
        if (this.hasContentTarget) {
            this.contentTarget.innerHTML = `
                <div class="deal-detail-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading deal details...</p>
                </div>
            `;
        }
    }

    showError(message) {
        if (this.hasContentTarget) {
            this.contentTarget.innerHTML = `
                <div class="deal-detail-error">
                    <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                    <p class="mt-3">${message}</p>
                    <button class="btn btn-outline-secondary mt-3"
                            data-action="click->deal-detail#close">
                        Close
                    </button>
                </div>
            `;
        }
    }

    disconnect() {
        // Clean up event listeners
        if (this.escapeHandler) {
            document.removeEventListener('keydown', this.escapeHandler);
        }

        // Restore body scroll
        document.body.style.overflow = '';
    }
}
