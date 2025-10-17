import { Controller } from '@hotwired/stimulus';

/**
 * User Course Enrollment Controller (for User Detail Page)
 *
 * Handles immediate enrollment/unenrollment for ONE user across MULTIPLE courses
 * - Switch ON = enroll student in course
 * - Switch OFF = unenroll student from course
 * - Immediate action (no confirm button)
 */
export default class extends Controller {
    static targets = ['courseSwitch'];
    static values = {
        userId: String,
        enrollUrl: String
    };

    connect() {
        // Controller connected
    }

    async toggle(event) {
        const switchEl = event.target;
        const courseId = switchEl.dataset.courseId;
        const shouldEnroll = switchEl.checked;

        // Disable switch during API call
        switchEl.disabled = true;

        try {
            const response = await fetch(this.enrollUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    courseId: courseId,
                    enroll: shouldEnroll
                })
            });

            if (!response.ok) {
                throw new Error('Failed to update enrollment');
            }

            const data = await response.json();

            if (data.success) {
                // Update badge
                this.updateBadge(switchEl, shouldEnroll);

                // Show success notification
                this.showNotification(data.message || 'Enrollment updated successfully', 'success');
            } else {
                // Revert switch on error
                switchEl.checked = !shouldEnroll;
                this.showNotification(data.message || 'Failed to update enrollment', 'error');
            }

        } catch (error) {
            console.error('Error toggling enrollment:', error);

            // Revert switch on error
            switchEl.checked = !shouldEnroll;
            this.showNotification('An error occurred while updating enrollment', 'error');
        } finally {
            // Re-enable switch
            switchEl.disabled = false;
        }
    }

    updateBadge(switchEl, isEnrolled) {
        // Find the badge container in the same row
        const row = switchEl.closest('tr');
        if (!row) return;

        const badgeContainer = row.querySelector('.d-flex.justify-content-center');
        if (!badgeContainer) return;

        const enrolledBadge = badgeContainer.querySelector('.badge.bg-success');
        const notEnrolledBadge = badgeContainer.querySelector('.badge.bg-secondary');

        if (enrolledBadge && notEnrolledBadge) {
            if (isEnrolled) {
                enrolledBadge.style.display = 'inline-block';
                notEnrolledBadge.style.display = 'none';
            } else {
                enrolledBadge.style.display = 'none';
                notEnrolledBadge.style.display = 'inline-block';
            }
        }
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
        }, 3000);
    }
}
