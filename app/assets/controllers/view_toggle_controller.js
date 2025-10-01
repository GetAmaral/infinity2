import { Controller } from "@hotwired/stimulus";

/**
 * View Toggle Controller - Pure Server-Side Data
 *
 * All filtering, sorting, and pagination happen on the server.
 * This controller only:
 * 1. Fetches JSON from API with query params
 * 2. Renders data in 3 different visual formats (grid/list/table)
 * 3. Maintains state (view, search, sort, page) across view switches
 */
export default class extends Controller {
    static targets = ["container", "gridButton", "listButton", "tableButton", "tableContainer"];

    static values = {
        default: { type: String, default: "grid" },
        storageKey: { type: String, default: "organization-view-preference" }
    };

    connect() {
        // Prevent duplicate initialization during Turbo navigation
        if (this.element.dataset.viewToggleInitialized === 'true') {
            console.log('🔄 View toggle already initialized, skipping...');
            return;
        }
        this.element.dataset.viewToggleInitialized = 'true';

        this.entityName = this.getEntityNameFromPage();
        this.currentView = 'grid';
        this.searchTerm = '';
        this.sortBy = 'name';
        this.sortDir = 'asc';
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.totalPages = 1;
        this.isLoading = false;

        // Show loading state immediately
        this.showLoading();

        // Wait for ListPreferences to be loaded
        this.waitForListPreferences().then(() => {
            // Load saved preferences
            const prefs = this.getEntityPreferences();
            this.currentView = prefs.view || 'grid';
            this.sortBy = prefs.sortBy || 'name';
            this.sortDir = prefs.sortDir || 'asc';
            this.currentPage = prefs.currentPage || 1;
            this.itemsPerPage = prefs.itemsPerPage || 10;
            this.searchTerm = prefs.searchTerm || '';

            console.log(`📋 Loaded preferences:`, prefs);

            // Apply saved view
            this.applyViewUI(this.currentView);

            // Setup event listeners
            this.setupSearchHandler();

            // Fetch initial data from API
            this.fetchAndRender();
        });
    }

    disconnect() {
        // Clean up initialization flag when controller disconnects
        if (this.element) {
            delete this.element.dataset.viewToggleInitialized;
        }
    }

    /**
     * Extract entity name from page
     */
    getEntityNameFromPage() {
        const tableElement = this.element.querySelector('table[id$="DataTable"]');
        if (tableElement) {
            return tableElement.id.replace('DataTable', '');
        }
        const path = window.location.pathname;
        const match = path.match(/\/(organization|user)s?/);
        return match ? match[1] + 's' : 'organizations';
    }

    /**
     * Wait for ListPreferences to be loaded
     */
    async waitForListPreferences() {
        for (let i = 0; i < 30; i++) {
            if (window.ListPreferences && window.ListPreferences.loaded) {
                return;
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        console.warn('⚠️ ListPreferences not loaded after 3 seconds');
    }

    /**
     * Get entity preferences from ListPreferences
     */
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
        };
    }

    /**
     * Save entity preference
     */
    async savePreference(key, value) {
        if (window.ListPreferences && window.ListPreferences.loaded) {
            await window.ListPreferences.savePreference(this.entityName, key, value);
        }
    }

    /**
     * Setup search input handler
     */
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
                this.currentPage = 1; // Reset to first page on new search
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

