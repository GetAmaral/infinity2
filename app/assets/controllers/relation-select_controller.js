import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

/**
 * Relation Select Controller
 *
 * Handles searchable select fields for ManyToOne and ManyToMany relationships.
 * Integrates with Genmax-generated forms and API search endpoints.
 */
export default class extends Controller {
    static targets = ['select']

    static values = {
        entity: String,      // Target entity name (e.g., "Role", "User")
        route: String,       // API search route name (e.g., "role_api_search")
        multiple: Boolean    // true for ManyToMany, false for ManyToOne
    }

    connect() {
        // If no select target, this controller is on the wrapper div for the "+" button
        // Skip Tom Select initialization - another controller instance handles that
        if (!this.hasSelectTarget) {
            return;
        }

        // Build the API endpoint URL
        const apiUrl = this.buildApiUrl(this.routeValue);

        // Configure Tom Select options
        const options = {
            // Plugins
            plugins: this.multipleValue ? {
                remove_button: {
                    title: 'Remove this item',
                }
            } : {},

            // Behavior
            maxItems: this.multipleValue ? null : 1,
            allowEmptyOption: !this.selectTarget.required,
            closeAfterSelect: !this.multipleValue,

            // Search configuration
            load: (query, callback) => {
                if (!query.length) {
                    return callback();
                }

                fetch(`${apiUrl}?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(json => {
                        // Transform API response to Tom Select format
                        // API returns: { "users": [...], "pagination": {...} }
                        // Tom Select needs: [{ value: "id", text: "display" }]
                        const items = this.transformApiResponse(json);
                        callback(items);
                    })
                    .catch(error => {
                        console.error('❌ Search error:', error);
                        callback();
                    });
            },

            // Performance
            loadThrottle: 300,

            // Placeholder
            placeholder: this.selectTarget.getAttribute('placeholder') || `Search ${this.entityValue}...`,

            // Value and text fields
            valueField: 'value',
            labelField: 'text',
            searchField: 'text'
        };

        try {
            // Initialize Tom Select
            this.tomSelect = new TomSelect(this.selectTarget, options);

            // Ensure form submission syncs the value
            const form = this.selectTarget.closest('form');
            if (form) {
                form.addEventListener('submit', () => {
                    // Force sync before submit
                    if (this.tomSelect) {
                        this.tomSelect.sync();
                    }
                });
            }
        } catch (error) {
            console.error('Failed to initialize Tom Select:', error);
        }
    }

    disconnect() {
        if (this.tomSelect) {
            this.tomSelect.destroy();
        }

        // Remove event listener
        if (this.boundHandleEntityCreated) {
            document.removeEventListener('entity:created', this.boundHandleEntityCreated);
        }
    }

    /**
     * Open modal to create new related entity
     * Triggered by "+" button click
     */
    openAddModal(event) {
        const button = event.currentTarget;
        const addRoute = button.dataset.addRoute;

        if (!addRoute) {
            console.error('No add route specified for relation field');
            return;
        }

        // Build URL from route name (e.g., "calendar_type_new_modal" -> "/calendartype/new")
        // Remove _new_modal suffix and convert to lowercase path
        const entityPath = addRoute.replace(/_new_modal$/, '').replace(/_/g, '').toLowerCase();
        const url = `/${entityPath}/new?modal=1`;

        fetch(url, {
            headers: {
                'Accept': 'text/html',
                'Turbo-Frame': 'modal'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Hide the original modal temporarily (keep it in DOM to preserve state)
            const originalModal = document.querySelector('.modal-fullscreen-overlay');
            if (originalModal) {
                originalModal.style.display = 'none';
                this.originalModal = originalModal;
            }

            // Create a separate container for the nested modal
            let nestedContainer = document.getElementById('nested-modal-container');
            if (!nestedContainer) {
                nestedContainer = document.createElement('div');
                nestedContainer.id = 'nested-modal-container';
                document.body.appendChild(nestedContainer);
            }

            nestedContainer.innerHTML = html;

            // Stimulus will automatically detect the new DOM via MutationObserver

            // Inject hidden input to mark this as a modal request when submitted
            const nestedForm = nestedContainer.querySelector('form');
            if (nestedForm) {
                // Add modal=1 to the form action URL
                const formAction = new URL(nestedForm.action, window.location.origin);
                formAction.searchParams.set('modal', '1');
                nestedForm.action = formAction.toString();

                // Also add as hidden input for safety
                const modalInput = document.createElement('input');
                modalInput.type = 'hidden';
                modalInput.name = 'modal';
                modalInput.value = '1';
                nestedForm.appendChild(modalInput);
            }

            // Listen for Turbo Stream render events (fired when backend returns stream)
            this.boundHandleTurboStream = this.handleTurboStreamRender.bind(this);
            document.addEventListener('turbo:before-stream-render', this.boundHandleTurboStream);

            // Listen for modal close (ESC key or close button)
            this.boundHandleModalClose = this.handleModalClose.bind(this);
            document.addEventListener('modal:closed', this.boundHandleModalClose);
        })
        .catch(error => {
            console.error('Failed to load add modal:', error);
        });
    }

    /**
     * Handle Turbo Stream render - extract entity data and close modal
     */
    handleTurboStreamRender(event) {
        const streamElement = event.target;

        // The data is inside the <template> tag, need to access template.content
        const template = streamElement.querySelector('template');
        if (!template) {
            return;
        }

        const entityContainer = template.content.querySelector('[data-entity-type]');
        if (!entityContainer) {
            return;
        }

        const entityType = entityContainer.dataset.entityType;
        const entityId = entityContainer.dataset.entityId;
        const displayText = entityContainer.dataset.displayText;

        // Check if this is for our field
        if (entityType.toLowerCase() !== this.entityValue.toLowerCase()) {
            return;
        }

        // Find the Tom Select instance by querying the DOM
        let tomSelectInstance = null;

        // First, try to use this controller's Tom Select instance
        if (this.hasSelectTarget && this.tomSelect) {
            tomSelectInstance = this.tomSelect;
        } else if (this.originalModal) {
            // Find the select element in the original modal by searching for the wrapper
            const wrapper = this.originalModal.querySelector(`[data-relation-select-entity-value="${entityType}"]`);
            if (wrapper) {
                const selectElement = wrapper.querySelector('select[data-relation-select-target="select"]');
                if (selectElement && selectElement.tomselect) {
                    tomSelectInstance = selectElement.tomselect;
                }
            }
        }

        // Add entity to Tom Select and select it
        if (tomSelectInstance) {
            tomSelectInstance.addOption({
                value: entityId,
                text: displayText
            });

            if (this.multipleValue) {
                const currentValue = tomSelectInstance.getValue();
                const newValue = Array.isArray(currentValue)
                    ? [...currentValue, entityId]
                    : [entityId];
                tomSelectInstance.setValue(newValue);
            } else {
                tomSelectInstance.setValue(entityId);
            }
        }

        // Close nested modal and restore original
        const nestedContainer = document.getElementById('nested-modal-container');
        if (nestedContainer) {
            nestedContainer.remove();
        }

        if (this.originalModal) {
            this.originalModal.style.display = 'flex';
            delete this.originalModal;
        }

        // Clean up event listeners
        document.removeEventListener('turbo:before-stream-render', this.boundHandleTurboStream);
        if (this.boundHandleModalClose) {
            document.removeEventListener('modal:closed', this.boundHandleModalClose);
        }
    }

    /**
     * Handle modal close (ESC key or close button clicked)
     * Restore original modal without adding any entity
     */
    handleModalClose(event) {
        // Remove the nested modal container
        const nestedContainer = document.getElementById('nested-modal-container');
        if (nestedContainer) {
            nestedContainer.remove();
        }

        // Show the original modal again
        if (this.originalModal) {
            this.originalModal.style.display = 'flex';
            delete this.originalModal;
        }

        // Remove event listeners
        if (this.boundHandleEntityCreated) {
            document.removeEventListener('entity:created', this.boundHandleEntityCreated);
        }
        document.removeEventListener('modal:closed', this.boundHandleModalClose);
    }

    /**
     * Build the API URL from the route name
     * Converts route name like "role_api_search" to "/role/api/search"
     */
    buildApiUrl(routeName) {
        // Extract the entity name from route (e.g., "role_api_search" -> "role")
        const entityPath = routeName.replace(/_api_search$/, '');
        return `/${entityPath}/api/search`;
    }

    /**
     * Transform API response to Tom Select format
     * API returns: { "users": [...], "pagination": {...} }
     * Tom Select needs: [{ value: "id", text: "display" }]
     */
    transformApiResponse(apiResponse) {
        // Find the array of entities (the key that's not "pagination", "total", etc.)
        let entities = [];

        for (const [key, value] of Object.entries(apiResponse)) {
            if (Array.isArray(value)) {
                entities = value;
                break;
            }
        }

        // Transform each entity to Tom Select format
        return entities.map(entity => {
            // Try to find a display field
            const displayText = entity.name || entity.title || entity.label ||
                              entity.display || entity.email ||
                              entity.id || 'Unknown';

            return {
                value: entity.id,
                text: displayText
            };
        });
    }
}
