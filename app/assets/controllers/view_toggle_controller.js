import { Controller } from "@hotwired/stimulus";

/**
 * Generic View Toggle Controller - Template-Based Rendering
 *
 * This controller is completely entity-agnostic. It:
 * 1. Fetches JSON data from API
 * 2. Clones HTML templates defined in Twig
 * 3. Populates templates using data-bind-* attributes
 * 4. Manages view state (grid/list/table)
 *
 * Each entity defines its own templates in Twig using <template> elements.
 */
export default class extends Controller {
    static targets = [
        "container",
        "gridButton",
        "listButton",
        "tableButton",
        "tableContainer",
        "gridTemplate",
        "listTemplate",
        "tableRowTemplate"
    ];

    static values = {
        default: { type: String, default: "grid" },
        filterAll: { type: String, default: "All" },
        filterYes: { type: String, default: "Yes" },
        filterNo: { type: String, default: "No" }
    };

    connect() {
        this.entityName = this.getEntityNameFromPage();
        this.isActive = true; // Track if this instance is active
        this.instanceId = Math.random().toString(36).substr(2, 9); // Unique ID for debugging

        console.log(`🔌 Controller CONNECT: ${this.entityName} [${this.instanceId}]`);

        // Use global flag per entity to prevent duplicate initialization across Turbo navigations
        if (!window.__viewToggleInitialized) {
            window.__viewToggleInitialized = {};
        }

        if (window.__viewToggleInitialized[this.entityName]) {
            console.log(`🔄 View toggle already initialized, skipping... ${this.entityName} [${this.instanceId}]`);
            this.isActive = false;
            return;
        }
        console.log(`✨ Initializing view toggle: ${this.entityName} [${this.instanceId}]`);
        window.__viewToggleInitialized[this.entityName] = true;

        this.currentView = 'grid';
        this.searchTerm = '';
        this.sortBy = 'name';
        this.sortDir = 'asc';
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.totalPages = 1;
        this.isLoading = false;
        this.columnFilters = {};

        this.showLoading();

        this.waitForListPreferences().then(() => {
            // Check if still active before proceeding
            if (!this.isActive) {
                console.log('⏭️ Controller disconnected, skipping fetch');
                return;
            }

            const prefs = this.getEntityPreferences();
            this.currentView = prefs.view || 'grid';
            this.sortBy = prefs.sortBy || 'name';
            this.sortDir = prefs.sortDir || 'asc';
            this.currentPage = prefs.currentPage || 1;
            this.itemsPerPage = prefs.itemsPerPage || 10;
            this.searchTerm = prefs.searchTerm || '';
            this.columnFilters = prefs.columnFilters || {};

            this.applyViewUI(this.currentView);
            this.setupSearchHandler();
            this.fetchAndRender();
        });
    }

    disconnect() {
        console.log(`🔌 Controller DISCONNECT: ${this.entityName} [${this.instanceId}]`);

        // Mark this instance as inactive to cancel any pending operations
        this.isActive = false;

        // Clean up the global flag so next visit can initialize fresh
        if (this.entityName && window.__viewToggleInitialized) {
            console.log(`🧹 Cleaning up global flag for: ${this.entityName} [${this.instanceId}]`);
            delete window.__viewToggleInitialized[this.entityName];
        }
    }

    getEntityNameFromPage() {
        const tableElement = this.element.querySelector('table[id$="DataTable"]');
        if (tableElement) {
            return tableElement.id.replace('DataTable', '');
        }
        const path = window.location.pathname;
        const match = path.match(/\/(organization|user|course)s?/);
        if (match) {
            const entity = match[1];
            return entity === 'course' ? 'courses' : entity + 's';
        }
        return 'organizations';
    }