        // Set initial value
        if (this.searchTerm) {
            searchInput.value = this.searchTerm;
        }
    }

    /**
     * User clicked view button (grid/list/table)
     */
    setView(event) {
        const view = event.currentTarget.dataset.view;
        if (!['grid', 'list', 'table'].includes(view)) {
            console.error('Invalid view:', view);
            return;
        }

        console.log(`🔄 Switching to ${view} view`);
        this.currentView = view;
        this.savePreference('view', view);

        // Update UI and render with current data
        this.applyViewUI(view);
        this.fetchAndRender();
    }

    /**
     * Apply view UI changes (show/hide containers, update buttons)
     */
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

    /**
     * Update active state of toggle buttons
     */
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

    /**
     * Fetch data from API and render in current view
     */
    async fetchAndRender() {
        if (this.isLoading) return;
        this.isLoading = true;

        try {
            // Build API URL with all params
            const params = new URLSearchParams({
                q: this.searchTerm,
                page: this.currentPage,
                limit: this.itemsPerPage,
                sortBy: this.sortBy,
                sortDir: this.sortDir,
            });

            const url = `/${this.entityName.replace(/s$/, '')}/api/search?${params}`;
            console.log(`🌐 Fetching: ${url}`);

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`API returned ${response.status}`);
            }

            const data = await response.json();
            const items = data[this.entityName] || [];

            // Update pagination state
            if (data.pagination) {
                this.currentPage = data.pagination.page;
                this.totalPages = data.pagination.totalPages;
            }

            console.log(`📦 Received ${items.length} items (page ${this.currentPage}/${this.totalPages})`);

            // Render in current view
            this.renderView(items);

        } catch (error) {
            console.error('❌ Fetch failed:', error);
            this.renderError(error.message);
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Show loading state
     */
    showLoading() {
        const loadingHtml = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-3">Loading data...</p>
            </div>
        `;

        // Show loading in both possible containers
        if (this.hasContainerTarget) {
            this.containerTarget.innerHTML = loadingHtml;
        }
        if (this.hasTableContainerTarget) {
            const tbody = this.tableContainerTarget.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mt-3">Loading data...</p>
                        </td>
                    </tr>
                `;
            }
        }
    }

    /**
     * Render items in current view
     */
    renderView(items) {
        if (this.currentView === 'table') {
            this.renderTableView(items);
        } else if (this.currentView === 'list') {
            this.renderListView(items);
        } else {
            this.renderGridView(items);
        }
    }

    /**
     * Render grid view
     */
    renderGridView(items) {
        const container = this.containerTarget;

        if (items.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="bi bi-search fs-1 text-muted"></i>
                    <p class="text-muted mt-3">No results found</p>
                </div>
            `;
            return;
        }

        let html = '';
        items.forEach((item, index) => {
            const isLarge = index % 3 === 0 ? 'large' : '';
            html += this.renderCard(item, isLarge, 'grid');
        });

        container.innerHTML = html;

        // Trigger Bootstrap re-initialization via global function
        this.triggerBootstrapInit();
    }

    /**
     * Render list view
     */
    renderListView(items) {
        const container = this.containerTarget;

        if (items.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="bi bi-search fs-1 text-muted"></i>
                    <p class="text-muted mt-3">No results found</p>
                </div>
            `;
            return;
        }

        let html = '';
        items.forEach(item => {
            html += this.renderCard(item, '', 'list');
        });

        container.innerHTML = html;

        // Trigger Bootstrap re-initialization via global function
        this.triggerBootstrapInit();
    }

    /**
     * Render table view
     */
    renderTableView(items) {
        const tableContainer = this.tableContainerTarget;
        const tbody = tableContainer.querySelector('tbody');

        if (!tbody) {
            console.error('Table tbody not found');
            return;
        }

        // Setup sortable headers (only once)
        this.setupTableHeaders();

        if (items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <i class="bi bi-search fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No results found</p>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        items.forEach(item => {
            html += this.renderTableRow(item);
        });

        tbody.innerHTML = html;

        // Trigger Bootstrap re-initialization via global function
        this.triggerBootstrapInit();
    }

    /**
     * Setup clickable sortable table headers
     * Reads sort field names from data-sort-field attributes
     */
    setupTableHeaders() {
        const tableContainer = this.tableContainerTarget;
        const thead = tableContainer.querySelector('thead tr');

        if (!thead || thead.dataset.sortSetup) {
            return; // Already setup
        }

        thead.dataset.sortSetup = 'true';

        thead.querySelectorAll('th').forEach((th) => {
            // Read sort field from data attribute
            const sortField = th.dataset.sortField;

            if (!sortField) {
                return; // Not sortable (like actions column)
            }

            // Make header clickable
            th.style.cursor = 'pointer';
            th.style.userSelect = 'none';

            // Add sort indicator
            const sortIcon = document.createElement('i');
            sortIcon.className = 'bi bi-arrow-down-up ms-2 sort-indicator';
            sortIcon.style.fontSize = '0.8em';
            th.appendChild(sortIcon);

            // Click handler for sorting
            th.addEventListener('click', () => {
                // Toggle direction if same column
                if (this.sortBy === sortField) {
                    this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortBy = sortField;
                    this.sortDir = 'asc';
                }

                // Save preferences
                this.savePreference('sortBy', this.sortBy);
                this.savePreference('sortDir', this.sortDir);

                // Update UI
                this.updateSortIndicators(thead);

                // Fetch with new sort
                this.fetchAndRender();
            });
        });

        // Set initial sort indicators
        this.updateSortIndicators(thead);
    }

    /**
     * Update sort direction indicators on headers
     */
    updateSortIndicators(thead) {
        thead.querySelectorAll('th').forEach((th) => {
            const sortField = th.dataset.sortField;
            const sortIcon = th.querySelector('.sort-indicator');

            if (!sortIcon || !sortField) return;

            // Update icon and style based on current sort
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

    /**
     * Render a single card (grid or list)
     */
    renderCard(item, sizeClass, viewType) {
        const userCount = item.userCount || 0;
        const description = item.description || '';
        const descriptionPreview = viewType === 'grid' && description.length > 100
            ? description.slice(0, 100) + '...'
            : description;
        const createdAt = new Date(item.createdAt || Date.now()).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric'
        });

        return `
            <div class="bento-item ${sizeClass}" id="organization-${item.id}">
                <div class="infinity-card ai-enhanced p-4 h-100">
                    <div class="d-flex align-items-start mb-3">
                        <div class="org-actions me-3">
                            ${this.renderDropdown(item)}
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2">
                                <div class="p-2 rounded-3 me-3" style="background: var(--infinity-ai-gradient); background-size: 200% 200%; animation: gradientShift 4s ease infinite;">
                                    <i class="bi bi-building text-white fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="text-white mb-1">${this.escapeHtml(item.name)}</h5>
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="real-time-badge">
                                            ${userCount === 0 ? 'No users' : `${userCount} user${userCount > 1 ? 's' : ''}`}
                                        </span>
                                        ${userCount > 5 ? '<span class="ai-status-indicator" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;"><i class="bi bi-star-fill"></i> Active</span>' : ''}
                                    </div>
                                </div>
                            </div>
                            ${description ? `<p class="text-secondary mb-3 org-description">${this.escapeHtml(descriptionPreview)}</p>` : '<p class="text-muted mb-3 fst-italic org-description">No description</p>'}
                            <div class="d-flex justify-content-between align-items-center mt-auto org-footer">
                                <small class="text-muted d-flex align-items-center">
                                    <i class="bi bi-calendar me-1"></i>${createdAt}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Render table row
     */
    renderTableRow(item) {
        const userCount = item.userCount || 0;
        const description = item.description || '';
        const descriptionPreview = description.length > 80 ? description.slice(0, 80) + '...' : description;
        const createdAt = new Date(item.createdAt || Date.now()).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric'
        });

        return `
            <tr id="organization-row-${item.id}">
                <td>
                    ${this.renderDropdown(item)}
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="p-2 rounded-3 me-3" style="background: var(--infinity-ai-gradient); background-size: 200% 200%; animation: gradientShift 4s ease infinite;">
                            <i class="bi bi-building text-white"></i>
                        </div>
                        <strong>${this.escapeHtml(item.name)}</strong>
                    </div>
                </td>
                <td>${description ? this.escapeHtml(descriptionPreview) : '<span class="text-muted fst-italic">No description</span>'}</td>
                <td><span class="badge" style="background: var(--infinity-ai-gradient); color: white;">${userCount}</span></td>
                <td>${createdAt}</td>
            </tr>
        `;
    }

    /**
     * Render actions dropdown
     */
    renderDropdown(item) {
        const entitySingular = this.entityName.replace(/s$/, '');
        return `
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" style="background: var(--infinity-dark-surface);">
                    <li>
                        <a class="dropdown-item" href="/${entitySingular}/${item.id}">
                            <i class="bi bi-eye me-2"></i>View Details
                        </a>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-controller="modal-opener" data-modal-opener-url-value="/${entitySingular}/${item.id}/edit" data-action="click->modal-opener#open">
                            <i class="bi bi-pencil me-2"></i>Edit
                        </button>
                    </li>
                    ${entitySingular === 'organization' ? `<li>
                        <a class="dropdown-item" href="/${entitySingular}/${item.id}/users">
                            <i class="bi bi-people me-2"></i>Manage Users
                        </a>` : ''}
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="/${entitySingular}/${item.id}/delete" class="d-inline" data-controller="confirm-delete" data-confirm-delete-message-value="Are you sure you want to delete ${this.escapeHtml(item.name)}?">
                            <input type="hidden" name="_token" value="">
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-trash me-2"></i>Delete
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        `;
    }

    /**
     * Render error message
     */
    renderError(message) {
        const container = this.currentView === 'table' ? this.tableContainerTarget : this.containerTarget;
        container.innerHTML = `
            <div class="alert alert-danger m-3" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Failed to load data: ${this.escapeHtml(message)}
            </div>
        `;
    }

    /**
     * Escape HTML to prevent XSS
     */
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

    /**
     * Trigger Bootstrap re-initialization using global functions
     * Uses the global initGlobalTooltips and initGlobalDropdowns from base.html.twig
     */
    triggerBootstrapInit() {
        // Use requestAnimationFrame to ensure DOM is fully updated
        requestAnimationFrame(async () => {
            // Call global initialization functions if they exist
            if (typeof window.initGlobalTooltips === 'function') {
                await window.initGlobalTooltips();
            }
            if (typeof window.initGlobalDropdowns === 'function') {
                await window.initGlobalDropdowns();
            }

            console.log('🔄 Bootstrap components re-initialized after view render');
        });
    }

    disconnect() {
        // Cleanup if needed
    }
}