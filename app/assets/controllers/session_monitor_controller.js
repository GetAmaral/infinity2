import { Controller } from '@hotwired/stimulus';

/**
 * Enhanced Session Monitor Controller
 *
 * Features:
 * - Client-side activity tracking (mouse, keyboard, scroll, touch)
 * - Server polling every 5 minutes to validate session status
 * - Progressive warnings at 5min, 2min, and 1min
 * - Auto-save for forms before session expires
 * - Modal dialog with countdown for final warning
 * - Sound alerts (user preference)
 * - Visual session indicator (traffic light in navbar)
 * - Session analytics tracking
 * - Remember Me integration
 * - Multi-tab session synchronization via BroadcastChannel
 *
 * WCAG 2.2.1 Compliant: Warns users at least 2 minutes before timeout
 */
export default class extends Controller {
    static values = {
        lifetime: Number,      // Session lifetime in seconds (from server)
        statusUrl: String,     // URL for status check (no activity)
        keepaliveUrl: String,  // URL for keepalive (extends session)
    }

    connect() {
        // Initialize state
        this.lastActivity = Date.now();
        this.sessionStart = Date.now();
        this.isRedirecting = false; // Guard flag to prevent infinite redirect loop
        this.warnings = {
            fiveMinute: false,
            twoMinute: false,
            oneMinute: false,
        };

        // Analytics tracking
        this.analytics = {
            warningsShown: 0,
            extensionsRequested: 0,
            sessionExpired: false,
        };

        // Check user preferences
        this.loadPreferences();

        // Initialize session indicator
        this.createSessionIndicator();

        // Activity tracking
        this.trackActivity();

        // Server polling every 5 minutes
        this.startServerPolling();

        // Client-side timer check every 10 seconds
        this.startClientTimer();

        // Create toast and modal containers
        this.createToastContainer();
        this.createModalContainer();

        // Multi-tab sync via BroadcastChannel
        this.initMultiTabSync();

        // Listen for Remember Me changes
        this.checkRememberMe();
    }

    disconnect() {
        // Clean up timers and event listeners
        if (this.serverPollInterval) clearInterval(this.serverPollInterval);
        if (this.clientCheckInterval) clearInterval(this.clientCheckInterval);
        if (this.countdownInterval) clearInterval(this.countdownInterval);
        if (this.indicatorInterval) clearInterval(this.indicatorInterval);

        this.removeActivityListeners();

        // Close BroadcastChannel
        if (this.broadcastChannel) {
            this.broadcastChannel.close();
        }

        // Send analytics before disconnect
        this.sendAnalytics();
    }

    /**
     * Load user preferences
     */
    loadPreferences() {
        this.preferences = {
            soundEnabled: window.PreferenceManager?.getUserPreference('session_sound_enabled', true) ?? true,
            autoExtend: window.PreferenceManager?.getUserPreference('session_auto_extend', true) ?? true,
        };

        // Only log in development mode
        if (window.location.hostname === 'localhost') {
            console.log('🔐 Session Monitor:', {
                sound: this.preferences.soundEnabled,
                autoExtend: this.preferences.autoExtend
            });
        }
    }

    /**
     * Create visual session indicator (traffic light)
     */
    createSessionIndicator() {
        const navbar = document.querySelector('.navbar .container');
        if (!navbar) return;

        // Find the flex container that holds the dropdowns
        const navbarActions = navbar.querySelector('.d-flex.ms-auto');
        if (!navbarActions) return;

        const indicator = document.createElement('div');
        indicator.id = 'session-status-indicator';
        indicator.className = 'd-flex align-items-center gap-2';
        indicator.style.cssText = 'cursor: pointer;';
        indicator.innerHTML = `
            <div class="d-flex align-items-center gap-1">
                <div id="session-indicator-light" class="rounded-circle" style="width: 8px; height: 8px; background: #22c55e; box-shadow: 0 0 8px #22c55e;"></div>
                <span id="session-indicator-text" class="text-white-50 d-none d-md-inline" style="font-size: 0.75rem;">Session Active</span>
            </div>
        `;

        // Add click handler to show status details
        indicator.addEventListener('click', () => this.showSessionDetails());

        // Append to the end of navbar actions (after user dropdown)
        navbarActions.appendChild(indicator);

        // Update indicator every second
        this.updateSessionIndicator();
        this.indicatorInterval = setInterval(() => {
            this.updateSessionIndicator();
        }, 1000);
    }