    async waitForListPreferences() {
        for (let i = 0; i < 30; i++) {
            if (window.ListPreferences && window.ListPreferences.loaded) {
                return;
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
    }

    getEntityPreferences() {
        if (window.ListPreferences && window.ListPreferences.loaded) {
            return window.ListPreferences.getEntityPreferences(this.entityName);
        }
        return {
            view: 'grid',
            sortBy: 'name',
            sortDir: 'asc',
            searchTerm: '',
            currentPage: 1,
            itemsPerPage: 10,
            columnFilters: {},
        };
    }

    async savePreference(key, value) {
        if (window.ListPreferences && window.ListPreferences.loaded) {
            await window.ListPreferences.savePreference(this.entityName, key, value);
        }
    }

    setupSearchHandler() {
        const searchInput = document.getElementById(`${this.entityName.replace(/s$/, '')}SearchInput`);
        const clearBtn = document.getElementById('clearSearchBtn');

        if (!searchInput) return;

        let debounceTimer = null;

        searchInput.addEventListener('input', (e) => {
            const value = e.target.value.trim();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                this.searchTerm = value;
                this.currentPage = 1;
                this.savePreference('searchTerm', value);
                this.fetchAndRender();
            }, 400);
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                clearTimeout(debounceTimer);
                searchInput.value = '';
                this.searchTerm = '';
                this.currentPage = 1;
                this.savePreference('searchTerm', '');
                this.fetchAndRender();
            });
        }

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' || e.key === 'Esc') {
                clearTimeout(debounceTimer);
                searchInput.value = '';
                this.searchTerm = '';
                this.currentPage = 1;
                this.savePreference('searchTerm', '');
                this.fetchAndRender();
            }
        });

        if (this.searchTerm) {
            searchInput.value = this.searchTerm;
        }
    }

    setView(event) {
        const view = event.currentTarget.dataset.view;
        if (!['grid', 'list', 'table'].includes(view)) {
            return;
        }

        this.currentView = view;
        this.savePreference('view', view);
        this.applyViewUI(view);
        this.fetchAndRender();
    }

    applyViewUI(view) {
        const container = this.containerTarget;
        const tableContainer = this.hasTableContainerTarget ? this.tableContainerTarget : null;

        if (view === 'grid' || view === 'list') {
            container.style.display = '';
            if (tableContainer) tableContainer.style.display = 'none';
            container.classList.remove('bento-grid', 'list-view');
            container.classList.add(view === 'grid' ? 'bento-grid' : 'list-view');
        } else if (view === 'table') {
            container.style.display = 'none';
            if (tableContainer) tableContainer.style.display = 'block';
        }

        this.updateButtonStates(view);
    }

    updateButtonStates(activeView) {
        if (this.hasGridButtonTarget) {
            this.gridButtonTarget.classList.toggle('active', activeView === 'grid');
        }
        if (this.hasListButtonTarget) {
            this.listButtonTarget.classList.toggle('active', activeView === 'list');
        }
        if (this.hasTableButtonTarget) {
            this.tableButtonTarget.classList.toggle('active', activeView === 'table');
        }
    }

    async fetchAndRender() {
        if (this.isLoading) {
            console.log(`⏸️ Already loading, skipping: ${this.entityName} [${this.instanceId}]`);
            return;
        }

        if (!this.isActive) {
            console.log(`❌ Not active, skipping fetch: ${this.entityName} [${this.instanceId}]`);
            return;
        }

        this.isLoading = true;
        console.log(`🌐 Starting fetch: ${this.entityName} [${this.instanceId}]`);

        try {
            const params = new URLSearchParams({
                q: this.searchTerm,
                page: this.currentPage,
                limit: this.itemsPerPage,
                sortBy: this.sortBy,
                sortDir: this.sortDir,
            });

            // Add column filters to params
            Object.entries(this.columnFilters).forEach(([field, value]) => {
                if (value) {
                    params.append(`filter[${field}]`, value);
                }
            });

            const url = `/${this.entityName.replace(/s$/, '')}/api/search?${params}`;
            console.log(`📡 Fetching URL: ${url} [${this.instanceId}]`);
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`API returned ${response.status}`);
            }

            const data = await response.json();
            const items = data[this.entityName] || [];

            if (data.pagination) {
                this.currentPage = data.pagination.page;
                this.totalPages = data.pagination.totalPages;
                this.updatePaginationInfo(data.pagination, items.length);
            }

            this.renderView(items);

        } catch (error) {
            console.error('❌ Fetch failed:', error);
            this.renderError(error.message);
        } finally {
            this.isLoading = false;
        }
    }

    showLoading() {
        const loadingHtml = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-3">Loading data...</p>
            </div>
        `;

        if (this.hasContainerTarget) {
            this.containerTarget.innerHTML = loadingHtml;
        }
        if (this.hasTableContainerTarget) {
            const tbody = this.tableContainerTarget.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            ${loadingHtml}
                        </td>
                    </tr>
                `;
            }
        }
    }

    renderView(items) {
        if (this.currentView === 'table') {
            this.renderTableView(items);
        } else if (this.currentView === 'list') {
            this.renderListView(items);
        } else {
            this.renderGridView(items);
        }
    }

    renderGridView(items) {
        if (!this.hasGridTemplateTarget) {
            console.error('Grid template not found');
            return;
        }

        const container = this.containerTarget;

        if (items.length === 0) {
            container.innerHTML = this.getEmptyStateHtml();
            return;
        }

        const fragment = document.createDocumentFragment();
        items.forEach((item, index) => {
            const clone = this.populateTemplate(this.gridTemplateTarget, item, index);
            fragment.appendChild(clone);
        });

        container.innerHTML = '';
        container.appendChild(fragment);
        this.triggerBootstrapInit();
    }

    renderListView(items) {
        if (!this.hasListTemplateTarget) {
            console.error('List template not found');
            return;
        }

        const container = this.containerTarget;

        if (items.length === 0) {
            container.innerHTML = this.getEmptyStateHtml();
            return;
        }

        const fragment = document.createDocumentFragment();
        items.forEach((item, index) => {
            const clone = this.populateTemplate(this.listTemplateTarget, item, index);
            fragment.appendChild(clone);
        });

        container.innerHTML = '';
        container.appendChild(fragment);
        this.triggerBootstrapInit();
    }

    renderTableView(items) {
        if (!this.hasTableRowTemplateTarget) {
            console.error('Table row template not found');
            return;
        }

        const tableContainer = this.tableContainerTarget;
        const tbody = tableContainer.querySelector('tbody');

        if (!tbody) {
            console.error('Table tbody not found');
            return;
        }

        this.setupTableHeaders();

        if (items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center py-5">
                        ${this.getEmptyStateHtml()}
                    </td>
                </tr>
            `;
            return;
        }

        const fragment = document.createDocumentFragment();
        items.forEach((item, index) => {
            const clone = this.populateTemplate(this.tableRowTemplateTarget, item, index);
            fragment.appendChild(clone);
        });

        tbody.innerHTML = '';
        tbody.appendChild(fragment);
        this.triggerBootstrapInit();
    }

    /**
     * Populate template with data using data-bind-* attributes
     */
    populateTemplate(template, item, index) {
        const clone = template.content.cloneNode(true);

        // Find all elements with data-bind OR data-bind-if attributes
        const bindElements = clone.querySelectorAll('[data-bind], [data-bind-if]');

        bindElements.forEach(el => {
            // Handle conditional rendering first (data-bind-if)
            if (el.hasAttribute('data-bind-if')) {
                const condition = el.getAttribute('data-bind-if');
                if (!this.evaluateCondition(item, condition)) {
                    el.remove();
                    return; // Skip further processing for removed elements
                }
            }

            // Then handle data binding
            const bindKey = el.getAttribute('data-bind');
            if (!bindKey) return; // No data-bind, only had data-bind-if

            const value = this.getNestedValue(item, bindKey);

            // Handle different binding types
            if (el.hasAttribute('data-bind-text')) {
                el.textContent = value || '';
            } else if (el.hasAttribute('data-bind-html')) {
                el.innerHTML = value || '';
            } else if (el.hasAttribute('data-bind-attr')) {
                const attr = el.getAttribute('data-bind-attr');
                el.setAttribute(attr, value || '');
            } else if (el.hasAttribute('data-bind-href')) {
                el.href = value || '#';
            } else if (el.hasAttribute('data-bind-src')) {
                el.src = value || '';
            } else if (el.hasAttribute('data-bind-class')) {
                const classes = el.getAttribute('data-bind-class').split('|');
                el.classList.add(...classes);
            } else {
                // Default: set text content
                el.textContent = value || '';
            }
        });

        // Handle data-entity-id for the root element
        const rootEl = clone.firstElementChild;
        if (rootEl && item.id) {
            rootEl.setAttribute('data-entity-id', item.id);
        }

        // Handle CSRF token binding (data-csrf-token-bind)
        const csrfTokenElements = clone.querySelectorAll('[data-csrf-token-bind]');
        csrfTokenElements.forEach(el => {
            const bindKey = el.getAttribute('data-csrf-token-bind');
            const csrfToken = this.getNestedValue(item, bindKey);
            if (csrfToken) {
                el.setAttribute('data-csrf-token', csrfToken);
            }
            el.removeAttribute('data-csrf-token-bind');
        });

        // Replace {entityId} placeholder in all attributes
        if (item.id) {
            const allElements = clone.querySelectorAll('*');
            allElements.forEach(el => {
                Array.from(el.attributes).forEach(attr => {
                    if (attr.value.includes('{entityId}')) {
                        el.setAttribute(attr.name, attr.value.replace(/{entityId}/g, item.id));
                    }
                });
            });
        }

        return clone;
    }

    /**
     * Get nested value from object using dot notation
     */
    getNestedValue(obj, path) {
        return path.split('.').reduce((curr, key) => curr?.[key], obj);
    }

    /**
     * Evaluate simple conditions
     */
    evaluateCondition(item, condition) {
        // Handle simple conditions like "active", "!active", "count>0"
        if (condition.startsWith('!')) {
            return !item[condition.slice(1)];
        }
        if (condition.includes('>')) {
            const [key, val] = condition.split('>');
            return (item[key.trim()] || 0) > parseInt(val.trim());
        }
        if (condition.includes('<')) {
            const [key, val] = condition.split('<');
            return (item[key.trim()] || 0) < parseInt(val.trim());
        }
        return !!item[condition];
    }

    setupTableHeaders() {
        const tableContainer = this.tableContainerTarget;
        const thead = tableContainer.querySelector('thead tr');

        if (!thead || thead.dataset.sortSetup) {
            return;
        }

        thead.dataset.sortSetup = 'true';

        thead.querySelectorAll('th').forEach((th) => {
            const sortField = th.dataset.sortField;
            if (!sortField) return;

            th.style.cursor = 'pointer';
            th.style.userSelect = 'none';

            const sortIcon = document.createElement('i');
            sortIcon.className = 'bi bi-arrow-down-up ms-2 sort-indicator';
            sortIcon.style.fontSize = '0.8em';
            th.appendChild(sortIcon);

            th.addEventListener('click', () => {
                if (this.sortBy === sortField) {
                    this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortBy = sortField;
                    this.sortDir = 'asc';
                }

                this.savePreference('sortBy', this.sortBy);
                this.savePreference('sortDir', this.sortDir);
                this.updateSortIndicators(thead);
                this.fetchAndRender();
            });
        });

        this.updateSortIndicators(thead);
        this.setupColumnFilters();
    }

    setupColumnFilters() {
        const tableContainer = this.tableContainerTarget;
        const thead = tableContainer.querySelector('thead');
        const filterRow = thead.querySelector('tr.filters');

        if (!filterRow || filterRow.dataset.filtersSetup) {
            return;
        }

        filterRow.dataset.filtersSetup = 'true';

        // Get all header cells from the first row
        const headerRow = thead.querySelector('tr:first-child');
        const headers = headerRow.querySelectorAll('th');

        // Create filter inputs for each column
        headers.forEach((th, index) => {
            const filterCell = document.createElement('th');
            const sortField = th.dataset.sortField;
            const fieldType = th.dataset.fieldType; // 'boolean', 'date', 'text', etc.

            // Only add input for sortable columns (those with data-sort-field)
            if (sortField) {
                let filterElement;

                // Create select dropdown for boolean fields
                if (fieldType === 'boolean') {
                    filterElement = document.createElement('select');
                    filterElement.className = 'form-select form-select-sm';
                    filterElement.dataset.filterField = sortField;

                    // Add options
                    const options = [
                        { value: '', label: this.filterAllValue },
                        { value: 'true', label: this.filterYesValue },
                        { value: 'false', label: this.filterNoValue }
                    ];

                    options.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt.value;
                        option.textContent = opt.label;
                        filterElement.appendChild(option);
                    });

                    // Restore saved filter value
                    if (this.columnFilters[sortField]) {
                        filterElement.value = this.columnFilters[sortField];
                    }

                    // Add change event listener (no debounce needed for select)
                    filterElement.addEventListener('change', (e) => {
                        const value = e.target.value;
                        if (value) {
                            this.columnFilters[sortField] = value;
                        } else {
                            delete this.columnFilters[sortField];
                        }
                        this.currentPage = 1;
                        this.savePreference('columnFilters', this.columnFilters);
                        this.fetchAndRender();
                    });
                } else if (fieldType === 'date') {
                    // Create daterange container with two date inputs
                    const dateRangeContainer = document.createElement('div');
                    dateRangeContainer.className = 'd-flex gap-1';

                    const fromDate = document.createElement('input');
                    fromDate.type = 'date';
                    fromDate.className = 'form-control form-control-sm';
                    fromDate.placeholder = 'From';
                    fromDate.style.fontSize = '0.75rem';
                    fromDate.dataset.filterField = sortField;
                    fromDate.dataset.rangeType = 'from';

                    const toDate = document.createElement('input');
                    toDate.type = 'date';
                    toDate.className = 'form-control form-control-sm';
                    toDate.placeholder = 'To';
                    toDate.style.fontSize = '0.75rem';
                    toDate.dataset.filterField = sortField;
                    toDate.dataset.rangeType = 'to';

                    // Restore saved filter value (format: "from:to")
                    if (this.columnFilters[sortField]) {
                        const [from, to] = this.columnFilters[sortField].split(':');
                        if (from) fromDate.value = from;
                        if (to) toDate.value = to;
                    }

                    // Add change event listener with debounce
                    let debounceTimer = null;
                    const handleDateChange = () => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => {
                            const fromValue = fromDate.value;
                            const toValue = toDate.value;

                            if (fromValue || toValue) {
                                this.columnFilters[sortField] = `${fromValue}:${toValue}`;
                            } else {
                                delete this.columnFilters[sortField];
                            }
                            this.currentPage = 1;
                            this.savePreference('columnFilters', this.columnFilters);
                            this.fetchAndRender();
                        }, 400);
                    };

                    fromDate.addEventListener('change', handleDateChange);
                    toDate.addEventListener('change', handleDateChange);

                    dateRangeContainer.appendChild(fromDate);
                    dateRangeContainer.appendChild(toDate);
                    filterCell.appendChild(dateRangeContainer);
                } else {
                    // Create text input for text fields
                    filterElement = document.createElement('input');
                    filterElement.type = 'text';
                    filterElement.className = 'form-control form-control-sm';
                    filterElement.placeholder = `Filter...`;
                    filterElement.dataset.filterField = sortField;

                    // Restore saved filter value
                    if (this.columnFilters[sortField]) {
                        filterElement.value = this.columnFilters[sortField];
                    }

                    // Add debounced event listener
                    let debounceTimer = null;
                    filterElement.addEventListener('input', (e) => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => {
                            const value = e.target.value.trim();
                            if (value) {
                                this.columnFilters[sortField] = value;
                            } else {
                                delete this.columnFilters[sortField];
                            }
                            this.currentPage = 1;
                            this.savePreference('columnFilters', this.columnFilters);
                            this.fetchAndRender();
                        }, 400);
                    });
                }

                if (filterElement) {
                    filterCell.appendChild(filterElement);
                }
            }

            filterRow.appendChild(filterCell);
        });
    }

    updateSortIndicators(thead) {
        thead.querySelectorAll('th').forEach((th) => {
            const sortField = th.dataset.sortField;
            const sortIcon = th.querySelector('.sort-indicator');

            if (!sortIcon || !sortField) return;

            if (sortField === this.sortBy) {
                sortIcon.className = this.sortDir === 'asc'
                    ? 'bi bi-arrow-up ms-2 sort-indicator'
                    : 'bi bi-arrow-down ms-2 sort-indicator';
                th.style.fontWeight = 'bold';
            } else {
                sortIcon.className = 'bi bi-arrow-down-up ms-2 sort-indicator';
                th.style.fontWeight = 'normal';
            }
        });
    }

    getEmptyStateHtml() {
        return `
            <div class="col-12 text-center py-5">
                <i class="bi bi-search fs-1 text-muted"></i>
                <p class="text-muted mt-3">No results found</p>
            </div>
        `;
    }

    renderError(message) {
        const container = this.currentView === 'table' ? this.tableContainerTarget : this.containerTarget;
        container.innerHTML = `
            <div class="alert alert-danger m-3" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Failed to load data: ${this.escapeHtml(message)}
            </div>
        `;
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    updatePaginationInfo(pagination, itemsCount) {
        const itemsStartEl = document.getElementById('itemsStart');
        const itemsEndEl = document.getElementById('itemsEnd');
        const itemsTotalEl = document.getElementById('itemsTotal');

        if (!itemsStartEl || !itemsEndEl || !itemsTotalEl) {
            return;
        }

        const start = pagination.total === 0 ? 0 : ((pagination.page - 1) * pagination.limit) + 1;
        const end = Math.min(pagination.page * pagination.limit, pagination.total);

        itemsStartEl.textContent = start;
        itemsEndEl.textContent = end;
        itemsTotalEl.textContent = pagination.total;
    }

    triggerBootstrapInit() {
        requestAnimationFrame(async () => {
            if (typeof window.initGlobalTooltips === 'function') {
                await window.initGlobalTooltips();
            }
            if (typeof window.initGlobalDropdowns === 'function') {
                await window.initGlobalDropdowns();
            }
        });
    }
}
