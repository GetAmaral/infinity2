import { Controller } from "@hotwired/stimulus";

// Live search controller for real-time filtering
export default class extends Controller {
    static values = {
        url: String,
        target: String,
        minLength: { type: Number, default: 2 },
        delay: { type: Number, default: 300 }
    };

    static targets = ["input", "results", "loading"];

    connect() {
        this.timeout = null;
        this.lastQuery = '';
        this.abortController = null;

        // Set up input event listener
        this.element.addEventListener('input', this.handleInput.bind(this));
        this.element.addEventListener('focus', this.handleFocus.bind(this));
        this.element.addEventListener('blur', this.handleBlur.bind(this));

        // Set up keyboard navigation
        this.element.addEventListener('keydown', this.handleKeydown.bind(this));
    }

    disconnect() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
        if (this.abortController) {
            this.abortController.abort();
        }
    }

    handleInput(event) {
        const query = event.target.value.trim();

        // Clear previous timeout
        if (this.timeout) {
            clearTimeout(this.timeout);
        }

        // Set new timeout for delayed search
        this.timeout = setTimeout(() => {
            this.performSearch(query);
        }, this.delayValue);
    }

    handleFocus(event) {
        // Show search suggestions if there's a query
        const query = event.target.value.trim();
        if (query && query.length >= this.minLengthValue) {
            this.showResults();
        }
    }

    handleBlur(event) {
        // Hide results after a short delay to allow for clicks
        setTimeout(() => {
            this.hideResults();
        }, 200);
    }

    handleKeydown(event) {
        if (!this.hasResultsTarget) return;

        const items = this.resultsTarget.querySelectorAll('.search-result-item');
        const currentIndex = Array.from(items).findIndex(item => item.classList.contains('active'));

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.navigateResults(items, currentIndex + 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.navigateResults(items, currentIndex - 1);
                break;
            case 'Enter':
                event.preventDefault();
                if (currentIndex >= 0 && items[currentIndex]) {
                    items[currentIndex].click();
                }
                break;
            case 'Escape':
                this.hideResults();
                this.element.blur();
                break;
        }
    }

    async performSearch(query) {
        // Don't search if query is too short or empty
        if (query.length < this.minLengthValue) {
            this.hideResults();
            // Don't update grid - let the template's custom handler restore original content
            return;
        }

        // Don't search if query hasn't changed
        if (query === this.lastQuery) {
            return;
        }

        this.lastQuery = query;

        // Cancel previous request
        if (this.abortController) {
            this.abortController.abort();
        }

        this.abortController = new AbortController();

        try {
            this.showLoading();

            const url = new URL(this.urlValue, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('page', '1');
            url.searchParams.set('limit', '10');
            url.searchParams.set('sortBy', 'name');
            url.searchParams.set('sortDir', 'asc');

            const response = await fetch(url, {
                signal: this.abortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            this.displayResults(data.organizations || []);
            this.updateTargetGrid(data.organizations || []);

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Search failed:', error);
                this.showError('Search failed. Please try again.');
            }
        } finally {
            this.hideLoading();
        }
    }

    displayResults(organizations) {
        if (!this.hasResultsTarget) {
            this.createResultsContainer();
        }

        if (organizations.length === 0) {
            this.resultsTarget.innerHTML = `
                <div class="search-no-results p-3 text-center">
                    <i class="bi bi-search text-muted mb-2" style="font-size: 2rem;"></i>
                    <p class="text-muted mb-0">No organizations found</p>
                </div>
            `;
        } else {
            this.resultsTarget.innerHTML = organizations.map(org => `
                <div class="search-result-item p-3 d-flex align-items-center" data-org-id="${org.id}">
                    <div class="p-2 rounded-3 me-3" style="background: var(--infinity-ai-gradient);">
                        <i class="bi bi-building text-white"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-white mb-1">${this.escapeHtml(org.name)}</h6>
                        <small class="text-secondary">
                            ${org.userCount} users • ${org.description ? this.escapeHtml(org.description.slice(0, 50)) + '...' : 'No description'}
                        </small>
                    </div>
                    <i class="bi bi-arrow-right text-muted"></i>
                </div>
            `).join('');

            // Add click handlers
            this.resultsTarget.querySelectorAll('.search-result-item').forEach(item => {
                item.addEventListener('click', () => {
                    const orgId = item.dataset.orgId;
                    window.location.href = `/organization/${orgId}`;
                });

                item.addEventListener('mouseenter', () => {
                    this.clearActiveResults();
                    item.classList.add('active');
                });
            });
        }

        this.showResults();
    }

    updateTargetGrid(organizations) {
        const targetElement = document.querySelector(this.targetValue);
        if (!targetElement) return;

        if (organizations.length === 0 && this.lastQuery.length >= this.minLengthValue) {
            targetElement.innerHTML = `
                <div class="bento-item large">
                    <div class="infinity-card p-5 text-center">
                        <div class="mb-4">
                            <i class="bi bi-search" style="font-size: 4rem; color: var(--infinity-text-muted);"></i>
                        </div>
                        <h4 class="text-white mb-3">No organizations found</h4>
                        <p class="text-secondary mb-4">Try adjusting your search terms or <a href="#" onclick="location.reload()" class="text-neon">view all organizations</a>.</p>
                    </div>
                </div>
            `;
        } else if (organizations.length > 0) {
            // Update grid with filtered results
            targetElement.innerHTML = organizations.map((org, index) => `
                <div class="bento-item ${index % 3 === 0 ? 'large' : ''}" id="organization-${org.id}">
                    <div class="infinity-card ai-enhanced p-4 h-100">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="p-2 rounded-3 me-3" style="background: var(--infinity-ai-gradient);">
                                    <i class="bi bi-building text-white fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="text-white mb-1">${this.escapeHtml(org.name)}</h5>
                                    <span class="real-time-badge">
                                        ${org.userCount === 0 ? 'No users' : `${org.userCount} user${org.userCount !== 1 ? 's' : ''}`}
                                    </span>
                                </div>
                            </div>
                        </div>
                        ${org.description ?
                            `<p class="text-secondary mb-3">${this.escapeHtml(org.description.slice(0, 100))}${org.description.length > 100 ? '...' : ''}</p>` :
                            '<p class="text-muted mb-3 fst-italic">No description</p>'
                        }
                        <div class="d-flex justify-content-end">
                            <a href="/organization/${org.id}" class="btn btn-sm infinity-btn-primary">
                                <i class="bi bi-arrow-right me-1"></i>View Details
                            </a>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    }

    navigateResults(items, newIndex) {
        // Clear current active state
        this.clearActiveResults();

        // Clamp index to valid range
        const clampedIndex = Math.max(0, Math.min(newIndex, items.length - 1));

        if (items[clampedIndex]) {
            items[clampedIndex].classList.add('active');
            items[clampedIndex].scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
    }

    clearActiveResults() {
        if (this.hasResultsTarget) {
            this.resultsTarget.querySelectorAll('.search-result-item').forEach(item => {
                item.classList.remove('active');
            });
        }
    }

    showResults() {
        if (this.hasResultsTarget) {
            this.resultsTarget.style.display = 'block';
        }
    }

    hideResults() {
        if (this.hasResultsTarget) {
            this.resultsTarget.style.display = 'none';
        }
    }

    showLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.style.display = 'block';
        }
    }

    hideLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.style.display = 'none';
        }
    }

    showError(message) {
        if (this.hasResultsTarget) {
            this.resultsTarget.innerHTML = `
                <div class="search-error p-3 text-center">
                    <i class="bi bi-exclamation-triangle text-danger mb-2" style="font-size: 2rem;"></i>
                    <p class="text-danger mb-0">${message}</p>
                </div>
            `;
            this.showResults();
        }
    }

    createResultsContainer() {
        if (this.hasResultsTarget) return;

        const resultsContainer = document.createElement('div');
        resultsContainer.className = 'search-results position-absolute w-100';
        resultsContainer.style.cssText = `
            top: 100%;
            left: 0;
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
            background: var(--infinity-dark-surface);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.375rem;
            margin-top: 0.25rem;
            display: none;
        `;

        this.element.style.position = 'relative';
        this.element.appendChild(resultsContainer);

        // Make it a target
        resultsContainer.setAttribute('data-live-search-target', 'results');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}