    /**
     * Update session indicator color and text
     */
    updateSessionIndicator() {
        const light = document.getElementById('session-indicator-light');
        const text = document.getElementById('session-indicator-text');

        if (!light || !text) return;

        const remaining = this.getRemainingTime();
        const minutes = Math.floor(remaining / 60);

        // Green: > 5 minutes
        // Yellow: 2-5 minutes
        // Red: < 2 minutes
        if (remaining > 300) {
            light.style.background = '#22c55e'; // Green
            light.style.boxShadow = '0 0 8px #22c55e';
            text.textContent = 'Session Active';
            text.className = 'text-white-50 d-none d-md-inline';
        } else if (remaining > 120) {
            light.style.background = '#eab308'; // Yellow
            light.style.boxShadow = '0 0 8px #eab308';
            text.textContent = `${minutes}m remaining`;
            text.className = 'text-warning d-none d-md-inline';
        } else if (remaining > 0) {
            light.style.background = '#ef4444'; // Red
            light.style.boxShadow = '0 0 8px #ef4444';
            text.textContent = `${minutes}:${String(Math.floor(remaining % 60)).padStart(2, '0')}`;
            text.className = 'text-danger d-none d-md-inline';
        } else {
            light.style.background = '#6b7280'; // Gray
            light.style.boxShadow = 'none';
            text.textContent = 'Expired';
            text.className = 'text-muted d-none d-md-inline';
        }
    }

    /**
     * Show session details popup
     */
    showSessionDetails() {
        const remaining = this.getRemainingTime();
        const minutes = Math.floor(remaining / 60);
        const seconds = Math.floor(remaining % 60);
        const elapsed = Math.floor((Date.now() - this.sessionStart) / 1000);
        const elapsedMin = Math.floor(elapsed / 60);

        const toast = this.createToast({
            id: 'session-details-toast',
            title: '🔐 Session Status',
            message: `
                <div class="small">
                    <strong>Time Remaining:</strong> ${minutes}:${String(seconds).padStart(2, '0')}<br>
                    <strong>Session Duration:</strong> ${elapsedMin} minutes<br>
                    <strong>Last Activity:</strong> ${this.formatTimeAgo(Date.now() - this.lastActivity)}<br>
                    <strong>Auto-Extend:</strong> ${this.preferences.autoExtend ? 'Enabled' : 'Disabled'}<br>
                    <strong>Sound Alerts:</strong> ${this.preferences.soundEnabled ? 'Enabled' : 'Disabled'}
                </div>
            `,
            autohide: true,
            dismissible: true,
            type: 'info',
        });

        this.showToast(toast);
        setTimeout(() => this.removeToast('session-details-toast'), 5000);
    }

    /**
     * Multi-tab session synchronization
     */
    initMultiTabSync() {
        if (!('BroadcastChannel' in window)) {
            console.warn('BroadcastChannel not supported, multi-tab sync disabled');
            return;
        }

        this.broadcastChannel = new BroadcastChannel('luminai_session_sync');

        // Listen for messages from other tabs
        this.broadcastChannel.addEventListener('message', (event) => {
            const { type, data } = event.data;

            switch (type) {
                case 'session_extended':
                    console.log('📡 Session extended in another tab');
                    this.lastActivity = Date.now();
                    this.resetWarnings();
                    break;

                case 'session_expired':
                    console.log('📡 Session expired in another tab');
                    this.handleSessionExpired();
                    break;

                case 'activity':
                    // Sync activity across tabs
                    this.lastActivity = data.timestamp;
                    break;
            }
        });
    }

