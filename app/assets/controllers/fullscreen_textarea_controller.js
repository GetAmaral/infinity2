import { Controller } from '@hotwired/stimulus';

/**
 * Fullscreen Textarea Controller
 *
 * Adds a permanent fullscreen edit button to every textarea.
 * Opens a fullscreen modal for comfortable editing.
 */
export default class extends Controller {
    connect() {
        console.log('Fullscreen Textarea Controller initialized');
        this.fullscreenModal = null;
        this.textareaButtons = new Map(); // Map of textarea -> {button, container}

        // Bind methods
        this.openFullscreen = this.openFullscreen.bind(this);
        this.closeFullscreen = this.closeFullscreen.bind(this);
        this.handleEscape = this.handleEscape.bind(this);

        // Initialize all existing textareas
        this.initializeTextareas();

        // Watch for dynamically added textareas (for modals, etc.)
        this.observer = new MutationObserver(() => {
            this.initializeTextareas();
        });

        this.observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    initializeTextareas() {
        // Find all textareas
        const textareas = document.querySelectorAll('textarea');

        textareas.forEach(textarea => {
            // Skip if already initialized
            if (this.textareaButtons.has(textarea)) {
                return;
            }

            // Ignore fullscreen modal textarea (don't add button to it)
            if (textarea.classList.contains('fullscreen-textarea-input')) {
                return;
            }

            // Create permanent button for this textarea
            this.createButton(textarea);
        });
    }

    createButton(textarea) {
        // Find or create wrapper container
        let container = textarea.closest('.form-group-modern, .form-group, .mb-3, .mb-2, .fewshot-item');
        if (!container) {
            container = textarea.parentElement;
        }

        // Make container position relative if not already
        const currentPosition = window.getComputedStyle(container).position;
        if (currentPosition === 'static') {
            container.style.position = 'relative';
        }

        // Create fullscreen button
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'fullscreen-textarea-btn-permanent';
        button.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
        button.title = 'Fullscreen Edit';

        // Add click handler
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.openFullscreen(textarea);
        });

        // Prevent any event propagation
        button.addEventListener('mousedown', (e) => {
            e.stopPropagation();
        });

        // Append button to container
        container.appendChild(button);

        // Store reference
        this.textareaButtons.set(textarea, { button, container });
    }

    openFullscreen(textarea) {
        // Create fullscreen modal
        this.fullscreenModal = document.createElement('div');
        this.fullscreenModal.className = 'fullscreen-textarea-modal';
        this.fullscreenModal.innerHTML = `
            <div class="fullscreen-textarea-container">
                <div class="fullscreen-textarea-header">
                    <h3><i class="bi bi-pencil-square me-2"></i>Fullscreen Editor</h3>
                    <button type="button" class="btn-close-fullscreen" data-action="close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="fullscreen-textarea-body">
                    <textarea class="fullscreen-textarea-input"
                              rows="25"
                              placeholder="${textarea.placeholder || 'Enter your text here...'}">${textarea.value}</textarea>
                </div>
                <div class="fullscreen-textarea-footer">
                    <button type="button" class="btn luminai-btn-primary" data-action="close">
                        <i class="bi bi-check-circle me-2"></i>Done
                    </button>
                </div>
            </div>
        `;

        // Add to body
        document.body.appendChild(this.fullscreenModal);

        // Get fullscreen textarea
        const fullscreenTextarea = this.fullscreenModal.querySelector('.fullscreen-textarea-input');

        // Focus on fullscreen textarea
        setTimeout(() => {
            fullscreenTextarea.focus();
            // Move cursor to end
            fullscreenTextarea.setSelectionRange(fullscreenTextarea.value.length, fullscreenTextarea.value.length);
        }, 100);

        // Add event listeners - always save on close
        const closeButtons = this.fullscreenModal.querySelectorAll('[data-action="close"]');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                console.log('[FULLSCREEN] Close button clicked, calling closeFullscreen(true)');
                this.closeFullscreen(true, textarea, fullscreenTextarea);
            });
        });

        // ESC key to save and close
        this.handleEscapeForTextarea = (e) => {
            if (e.key === 'Escape' && this.fullscreenModal) {
                e.preventDefault();
                this.closeFullscreen(true, textarea, fullscreenTextarea);
            }
        };
        document.addEventListener('keydown', this.handleEscapeForTextarea);

        console.log('[FULLSCREEN] Modal opened for textarea:', textarea);
    }

    closeFullscreen(save = true, originalTextarea, fullscreenTextarea) {
        if (!this.fullscreenModal) return;

        console.log('[FULLSCREEN] Closing with save:', save);

        // Always save content back to original textarea
        if (originalTextarea && fullscreenTextarea) {
            const newValue = fullscreenTextarea.value;
            console.log('[FULLSCREEN] Saving value:', newValue);

            originalTextarea.value = newValue;

            // Trigger input event to notify form of change
            originalTextarea.dispatchEvent(new Event('input', { bubbles: true }));
            originalTextarea.dispatchEvent(new Event('change', { bubbles: true }));

            console.log('[FULLSCREEN] Value saved to original textarea');
        }

        // Remove modal
        this.fullscreenModal.remove();
        this.fullscreenModal = null;

        // Remove ESC handler
        if (this.handleEscapeForTextarea) {
            document.removeEventListener('keydown', this.handleEscapeForTextarea);
            this.handleEscapeForTextarea = null;
        }

        // Return focus to original textarea
        if (originalTextarea) {
            originalTextarea.focus();
        }
    }

    handleEscape(e) {
        // Legacy handler - not used anymore
    }

    disconnect() {
        // Clean up all buttons
        this.textareaButtons.forEach(({ button, container }) => {
            if (button && button.parentNode) {
                button.remove();
            }
        });
        this.textareaButtons.clear();

        // Close modal if open
        if (this.fullscreenModal) {
            this.fullscreenModal.remove();
            this.fullscreenModal = null;
        }

        // Stop observing mutations
        if (this.observer) {
            this.observer.disconnect();
        }
    }
}
