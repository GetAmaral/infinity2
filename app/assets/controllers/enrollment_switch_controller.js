import { Controller } from '@hotwired/stimulus';

/**
 * Enrollment Switch Controller
 *
 * Simple list-based enrollment with switches
 * - Switch ON = enroll/activate
 * - Switch OFF = deactivate
 * - Client-side search
 * - Only process on confirm
 */
export default class extends Controller {
    static targets = ['searchInput', 'userRow', 'userName', 'userSwitch', 'confirmButton'];
    static values = {
        courseId: String,
        enrollUrl: String,
        deactivateUrl: String
    };

    connect() {
        console.log('Enrollment Switch Controller connected');
        this.initialStates = new Map(); // Store initial switch states
        this.searchTimeout = null; // For debouncing search

        // Store initial state of all switches
        this.userSwitchTargets.forEach(switchEl => {
            const userId = switchEl.dataset.userId;
            this.initialStates.set(userId, switchEl.checked);

            // Add change listener to update badge
            switchEl.addEventListener('change', (e) => {
                this.updateBadge(e.target);
            });
        });

        console.log('Enrollment Switch Controller connected');
        console.log('Total users:', this.userRowTargets.length);
        console.log('Initial enrollment states:', this.initialStates);
    }

    updateBadge(switchEl) {
        const label = switchEl.parentElement.querySelector('.badge');
        if (label) {
            if (switchEl.checked) {
                label.className = 'badge bg-success';
                label.textContent = 'Enrolled';
            } else {
                label.className = 'badge bg-secondary';
                label.textContent = 'Not Enrolled';
            }
        }
    }

    search(event) {
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        // Debounce search for 300ms
        this.searchTimeout = setTimeout(() => {
            const searchTerm = event.target.value.toLowerCase().trim();
            console.log('🔍 Searching for:', searchTerm);

            let visibleCount = 0;
            let hiddenCount = 0;

            this.userRowTargets.forEach(row => {
                const userName = row.dataset.userName.toLowerCase();
                const userEmail = row.querySelector('small').textContent.toLowerCase();

                // Match against both name and email
                const matches = userName.includes(searchTerm) || userEmail.includes(searchTerm);

                if (matches) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                    hiddenCount++;
                }
            });

            console.log(`✅ Found ${visibleCount} users, hidden ${hiddenCount}`);
        }, 300); // Wait 300ms after user stops typing
    }

    async confirm(event) {
        event.preventDefault();

        const changes = this.getChanges();

        if (changes.toEnroll.length === 0 && changes.toDeactivate.length === 0) {
            this.showNotification('No changes to save', 'info');
            return;
        }

        console.log('Changes to process:', changes);

        this.confirmButtonTarget.disabled = true;
        this.confirmButtonTarget.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Saving...';

        try {
            let enrolledCount = 0;
            let deactivatedCount = 0;

            // Process deactivations
            if (changes.toDeactivate.length > 0) {
                console.log('🔴 Deactivating:', changes.toDeactivate);
                const deactivateResponse = await fetch(this.deactivateUrlValue, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        courseId: this.courseIdValue,
                        studentIds: changes.toDeactivate
                    })
                });

                if (deactivateResponse.ok) {
                    const data = await deactivateResponse.json();
                    deactivatedCount = data.deactivated || 0;
                    console.log('✅ Deactivated:', deactivatedCount);
                }
            }

            // Process enrollments
            if (changes.toEnroll.length > 0) {
                console.log('🟢 Enrolling:', changes.toEnroll);
                const enrollResponse = await fetch(this.enrollUrlValue, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        courseId: this.courseIdValue,
                        studentIds: changes.toEnroll,
                        active: true
                    })
                });

                if (enrollResponse.ok) {
                    const data = await enrollResponse.json();
                    enrolledCount = data.enrolled || 0;
                    console.log('✅ Enrolled:', enrolledCount);
                }
            }

            // Show success message
            const messages = [];
            if (enrolledCount > 0) messages.push(`Enrolled ${enrolledCount} student(s)`);
            if (deactivatedCount > 0) messages.push(`Deactivated ${deactivatedCount} student(s)`);

            this.showNotification(messages.join(', '), 'success');

            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);

        } catch (error) {
            console.error('Error processing enrollments:', error);
            this.showNotification('Failed to process enrollments', 'error');
            this.confirmButtonTarget.disabled = false;
            this.confirmButtonTarget.innerHTML = '<i class="bi bi-check-circle me-2"></i>Confirm';
        }
    }

    getChanges() {
        const toEnroll = [];
        const toDeactivate = [];

        this.userSwitchTargets.forEach(switchEl => {
            const userId = switchEl.dataset.userId;
            const wasEnrolled = this.initialStates.get(userId);
            const isEnrolled = switchEl.checked;

            // Changed from OFF to ON = enroll
            if (!wasEnrolled && isEnrolled) {
                toEnroll.push(userId);
            }

            // Changed from ON to OFF = deactivate
            if (wasEnrolled && !isEnrolled) {
                toDeactivate.push(userId);
            }
        });

        return { toEnroll, toDeactivate };
    }

    showNotification(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 5000);
    }
}