    /**
     * Broadcast message to other tabs
     */
    broadcast(type, data = {}) {
        if (this.broadcastChannel) {
            this.broadcastChannel.postMessage({ type, data });
        }
    }

    /**
     * Check if Remember Me is enabled
     */
    checkRememberMe() {
        // Check if user has Remember Me cookie
        const rememberMeCookie = document.cookie
            .split('; ')
            .find(row => row.startsWith('REMEMBERME='));

        if (rememberMeCookie) {
            console.log('✅ Remember Me detected - extended session');
            this.hasRememberMe = true;

            // If auto-extend is enabled, automatically extend session when warnings appear
            if (this.preferences.autoExtend) {
                console.log('♻️ Auto-extend enabled');
            }
        }
    }

    /**
     * Track user activity (mouse, keyboard, scroll, touch)
     */
    trackActivity() {
        const activityEvents = ['mousedown', 'keydown', 'scroll', 'touchstart', 'click'];

        this.activityHandler = () => {
            this.lastActivity = Date.now();

            // Broadcast activity to other tabs
            this.broadcast('activity', { timestamp: Date.now() });

            // Auto-save forms on activity
            this.autoSaveForms();

            // If auto-extend is enabled and warnings are active, extend session
            if (this.preferences.autoExtend && this.hasWarnings()) {
                this.keepAlive();
            }
        };

        activityEvents.forEach(event => {
            document.addEventListener(event, this.activityHandler, { passive: true });
        });
    }

    removeActivityListeners() {
        const activityEvents = ['mousedown', 'keydown', 'scroll', 'touchstart', 'click'];
        activityEvents.forEach(event => {
            document.removeEventListener(event, this.activityHandler);
        });
    }

    /**
     * Check if any warnings are active
     */
    hasWarnings() {
        return this.warnings.fiveMinute || this.warnings.twoMinute || this.warnings.oneMinute;
    }

    /**
     * Reset all warnings
     */
    resetWarnings() {
        this.warnings = {
            fiveMinute: false,
            twoMinute: false,
            oneMinute: false,
        };

        this.removeToast('session-warning-5min');
        this.removeToast('session-warning-2min');

        if (this.modalInstance) {
            this.modalInstance.hide();
        }

        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
    }

    /**
     * Poll server every 5 minutes to validate session
     */
    startServerPolling() {
        // Initial check
        this.checkServerSession();

        // Poll every 5 minutes (300,000ms)
        this.serverPollInterval = setInterval(() => {
            this.checkServerSession();
        }, 300000);
    }

    /**
     * Check remaining time every 10 seconds (client-side)
     */
    startClientTimer() {
        this.clientCheckInterval = setInterval(() => {
            this.checkRemainingTime();
        }, 10000); // Every 10 seconds
    }

