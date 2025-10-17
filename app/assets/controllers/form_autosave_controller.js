import { Controller } from '@hotwired/stimulus';

/**
 * Form Auto-Save Controller
 *
 * Automatically saves and restores form data to prevent data loss during session expiration.
 * Usage:
 *   <form data-controller="form-autosave" data-form-autosave-id-value="user-edit-form">
 *     ...form fields...
 *   </form>
 *
 * Features:
 * - Auto-saves form data to localStorage on change
 * - Restores saved data on page load
 * - Shows notification when restored data is available
 * - Clears saved data on successful form submission
 */
export default class extends Controller {
    static values = {
        id: String,           // Unique form identifier
        saveInterval: Number, // Auto-save interval in ms (default: 5000 = 5s)
    }

    connect() {
        // Set default save interval if not provided
        if (!this.hasSaveIntervalValue) {
            this.saveIntervalValue = 5000; // 5 seconds
        }

        this.storageKey = `form_autosave_${this.idValue}`;

        // Check for restored data
        this.checkForRestoredData();

        // Set up auto-save on input change
        this.setupAutoSave();

        // Clear saved data on successful form submission
        this.element.addEventListener('submit', this.handleSubmit.bind(this));
    }

    disconnect() {
        if (this.autoSaveTimeout) {
            clearTimeout(this.autoSaveTimeout);
        }
    }

    /**
     * Check if there's saved data to restore
     */
    checkForRestoredData() {
        try {
            const savedData = localStorage.getItem(this.storageKey);

            if (!savedData) {
                return;
            }

            const { data, timestamp, url } = JSON.parse(savedData);

            // Only restore if same page
            if (url !== window.location.pathname) {
                return;
            }

            // Check if data is not too old (7 days)
            const age = Date.now() - timestamp;
            const maxAge = 7 * 24 * 60 * 60 * 1000; // 7 days in ms

            if (age > maxAge) {
                localStorage.removeItem(this.storageKey);
                return;
            }

            // Show restore notification
            this.showRestoreNotification(data, timestamp);
        } catch (error) {
            console.error('Error checking for restored data:', error);
        }
    }

    /**
     * Show notification with option to restore data
     */
    showRestoreNotification(data, timestamp) {
        const timeAgo = this.formatTimeAgo(Date.now() - timestamp);

        // Create notification
        const notification = document.createElement('div');
        notification.className = 'alert alert-info border-0 rounded-3 d-flex align-items-center justify-content-between';
        notification.style.cssText = 'background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3) !important; color: #3b82f6;';
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill me-2"></i>
                <div>
                    <strong>Unsaved Changes Recovered</strong><br>
                    <small>We found unsaved changes from ${timeAgo} ago. Would you like to restore them?</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-primary" data-action="click->form-autosave#restore">
                    Restore
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="click->form-autosave#discardSaved">
                    Discard
                </button>
            </div>
        `;

        // Insert before form
        this.element.parentNode.insertBefore(notification, this.element);

        // Store data for restoration
        this.savedFormData = data;
        this.notificationElement = notification;
    }

    /**
     * Restore saved form data
     */
    restore() {
        if (!this.savedFormData) {
            return;
        }

        // Populate form fields
        Object.entries(this.savedFormData).forEach(([name, value]) => {
            const field = this.element.querySelector(`[name="${name}"]`);

            if (!field) {
                return;
            }

            if (field.type === 'checkbox') {
                field.checked = value === 'on' || value === '1' || value === true;
            } else if (field.type === 'radio') {
                if (field.value === value) {
                    field.checked = true;
                }
            } else {
                field.value = value;
            }

            // Trigger change event for Stimulus controllers
            field.dispatchEvent(new Event('change', { bubbles: true }));
        });

        // Remove notification
        if (this.notificationElement) {
            this.notificationElement.remove();
        }

        console.log('✅ Form data restored');
    }

    /**
     * Discard saved data
     */
    discardSaved() {
        localStorage.removeItem(this.storageKey);
        this.savedFormData = null;

        if (this.notificationElement) {
            this.notificationElement.remove();
        }

        console.log('🗑️ Saved form data discarded');
    }

    /**
     * Set up auto-save on input change
     */
    setupAutoSave() {
        // Debounced auto-save on input change
        this.element.addEventListener('input', () => {
            if (this.autoSaveTimeout) {
                clearTimeout(this.autoSaveTimeout);
            }

            this.autoSaveTimeout = setTimeout(() => {
                this.save();
            }, this.saveIntervalValue);
        });

        // Also save on change (for dropdowns, checkboxes)
        this.element.addEventListener('change', () => {
            if (this.autoSaveTimeout) {
                clearTimeout(this.autoSaveTimeout);
            }

            this.autoSaveTimeout = setTimeout(() => {
                this.save();
            }, this.saveIntervalValue);
        });
    }

    /**
     * Save form data to localStorage
     */
    save() {
        try {
            const formData = new FormData(this.element);
            const data = {};

            formData.forEach((value, key) => {
                data[key] = value;
            });

            // Don't save empty forms
            if (Object.keys(data).length === 0) {
                return;
            }

            const saveData = {
                data: data,
                timestamp: Date.now(),
                url: window.location.pathname,
            };

            localStorage.setItem(this.storageKey, JSON.stringify(saveData));

            console.log(`💾 Form auto-saved: ${this.idValue}`);
        } catch (error) {
            console.error('Auto-save failed:', error);
        }
    }

    /**
     * Handle form submission
     */
    handleSubmit(event) {
        // Clear saved data on successful submission
        // Wait a bit to ensure form is actually submitted
        setTimeout(() => {
            localStorage.removeItem(this.storageKey);
            console.log('🧹 Saved form data cleared after submission');
        }, 1000);
    }

    /**
     * Format time ago (e.g., "5 minutes", "2 hours")
     */
    formatTimeAgo(ms) {
        const seconds = Math.floor(ms / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (days > 0) return `${days} day${days > 1 ? 's' : ''}`;
        if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''}`;
        if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''}`;
        return `${seconds} second${seconds > 1 ? 's' : ''}`;
    }
}
