/**
 * Infinity Preference Manager
 *
 * SINGLE SOURCE OF TRUTH for all preferences:
 * - UserPreferences (theme, locale, animations, etc.)
 * - ListPreferences (view, sort, search, pagination)
 *
 * Flow:
 * 1. Full page load: Database → localStorage + apply
 * 2. Turbo navigation: localStorage → apply
 * 3. User changes: localStorage + database (API)
 *
 * All parts of the system MUST use this manager.
 */
(function() {
    'use strict';

    class PreferenceManager {
        constructor() {
            this.preferences = null;
            this.loaded = false;
            this.isAuthenticated = false;
            this.saveQueue = {};
            this.saveTimeout = null;
            this.loadPromise = null;
        }

        /**
         * Initialize preferences
         * - Full page load: Load from database, save to localStorage
         * - Turbo navigation: Load from localStorage
         */
        async init(isTurboNavigation = false) {
            // If already loaded, return existing preferences
            if (this.loaded && this.preferences) {
                console.log('✅ Preferences already loaded');
                return this.preferences;
            }

            // If loading is in progress, wait for it
            if (this.loadPromise) {
                return this.loadPromise;
            }

            // Create load promise
            this.loadPromise = (async () => {
                try {
                    if (isTurboNavigation) {
                        // Turbo navigation: Load from localStorage ONLY
                        console.log('🔄 Turbo navigation: Loading from localStorage');
                        this.loadFromLocalStorage();
                        this.applyPreferences();
                    } else {
                        // Full page load: Check if on login/public page first
                        const isPublicPage = window.location.pathname === '/login' ||
                                           window.location.pathname === '/register' ||
                                           window.location.pathname === '/';

                        if (isPublicPage) {
                            // Skip database load on public pages, use localStorage only
                            console.log('📂 Public page: Loading from localStorage only');
                            this.loadFromLocalStorage();
                            this.isAuthenticated = false;
                        } else {
                            // Full page load: Try database first, fallback to localStorage
                            console.log('📥 Full page load: Loading from database');
                            const dbLoaded = await this.loadFromDatabase();

                            if (!dbLoaded) {
                                // Fallback to localStorage
                                console.log('📂 Database load failed, using localStorage');
                                this.loadFromLocalStorage();
                            }
                        }

                        this.applyPreferences();
                    }

                    this.loaded = true;
                    console.log('✅ Preferences loaded:', this.preferences);
                    return this.preferences;

                } catch (error) {
                    console.error('❌ Preference init failed:', error);
                    this.preferences = this.getDefaultPreferences();
                    this.loaded = true;
                    return this.preferences;
                } finally {
                    this.loadPromise = null;
                }
            })();

            return this.loadPromise;
        }

        /**
         * Load preferences from database (on login or full page load)
         */
        async loadFromDatabase() {
            try {
                // Load both UserPreferences and ListPreferences in parallel
                const [userPrefsResponse, listPrefsResponse] = await Promise.all([
                    fetch('/settings/ajax/preferences', {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' }
                    }),
                    fetch('/settings/ajax/list-preferences', {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' }
                    })
                ]);

                if (!userPrefsResponse.ok || !listPrefsResponse.ok) {
                    this.isAuthenticated = false;
                    return false;
                }

                const userData = await userPrefsResponse.json();
                const listData = await listPrefsResponse.json();

                if (!userData.success || !listData.success) {
                    this.isAuthenticated = false;
                    return false;
                }

                // Merge both preference types
                this.preferences = {
                    user: userData.preferences || {},
                    list: listData.preferences || {}
                };

                this.isAuthenticated = true;

                // Save to localStorage for future Turbo navigations
                this.saveToLocalStorage();

                console.log('✅ Loaded from database:', this.preferences);
                return true;

            } catch (error) {
                console.warn('⚠️ Database load failed:', error);
                this.isAuthenticated = false;
                return false;
            }
        }

        /**
         * Load preferences from localStorage
         */
        loadFromLocalStorage() {
            try {
                const stored = localStorage.getItem('infinity_preferences');
                if (stored) {
                    this.preferences = JSON.parse(stored);
                    console.log('✅ Loaded from localStorage');
                } else {
                    this.preferences = this.getDefaultPreferences();
                    console.log('📝 Using default preferences');
                }
            } catch (error) {
                console.warn('⚠️ localStorage parse failed:', error);
                this.preferences = this.getDefaultPreferences();
            }
        }

        /**
         * Save preferences to localStorage
         */
        saveToLocalStorage() {
            try {
                localStorage.setItem('infinity_preferences', JSON.stringify(this.preferences));
            } catch (error) {
                console.error('❌ Failed to save to localStorage:', error);
            }
        }

        /**
         * Apply preferences to the page (theme, etc.)
         */
        applyPreferences() {
            if (!this.preferences) return;

            // Apply theme
            const theme = this.preferences.user?.theme || 'dark';
            document.documentElement.setAttribute('data-theme', theme);

            // Update GlobalTheme if it exists
            if (window.GlobalTheme) {
                window.GlobalTheme.current = theme;
                window.GlobalTheme.updateNavbar();
            }
        }

        /**
         * Get user preference (theme, locale, animations, etc.)
         */
        getUserPreference(key, defaultValue = null) {
            if (!this.preferences?.user) {
                return defaultValue;
            }
            return this.preferences.user[key] ?? defaultValue;
        }

        /**
         * Set user preference and save
         */
        async setUserPreference(key, value) {
            if (!this.preferences) {
                this.preferences = this.getDefaultPreferences();
            }

            this.preferences.user[key] = value;

            // Save to localStorage immediately
            this.saveToLocalStorage();

            // Apply if it's theme
            if (key === 'theme') {
                this.applyPreferences();
            }

            // Queue API save if authenticated
            if (this.isAuthenticated) {
                await this.saveToDatabase('user', { [key]: value });
            }
        }

        /**
         * Get list preference for entity (organizations, users, etc.)
         */
        getListPreference(entityName, key, defaultValue = null) {
            if (!this.preferences?.list?.[entityName]) {
                return defaultValue;
            }
            return this.preferences.list[entityName][key] ?? defaultValue;
        }

        /**
         * Get all list preferences for entity
         */
        getEntityPreferences(entityName) {
            if (!this.preferences?.list?.[entityName]) {
                return this.getDefaultEntityPreferences();
            }
            return this.preferences.list[entityName];
        }

        /**
         * Set list preference and save
         */
        async setListPreference(entityName, key, value) {
            if (!this.preferences) {
                this.preferences = this.getDefaultPreferences();
            }

            if (!this.preferences.list[entityName]) {
                this.preferences.list[entityName] = this.getDefaultEntityPreferences();
            }

            this.preferences.list[entityName][key] = value;

            // Save to localStorage immediately
            this.saveToLocalStorage();

            // Queue API save if authenticated (debounced)
            if (this.isAuthenticated) {
                this.queueListSave(entityName, key, value);
            }
        }

        /**
         * Set multiple list preferences for entity
         */
        async setEntityPreferences(entityName, preferences) {
            if (!this.preferences) {
                this.preferences = this.getDefaultPreferences();
            }

            if (!this.preferences.list[entityName]) {
                this.preferences.list[entityName] = this.getDefaultEntityPreferences();
            }

            // Merge preferences
            this.preferences.list[entityName] = {
                ...this.preferences.list[entityName],
                ...preferences
            };

            // Save to localStorage immediately
            this.saveToLocalStorage();

            // Save to database if authenticated
            if (this.isAuthenticated) {
                await this.saveToDatabase('list', {
                    entityName,
                    preferences: this.preferences.list[entityName]
                });
            }
        }

        /**
         * Queue list preference save with debouncing
         */
        queueListSave(entityName, key, value) {
            const queueKey = `${entityName}.${key}`;
            this.saveQueue[queueKey] = { entityName, key, value };

            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => {
                this.flushSaveQueue();
            }, 500);
        }

        /**
         * Flush save queue to database
         */
        async flushSaveQueue() {
            if (Object.keys(this.saveQueue).length === 0) {
                return;
            }

            const queue = { ...this.saveQueue };
            this.saveQueue = {};

            // Group by entity
            const byEntity = {};
            for (const { entityName, key, value } of Object.values(queue)) {
                if (!byEntity[entityName]) {
                    byEntity[entityName] = {};
                }
                byEntity[entityName][key] = value;
            }

            // Send one request per entity
            for (const [entityName, changes] of Object.entries(byEntity)) {
                await this.saveToDatabase('list', { entityName, preferences: changes });
            }
        }

        /**
         * Save to database API
         */
        async saveToDatabase(type, data) {
            try {
                if (type === 'user') {
                    // Save user preference
                    await fetch('/settings/ajax/preferences', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                } else if (type === 'list') {
                    // Save list preference
                    const { entityName, preferences } = data;
                    await fetch(`/settings/ajax/list-preferences/${entityName}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(preferences)
                    });
                }
            } catch (error) {
                console.warn('⚠️ API save failed (localStorage still updated):', error);
            }
        }

        /**
         * Clear all preferences
         */
        async clearAll() {
            this.preferences = this.getDefaultPreferences();
            this.saveToLocalStorage();

            if (this.isAuthenticated) {
                try {
                    await Promise.all([
                        fetch('/settings/ajax/preferences/reset', { method: 'POST' }),
                        fetch('/settings/ajax/list-preferences', { method: 'DELETE' })
                    ]);
                } catch (error) {
                    console.warn('⚠️ API clear failed:', error);
                }
            }

            this.applyPreferences();
        }

        /**
         * Get default preferences structure
         */
        getDefaultPreferences() {
            return {
                user: {
                    theme: 'dark',
                    locale: 'en',
                    sidebar_collapsed: false,
                    notifications_enabled: true,
                    auto_save: true,
                    animations_enabled: true,
                    dashboard_layout: 'grid',
                    items_per_page: 25,
                    timezone: 'UTC',
                    date_format: 'Y-m-d',
                    time_format: 'H:i:s',
                    currency: 'USD',
                    sound_enabled: true,
                    compact_mode: false
                },
                list: {
                    organizations: this.getDefaultEntityPreferences(),
                    users: this.getDefaultEntityPreferences()
                }
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
                currentPage: 1
            };
        }

        /**
         * Wait for preferences to load
         */
        async waitForLoad() {
            if (this.loaded) {
                return this.preferences;
            }

            for (let i = 0; i < 50; i++) {
                if (this.loaded) {
                    return this.preferences;
                }
                await new Promise(resolve => setTimeout(resolve, 100));
            }

            console.warn('⚠️ Preference load timeout');
            return this.preferences || this.getDefaultPreferences();
        }
    }

    // Create global instance
    window.PreferenceManager = new PreferenceManager();

    // DEPRECATED: Keep for backward compatibility but redirect to PreferenceManager
    window.ListPreferences = {
        get loaded() {
            return window.PreferenceManager.loaded;
        },
        getEntityPreferences(entityName) {
            return window.PreferenceManager.getEntityPreferences(entityName);
        },
        async savePreference(entityName, key, value) {
            return window.PreferenceManager.setListPreference(entityName, key, value);
        },
        async saveEntityPreferences(entityName, preferences) {
            return window.PreferenceManager.setEntityPreferences(entityName, preferences);
        }
    };

})();
