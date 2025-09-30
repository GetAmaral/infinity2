/**
 * List Preferences Manager
 * Manages list view state (search, sort, filter, pagination) with dual storage:
 * - Database: User->listPreferences JSON field (via API)
 * - LocalStorage: Fallback for unauthenticated users
 */
(function() {
    'use strict';

    class ListPreferences {
        constructor() {
            this.preferences = {};
            this.loaded = false;
            this.storageKey = 'infinity_list_preferences';
            this.isAuthenticated = false;
            this.saveQueue = {};
            this.saveTimeout = null;
        }

        /**
         * Initialize and load preferences from API
         */
        async init() {
            try {
                const response = await fetch('/settings/ajax/list-preferences', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.preferences = data.preferences || {};
                        this.isAuthenticated = true;
                        this.loaded = true;
                        console.log('✅ List preferences loaded from database');
                    }
                } else if (response.status === 401) {
                    // User not authenticated, use localStorage only
                    this.isAuthenticated = false;
                    this.loadFromLocalStorage();
                } else {
                    // Other error, fallback to localStorage
                    this.isAuthenticated = false;
                    this.loadFromLocalStorage();
                }
            } catch (error) {
                this.isAuthenticated = false;
                this.loadFromLocalStorage();
            }

            return this.preferences;
        }

        /**
         * Load preferences from localStorage (fallback)
         */
        loadFromLocalStorage() {
            try {
                const stored = localStorage.getItem(this.storageKey);
                if (stored) {
                    this.preferences = JSON.parse(stored);
                    this.loaded = true;
                } else {
                    this.preferences = this.getDefaultPreferences();
                    this.loaded = true;
                }
            } catch (error) {
                this.preferences = this.getDefaultPreferences();
                this.loaded = true;
            }
        }

        /**
         * Get preferences for a specific entity
         */
        getEntityPreferences(entityName) {
            return this.preferences[entityName] || this.getDefaultEntityPreferences();
        }

        /**
         * Save preferences for a specific entity
         */
        async saveEntityPreferences(entityName, preferences) {
            this.preferences[entityName] = preferences;

            // Always save to localStorage
            this.saveToLocalStorage();

            // Skip API for unauthenticated users
            if (!this.isAuthenticated) {
                return;
            }

            // Save to API
            try {
                await fetch(`/settings/ajax/list-preferences/${entityName}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(preferences),
                });
            } catch (error) {
                // Silently fail
            }
        }

        /**
         * Save a single preference value with debouncing
         */
        async savePreference(entityName, key, value) {
            if (!this.preferences[entityName]) {
                this.preferences[entityName] = this.getDefaultEntityPreferences();
            }

            this.preferences[entityName][key] = value;

            // Always save to localStorage immediately
            this.saveToLocalStorage();

            // Skip API calls completely for unauthenticated users
            if (!this.isAuthenticated) {
                return;
            }

            // Queue this save for batched API update
            const queueKey = `${entityName}.${key}`;
            this.saveQueue[queueKey] = { entityName, key, value };

            // Debounce: clear existing timer and set new one
            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => {
                this.flushSaveQueue();
            }, 500); // 500ms debounce
        }

        /**
         * Flush the save queue to API
         */
        async flushSaveQueue() {
            if (Object.keys(this.saveQueue).length === 0) {
                return;
            }

            const queue = { ...this.saveQueue };
            this.saveQueue = {};

            // Group by entity
            const byEntity = {};
            for (const [queueKey, { entityName, key, value }] of Object.entries(queue)) {
                if (!byEntity[entityName]) {
                    byEntity[entityName] = {};
                }
                byEntity[entityName][key] = value;
            }

            // Send one request per entity
            for (const [entityName, changes] of Object.entries(byEntity)) {
                try {
                    await fetch(`/settings/ajax/list-preferences/${entityName}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(changes),
                    });
                } catch (error) {
                    // Silently fail - localStorage already has the data
                }
            }
        }

        /**
         * Save to localStorage
         */
        saveToLocalStorage() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(this.preferences));
            } catch (error) {
                console.warn('⚠️ Failed to save list preferences to localStorage:', error);
            }
        }

        /**
         * Get default preferences structure
         */
        getDefaultPreferences() {
            return {
                organizations: this.getDefaultEntityPreferences(),
                users: this.getDefaultEntityPreferences(),
            };
        }

        /**
         * Get default entity preferences
         */
        getDefaultEntityPreferences() {
            return {
                view: 'grid',
                itemsPerPage: 10,
                sortColumn: null,
                sortDirection: 'asc',
                searchTerm: '',
                filters: [],
                columnFilters: {},
                currentPage: 1,
            };
        }

        /**
         * Clear all preferences
         */
        async clearPreferences() {
            this.preferences = this.getDefaultPreferences();
            localStorage.removeItem(this.storageKey);

            // Skip API for unauthenticated users
            if (!this.isAuthenticated) {
                return;
            }

            try {
                await fetch('/settings/ajax/list-preferences', {
                    method: 'DELETE',
                });
            } catch (error) {
                // Silently fail
            }
        }
    }

    // Create global instance (initialization controlled by base.html.twig)
    window.ListPreferences = new ListPreferences();
})();