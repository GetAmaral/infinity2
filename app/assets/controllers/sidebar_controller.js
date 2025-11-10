// assets/controllers/sidebar_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['section'];
    static values = {
        stateUrl: String
    };

    connect() {
        // ALWAYS set global reference on every connect (Turbo may clear it)
        window.sidebarController = this;

        // Only initialize once (Turbo permanent keeps controller alive)
        if (!this.initialized) {
            this.setupKeyboardShortcuts();
            this.setupTurboListeners();
            this.setupNavbarToggle();
            this.initialized = true;
        }

        // Load state and update active item
        this.loadState().then(() => {
            this.updateActiveItem();
        });
    }

    setupNavbarToggle() {
        // Listen for clicks on navbar toggle button (desktop only)
        document.addEventListener('click', (e) => {
            const navbarBtn = e.target.closest('.sidebar-toggle-navbar-btn');
            if (navbarBtn && window.innerWidth >= 992) {
                e.preventDefault();
                e.stopPropagation();
                this.toggle();
            }
        }, true); // Use capture phase
    }

    setupTurboListeners() {
        // Update active item after every Turbo navigation
        document.addEventListener('turbo:load', () => {
            this.updateActiveItem();
        });
    }

    isAuthenticated() {
        // Check if we're on a public page (login, register, etc.)
        const publicPaths = ['/login', '/register', '/forgot-password', '/reset-password'];
        const currentPath = window.location.pathname;
        return !publicPaths.some(path => currentPath.startsWith(path));
    }

    async loadState() {
        // Load from LocalStorage first (instant) - BEFORE anything renders
        const cached = this.getFromLocalStorage();
        if (cached) {
            this.applyState(cached);
        }

        try {
            // Only fetch from server if authenticated
            if (!this.isAuthenticated()) {
                return;
            }

            // Then load from server (sync)
            const response = await fetch('/api/sidebar/preferences');

            // If unauthorized, don't try to parse JSON
            if (!response.ok) {
                return;
            }

            const serverState = await response.json();

            // Apply server state
            this.applyState(serverState);

            // Update localStorage
            this.saveToLocalStorage(serverState);
        } catch (error) {
            // Silently fail
        }
    }

    applyState(state) {
        // Desktop only: Apply collapsed state (CSS handles visibility)
        if (state.collapsed) {
            this.element.style.flexBasis = '4px';
            this.element.classList.add('collapsed');
        } else {
            this.element.style.flexBasis = '260px';
            this.element.classList.remove('collapsed');
        }

        // Don't apply expanded sections here - let updateActiveItem handle it
        // This ensures the section containing the current page is always open
    }

    async toggle() {
        // Desktop only: toggle collapsed state (mobile uses Bootstrap offcanvas)
        const isCollapsed = this.element.classList.contains('collapsed');

        if (isCollapsed) {
            // Expand
            this.element.style.flexBasis = '260px';
            this.element.classList.remove('collapsed');
        } else {
            // Collapse - thin line
            this.element.style.flexBasis = '4px';
            this.element.classList.add('collapsed');
        }

        await this.saveState({ collapsed: !isCollapsed });

        // Update active item to ensure proper section state after toggle
        this.updateActiveItem();
    }

    expandIfCollapsed(event) {
        // Only expand if sidebar is collapsed
        if (this.element.classList.contains('collapsed')) {
            this.toggle();
        }
    }

    async toggleSection(event) {
        // Don't allow section toggling when collapsed
        if (this.element.classList.contains('collapsed')) {
            return;
        }

        const section = event.currentTarget.closest('.sidebar-section');
        if (!section) return;

        const wasOpen = section.dataset.open === 'true';

        // Close all sections (accordion behavior)
        this.sectionTargets.forEach(s => {
            const content = s.querySelector('.section-content');
            const toggle = s.querySelector('.section-toggle i');
            if (content) content.style.maxHeight = '0';
            if (content) content.style.opacity = '0';
            if (toggle) toggle.style.transform = 'rotate(0deg)';
            s.dataset.open = 'false';
        });

        // Toggle current section
        if (!wasOpen) {
            const content = section.querySelector('.section-content');
            const toggle = section.querySelector('.section-toggle i');
            if (content) content.style.maxHeight = '1000px';
            if (content) content.style.opacity = '1';
            if (toggle) toggle.style.transform = 'rotate(90deg)';
            section.dataset.open = 'true';
        }

        // Save state
        const expandedSections = this.sectionTargets
            .filter(s => s.dataset.open === 'true')
            .map(s => s.dataset.section);

        await this.saveState({ expandedSections });
    }

    async saveState(updates) {
        // Get current state
        const currentState = this.getFromLocalStorage() || {
            collapsed: false,
            expandedSections: []
        };

        // Merge updates
        const newState = { ...currentState, ...updates };

        // Save to LocalStorage immediately
        this.saveToLocalStorage(newState);

        // Only save to server if authenticated
        if (!this.isAuthenticated()) {
            return;
        }

        // Debounced save to server
        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(async () => {
            try {
                const response = await fetch(this.stateUrlValue, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(newState)
                });
            } catch (error) {
                // Silently fail
            }
        }, 1000);
    }

    getFromLocalStorage() {
        const data = localStorage.getItem('luminai_sidebar_state');
        return data ? JSON.parse(data) : null;
    }

    saveToLocalStorage(state) {
        localStorage.setItem('luminai_sidebar_state', JSON.stringify(state));
    }

    updateActiveItem() {
        const currentPath = window.location.pathname;
        let activeSection = null;

        // Remove all active states
        this.element.querySelectorAll('.sidebar-item').forEach(item => {
            item.classList.remove('active');
            item.style.background = '';
            item.style.borderLeft = '';
            item.style.fontWeight = '';
            item.style.color = '';

            const icon = item.querySelector('.sidebar-icon');
            if (icon) icon.style.color = 'var(--luminai-text)';
        });

        // Find and activate current page
        this.element.querySelectorAll('.sidebar-item').forEach(item => {
            const itemHref = item.getAttribute('href');
            if (itemHref === currentPath) {
                item.classList.add('active');
                item.style.background = 'rgba(99, 102, 241, 0.15)';
                item.style.borderLeft = '3px solid var(--luminai-accent)';
                item.style.fontWeight = '600';
                item.style.color = 'var(--luminai-accent)';

                const icon = item.querySelector('.sidebar-icon');
                if (icon) icon.style.color = 'var(--luminai-accent)';

                activeSection = item.closest('.sidebar-section');
            }
        });

        // Only manage sections when NOT collapsed (CSS handles collapsed state)
        const isCollapsed = this.element.classList.contains('collapsed');
        if (!isCollapsed && activeSection) {
            // Close all sections first
            this.sectionTargets.forEach(s => {
                const content = s.querySelector('.section-content');
                const toggle = s.querySelector('.section-toggle i');
                if (content) content.style.maxHeight = '0';
                if (content) content.style.opacity = '0';
                if (toggle) toggle.style.transform = 'rotate(0deg)';
                s.dataset.open = 'false';
            });

            // Open the active section
            this.openSection(activeSection);
        }
    }

    openSection(section) {
        const content = section.querySelector('.section-content');
        const toggle = section.querySelector('.section-toggle i');

        if (content) {
            content.style.maxHeight = '1000px';
            content.style.opacity = '1';
        }
        if (toggle) {
            toggle.style.transform = 'rotate(90deg)';
        }
        section.dataset.open = 'true';
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + B: Toggle sidebar (desktop only)
            if ((e.ctrlKey || e.metaKey) && e.key === 'b' && window.innerWidth >= 992) {
                e.preventDefault();
                this.toggle();
            }
        });
    }
}
