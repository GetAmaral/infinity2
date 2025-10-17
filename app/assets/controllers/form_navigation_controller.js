import { Controller } from "@hotwired/stimulus";

/**
 * Form Navigation Controller
 *
 * Handles Enter key behavior in forms:
 * - Enter moves to next field instead of submitting
 * - Select fields: select highlighted item and move next
 * - Textareas: insert new line (natural behavior)
 * - Last field: submit form
 *
 * Usage:
 * <form data-controller="form-navigation">
 *   <!-- form fields -->
 * </form>
 */
export default class extends Controller {
    connect() {
        console.log('[form-navigation] Controller connected to form:', this.element);
        this.element.addEventListener('keydown', this.handleKeyDown.bind(this));

        // Auto-focus first field (if not already focused and no autofocus attribute exists)
        this.autoFocusFirstField();
    }

    disconnect() {
        this.element.removeEventListener('keydown', this.handleKeyDown.bind(this));
    }

    /**
     * Auto-focus the first available field in the form
     */
    autoFocusFirstField() {
        // Check if any field already has autofocus attribute
        const hasAutofocus = this.element.querySelector('[autofocus]');
        if (hasAutofocus) {
            return; // Let HTML autofocus handle it
        }

        // Check if focus is already inside the form
        if (this.element.contains(document.activeElement)) {
            return; // Don't steal focus
        }

        // Small delay to ensure DOM is ready (especially for modals)
        setTimeout(() => {
            const fields = this.getFocusableFields();
            if (fields.length > 0) {
                this.focusField(fields[0]);
            }
        }, 150);
    }

    handleKeyDown(event) {
        // Only handle Enter key
        if (event.key !== 'Enter') {
            return;
        }

        const activeElement = document.activeElement;
        console.log('[form-navigation] Enter pressed on:', activeElement, 'tagName:', activeElement?.tagName, 'type:', activeElement?.type);

        // Allow natural Enter behavior in textareas and CKEditor
        if (this.isMultilineField(activeElement)) {
            console.log('[form-navigation] Multiline field detected, allowing default');
            return; // Let default behavior happen (new line)
        }

        // Allow Enter on submit buttons
        if (activeElement.type === 'submit' || activeElement.hasAttribute('data-allow-enter-submit')) {
            console.log('[form-navigation] Submit button, allowing default');
            return; // Let form submit naturally
        }

        console.log('[form-navigation] Preventing default and processing navigation');
        // Prevent default form submission
        event.preventDefault();

        // Handle select fields (including tom-select)
        if (this.isSelectField(activeElement)) {
            this.handleSelectField(activeElement);
        }

        // Move to next field
        this.focusNextField(activeElement);
    }

    /**
     * Check if element is a multiline field (textarea, CKEditor)
     */
    isMultilineField(element) {
        if (!element) return false;

        // Check for textarea
        if (element.tagName === 'TEXTAREA') {
            return true;
        }

        // Check for CKEditor (look for contenteditable)
        if (element.contentEditable === 'true' && element.closest('.ck-editor')) {
            return true;
        }

        return false;
    }

    /**
     * Check if element is a select field
     */
    isSelectField(element) {
        if (!element) return false;

        // Native select
        if (element.tagName === 'SELECT') {
            return true;
        }

        // Tom-select or other custom select libraries
        if (element.closest('.ts-control') || element.closest('.tom-select')) {
            return true;
        }

        return false;
    }

    /**
     * Handle select field - trigger selection if dropdown is open
     */
    handleSelectField(element) {
        // For tom-select, trigger the selection of highlighted item
        const tomSelectWrapper = element.closest('.ts-wrapper');
        if (tomSelectWrapper) {
            const tomSelect = tomSelectWrapper.tomselect;
            if (tomSelect && tomSelect.isOpen) {
                // Tom-select will handle Enter naturally to select item
                return;
            }
        }

        // For native select, just close it
        if (element.tagName === 'SELECT') {
            element.blur();
        }
    }

    /**
     * Get all focusable fields in the form
     */
    getFocusableFields() {
        const selector = `
            input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([disabled]):not([readonly]),
            select:not([disabled]):not([readonly]),
            textarea:not([disabled]):not([readonly]),
            button[type="submit"]:not([disabled]),
            [contenteditable="true"],
            .ts-control input[type="text"]:not([disabled])
        `;

        const fields = Array.from(this.element.querySelectorAll(selector));

        // Filter out fields that are not visible
        return fields.filter(field => {
            const style = window.getComputedStyle(field);
            return style.display !== 'none' &&
                   style.visibility !== 'hidden' &&
                   field.offsetParent !== null;
        });
    }

    /**
     * Focus next field in the form
     */
    focusNextField(currentElement) {
        const fields = this.getFocusableFields();
        console.log('[form-navigation] Found', fields.length, 'focusable fields:', fields.map(f => `${f.tagName}[${f.name || f.id}]`));

        if (fields.length === 0) {
            console.log('[form-navigation] No focusable fields found!');
            return;
        }

        // Find current field index
        let currentIndex = -1;

        // Check if current element is inside a tom-select
        const tomSelectWrapper = currentElement.closest('.ts-wrapper');
        if (tomSelectWrapper) {
            // Find the actual select element
            const originalSelect = tomSelectWrapper.previousElementSibling;
            if (originalSelect && originalSelect.tagName === 'SELECT') {
                currentIndex = fields.indexOf(originalSelect);
            }
        } else {
            currentIndex = fields.indexOf(currentElement);
        }

        // If we can't find current field, try to find it by closest match
        if (currentIndex === -1) {
            for (let i = 0; i < fields.length; i++) {
                if (fields[i].contains(currentElement) || currentElement.contains(fields[i])) {
                    currentIndex = i;
                    break;
                }
            }
        }

        console.log('[form-navigation] Current field index:', currentIndex, 'of', fields.length - 1);

        // Move to next field or submit if last
        if (currentIndex === -1) {
            // Focus first field if we can't determine current
            console.log('[form-navigation] Could not find current, focusing first field');
            fields[0]?.focus();
        } else if (currentIndex === fields.length - 1) {
            // Last field - submit the form
            console.log('[form-navigation] Last field reached, submitting form');
            this.submitForm();
        } else {
            // Focus next field
            const nextField = fields[currentIndex + 1];
            console.log('[form-navigation] Moving to next field:', nextField.tagName, nextField.name || nextField.id);
            this.focusField(nextField);
        }
    }

    /**
     * Focus a field (with special handling for tom-select)
     */
    focusField(field) {
        if (!field) return;

        // Check if it's a tom-select
        if (field.tagName === 'SELECT' && field.tomselect) {
            field.tomselect.focus();
            return;
        }

        // Regular field
        field.focus();

        // For text inputs, select all text for easy replacement
        if (field.tagName === 'INPUT' &&
            (field.type === 'text' || field.type === 'email' || field.type === 'url' || field.type === 'tel')) {
            field.select();
        }
    }

    /**
     * Submit the form
     */
    submitForm() {
        // Use requestSubmit if available (triggers validation)
        if (typeof this.element.requestSubmit === 'function') {
            this.element.requestSubmit();
        } else {
            // Fallback to submit() (doesn't trigger validation)
            this.element.submit();
        }
    }
}