    /**
     * Check session status on server (does NOT extend session)
     */
    async checkServerSession() {
        // Don't check if already redirecting
        if (this.isRedirecting) {
            return;
        }

        try {
            const response = await fetch(this.statusUrlValue, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                redirect: 'manual', // Don't follow redirects automatically
            });

            // If we get a redirect (302), session is likely expired
            if (response.type === 'opaqueredirect' || response.status === 302 || response.status === 0) {
                console.warn('Session check returned redirect - session likely expired');
                this.handleSessionExpired();
                return;
            }

            if (!response.ok) {
                console.error('Session status check failed:', response.status);
                return;
            }

            const data = await response.json();

            if (!data.authenticated || data.expired) {
                this.handleSessionExpired();
                return;
            }

            console.log(`✅ Session valid - ${Math.floor(data.remaining / 60)} minutes remaining`);
        } catch (error) {
            console.error('Session status check error:', error);
        }
    }

    /**
     * Calculate remaining time based on client-side activity
     */
    getRemainingTime() {
        const now = Date.now();
        const elapsed = (now - this.lastActivity) / 1000; // seconds
        const remaining = this.lifetimeValue - elapsed;
        return Math.max(0, remaining);
    }

    /**
     * Check remaining time and trigger warnings
     */
    checkRemainingTime() {
        // Don't check if already redirecting
        if (this.isRedirecting) {
            return;
        }

        const remaining = this.getRemainingTime();

        // 5-minute warning (dismissible toast)
        if (remaining <= 300 && remaining > 120 && !this.warnings.fiveMinute) {
            this.showFiveMinuteWarning();
            this.warnings.fiveMinute = true;
            this.analytics.warningsShown++;
        }

        // 2-minute warning (persistent toast)
        if (remaining <= 120 && remaining > 60 && !this.warnings.twoMinute) {
            this.showTwoMinuteWarning();
            this.warnings.twoMinute = true;
            this.analytics.warningsShown++;
        }

        // 1-minute warning (modal with countdown)
        if (remaining <= 60 && !this.warnings.oneMinute) {
            this.showOneMinuteWarning();
            this.warnings.oneMinute = true;
            this.analytics.warningsShown++;
            this.startCountdown();
        }

        // Session expired
        if (remaining <= 0) {
            this.handleSessionExpired();
        }
    }

    /**
     * 5-minute warning: Dismissible toast (top-right)
     */
    showFiveMinuteWarning() {
        this.playSound('warning');

        const toast = this.createToast({
            id: 'session-warning-5min',
            title: '⏰ Session Expiring Soon',
            message: 'Your session will expire in 5 minutes. Save your work!',
            autohide: false,
            dismissible: true,
            type: 'warning',
        });

        this.showToast(toast);
    }

    /**
     * 2-minute warning: Persistent toast (top-center)
     */
    showTwoMinuteWarning() {
        // Remove 5-min toast
        this.removeToast('session-warning-5min');

        this.playSound('warning');

        const toast = this.createToast({
            id: 'session-warning-2min',
            title: '⚠️ Session Expiring',
            message: 'Your session will expire in 2 minutes. Click "Stay Logged In" or save your work now!',
            autohide: false,
            dismissible: false,
            type: 'danger',
            showButton: true,
        });

        this.showToast(toast, 'top-center');
    }

    /**
     * 1-minute warning: Modal dialog with countdown
     */
    showOneMinuteWarning() {
        // Remove all toasts
        this.removeToast('session-warning-5min');
        this.removeToast('session-warning-2min');

        this.playSound('urgent');

        // Show modal
        const modal = this.createModal();
        this.modalElement = modal;
        this.modalInstance = new bootstrap.Modal(modal, {
            backdrop: 'static',
            keyboard: false,
        });
        this.modalInstance.show();
    }

    /**
     * Start countdown timer (updates every second)
     */
    startCountdown() {
        this.countdownInterval = setInterval(() => {
            // Don't check if already redirecting
            if (this.isRedirecting) {
                return;
            }

            const remaining = this.getRemainingTime();

            if (remaining <= 0) {
                this.handleSessionExpired();
                return;
            }

            const seconds = Math.floor(remaining);
            const countdownEl = document.getElementById('session-countdown');
            if (countdownEl) {
                countdownEl.textContent = this.formatTime(seconds);

                // Flash red at <10 seconds
                if (seconds <= 10) {
                    countdownEl.style.animation = 'pulse 1s infinite';
                }
            }
        }, 1000); // Update every second
    }

    /**
     * Format seconds to MM:SS
     */
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    /**
     * Format time ago (e.g., "5 minutes", "2 hours")
     */
    formatTimeAgo(ms) {
        const seconds = Math.floor(ms / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);

        if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        return `${seconds} second${seconds > 1 ? 's' : ''} ago`;
    }

    /**
     * Play sound alert using Web Audio API
     */
    playSound(type = 'warning') {
        if (!this.preferences.soundEnabled) {
            return;
        }

        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            // Different tones for different warning levels
            if (type === 'urgent') {
                // Higher pitch, faster beeps for urgent
                oscillator.frequency.value = 1200;
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
            } else {
                // Lower pitch for warning
                oscillator.frequency.value = 800;
                gainNode.gain.setValueAtTime(0.2, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
            }

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);
        } catch (error) {
            console.error('Sound playback failed:', error);
        }
    }

    /**
     * Keep session alive (extends session)
     */
    async keepAlive() {
        try {
            this.analytics.extensionsRequested++;

            const response = await fetch(this.keepaliveUrlValue, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                console.error('Keepalive failed:', response.status);
                return;
            }

            const data = await response.json();

            if (data.success) {
                console.log('✅ Session extended');

                // Reset timers and warnings
                this.lastActivity = Date.now();
                this.resetWarnings();

                // Broadcast to other tabs
                this.broadcast('session_extended', { timestamp: Date.now() });

                // Show success toast
                this.showSuccessToast('Session extended successfully!');

                // Track analytics
                this.sendAnalytics('session_extended');
            }
        } catch (error) {
            console.error('Keepalive error:', error);
        }
    }

    /**
     * Handle session expiration
     */
    handleSessionExpired() {
        // Guard against multiple simultaneous calls
        if (this.isRedirecting) {
            return;
        }
        this.isRedirecting = true;

        console.log('❌ Session expired - redirecting to login');

        this.analytics.sessionExpired = true;

        // Send analytics before redirect
        this.sendAnalytics('session_expired');

        // Broadcast to other tabs
        this.broadcast('session_expired');

        // Save current page for redirect after login
        sessionStorage.setItem('redirect_after_login', window.location.pathname);

        // Auto-save forms before redirect
        this.autoSaveForms();

        // CRITICAL: Stop all intervals BEFORE redirecting to prevent infinite loop
        if (this.serverPollInterval) {
            clearInterval(this.serverPollInterval);
            this.serverPollInterval = null;
        }
        if (this.clientCheckInterval) {
            clearInterval(this.clientCheckInterval);
            this.clientCheckInterval = null;
        }
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }
        if (this.indicatorInterval) {
            clearInterval(this.indicatorInterval);
            this.indicatorInterval = null;
        }

        console.log('🛑 All session monitoring intervals stopped');

        // Redirect to login
        if (typeof Turbo !== 'undefined') {
            Turbo.visit('/login?expired=1');
        } else {
            window.location.href = '/login?expired=1';
        }
    }

    /**
     * Auto-save all forms on the page
     */
    autoSaveForms() {
        const forms = document.querySelectorAll('form[data-controller*="form-autosave"]');

        forms.forEach(form => {
            const formData = new FormData(form);
            const data = {};

            formData.forEach((value, key) => {
                data[key] = value;
            });

            // Save to localStorage with form ID
            const formId = form.id || form.getAttribute('name') || 'unnamed-form';
            const storageKey = `form_autosave_${formId}`;

            try {
                localStorage.setItem(storageKey, JSON.stringify({
                    data: data,
                    timestamp: Date.now(),
                    url: window.location.pathname,
                }));

                console.log(`💾 Form auto-saved: ${formId}`);
            } catch (error) {
                console.error('Auto-save failed:', error);
            }
        });
    }

    /**
     * Send session analytics
     */
    sendAnalytics(event = 'session_status') {
        const analyticsData = {
            event: event,
            warningsShown: this.analytics.warningsShown,
            extensionsRequested: this.analytics.extensionsRequested,
            sessionExpired: this.analytics.sessionExpired,
            sessionDuration: Math.floor((Date.now() - this.sessionStart) / 1000),
            soundEnabled: this.preferences.soundEnabled,
            autoExtend: this.preferences.autoExtend,
            hasRememberMe: this.hasRememberMe || false,
        };

        // Send to localStorage for later analysis
        try {
            const existingData = JSON.parse(localStorage.getItem('session_analytics') || '[]');
            existingData.push({
                ...analyticsData,
                timestamp: new Date().toISOString(),
            });

            // Keep only last 100 entries
            if (existingData.length > 100) {
                existingData.splice(0, existingData.length - 100);
            }

            localStorage.setItem('session_analytics', JSON.stringify(existingData));
        } catch (error) {
            console.error('Analytics save failed:', error);
        }

        console.log('📊 Analytics:', analyticsData);
    }

    /**
     * Create toast container
     */
    createToastContainer() {
        if (!document.getElementById('session-toast-container')) {
            const container = document.createElement('div');
            container.id = 'session-toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        if (!document.getElementById('session-toast-container-center')) {
            const container = document.createElement('div');
            container.id = 'session-toast-container-center';
            container.className = 'toast-container position-fixed top-50 start-50 translate-middle p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
    }

    /**
     * Create modal container
     */
    createModalContainer() {
        if (!document.getElementById('session-modal-container')) {
            const container = document.createElement('div');
            container.id = 'session-modal-container';
            document.body.appendChild(container);
        }
    }

    /**
     * Create toast element
     */
    createToast({ id, title, message, autohide, dismissible, type, showButton }) {
        const toast = document.createElement('div');
        toast.id = id;
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        if (!autohide) {
            toast.setAttribute('data-bs-autohide', 'false');
        }

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>
                    ${message}
                    ${showButton ? '<div class="mt-2"><button class="btn btn-light btn-sm" onclick="this.closest(\'[data-controller*=\"session-monitor\"]\').sessionMonitorController.keepAlive()">Stay Logged In</button></div>' : ''}
                </div>
                ${dismissible ? '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' : ''}
            </div>
        `;

        return toast;
    }

    /**
     * Show toast
     */
    showToast(toast, position = 'top-right') {
        const container = position === 'top-center'
            ? document.getElementById('session-toast-container-center')
            : document.getElementById('session-toast-container');

        container.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }

    /**
     * Remove toast
     */
    removeToast(id) {
        const toast = document.getElementById(id);
        if (toast) {
            const bsToast = bootstrap.Toast.getInstance(toast);
            if (bsToast) {
                bsToast.hide();
            }
            setTimeout(() => toast.remove(), 500);
        }
    }

    /**
     * Create modal dialog
     */
    createModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'session-expiry-modal';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-labelledby', 'sessionExpiryModalLabel');
        modal.setAttribute('aria-hidden', 'true');

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="background: var(--luminai-dark-surface); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-white" id="sessionExpiryModalLabel">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                            Session Expiring
                        </h5>
                    </div>
                    <div class="modal-body text-white text-center">
                        <p class="fs-5 mb-3">Your session will expire in:</p>
                        <div class="display-4 text-warning mb-3" id="session-countdown" style="font-variant-numeric: tabular-nums;">1:00</div>
                        <p class="text-muted">Click "Stay Logged In" to continue your session, or you will be automatically logged out.</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" onclick="if(typeof Turbo !== 'undefined') { Turbo.visit('/logout'); } else { window.location.href='/logout'; }">Logout Now</button>
                        <button type="button" class="btn btn-primary" onclick="this.closest('[data-controller*=\"session-monitor\"]').sessionMonitorController.keepAlive()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Stay Logged In
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('session-modal-container').appendChild(modal);
        return modal;
    }

    /**
     * Show success toast
     */
    showSuccessToast(message) {
        const toast = this.createToast({
            id: 'session-success-toast',
            title: '✅ Success',
            message: message,
            autohide: true,
            dismissible: true,
            type: 'success',
        });

        this.showToast(toast);

        setTimeout(() => this.removeToast('session-success-toast'), 5000);
    }
}

// Expose controller to window for onclick handlers
document.addEventListener('DOMContentLoaded', () => {
    const monitorEl = document.querySelector('[data-controller*="session-monitor"]');
    if (monitorEl) {
        // Get Stimulus controller instance
        const app = window.Stimulus || window.Application;
        if (app) {
            monitorEl.sessionMonitorController = app.getControllerForElementAndIdentifier(monitorEl, 'session-monitor');
        }
    }
});

// Add pulse animation for countdown
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
`;
document.head.appendChild(style);
