import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

/**
 * Course Enrollment Controller
 *
 * Manages multi-student enrollment with Tom Select
 * Features:
 * - Multi-select with search for large datasets
 * - Auto-deactivate on unselect (instead of delete)
 * - Real-time enrollment status updates
 */
export default class extends Controller {
    static targets = ['select', 'submitButton'];
    static values = {
        courseId: String,
        enrollUrl: String,
        deactivateUrl: String,
        currentEnrollments: Array, // Array of currently enrolled student IDs
    };

    connect() {
        console.log('Course Enrollment Controller connected');
        console.log('Submit button target:', this.hasSubmitButtonTarget);

        // Debug: Check if we can find the submit button
        if (this.hasSubmitButtonTarget) {
            console.log('✅ Submit button found:', this.submitButtonTarget);
            // Add a direct event listener as a test
            this.submitButtonTarget.addEventListener('click', () => {
                console.log('🔔 Direct click listener fired!');
            });
        } else {
            console.error('❌ Submit button NOT found!');
        }

        this.initializeTomSelect();
    }

    disconnect() {
        if (this.tomSelect) {
            this.tomSelect.destroy();
        }
    }

    initializeTomSelect() {
        // Initialize Tom Select with advanced configuration
        this.tomSelect = new TomSelect(this.selectTarget, {
            plugins: ['remove_button', 'clear_button'],
            maxItems: null, // Allow unlimited selections
            valueField: 'value',
            labelField: 'text',
            searchField: ['text', 'email'],
            placeholder: 'Select students to enroll...',
            persist: false,
            create: false,
            onChange: (value) => this.handleSelectionChange(value),
            render: {
                option: (data, escape) => {
                    const isEnrolled = this.currentEnrollmentsValue.includes(data.value);
                    return `<div class="d-flex justify-content-between align-items-center py-1">
                        <div>
                            <strong>${escape(data.text)}</strong>
                            <br>
                            <small class="text-muted">${escape(data.email)}</small>
                        </div>
                        ${isEnrolled ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>' : ''}
                    </div>`;
                },
                item: (data, escape) => {
                    return `<div class="d-flex align-items-center">
                        <span>${escape(data.text)}</span>
                    </div>`;
                }
            },
            // Load options from select element
            options: this.getSelectOptions(),
        });

        // Pre-select currently ACTIVE enrolled students
        if (this.currentEnrollmentsValue && this.currentEnrollmentsValue.length > 0) {
            console.log('Pre-selecting active enrolled students:', this.currentEnrollmentsValue);
            this.tomSelect.setValue(this.currentEnrollmentsValue, true);
        }

        const optionsCount = Object.keys(this.tomSelect.options).length;
        console.log('Tom Select initialized with', optionsCount, 'options');
        console.log('Active enrolled students loaded:', this.currentEnrollmentsValue);
    }

    getSelectOptions() {
        const options = [];
        const selectElement = this.selectTarget;

        for (let i = 0; i < selectElement.options.length; i++) {
            const option = selectElement.options[i];
            if (option.value) {
                options.push({
                    value: option.value,
                    text: option.text,
                    email: option.dataset.email || '',
                });
            }
        }

        return options;
    }

    async handleSelectionChange(selectedValues) {
        console.log('Selection changed:', selectedValues);

        // Convert to array if it's a string
        const selectedArray = Array.isArray(selectedValues)
            ? selectedValues
            : (selectedValues ? [selectedValues] : []);

        // Find students that were unselected (were enrolled, now not selected)
        const unselectedStudents = this.currentEnrollmentsValue.filter(
            id => !selectedArray.includes(id)
        );

        // Find students that were newly selected (not enrolled, now selected)
        const newlySelectedStudents = selectedArray.filter(
            id => !this.currentEnrollmentsValue.includes(id)
        );

        console.log('Newly selected:', newlySelectedStudents);
        console.log('Unselected (to deactivate):', unselectedStudents);

        // Auto-deactivate unselected students
        if (unselectedStudents.length > 0) {
            await this.deactivateStudents(unselectedStudents);
        }

        // Update current enrollments
        this.currentEnrollmentsValue = selectedArray;
    }

    async deactivateStudents(studentIds) {
        console.log('Auto-deactivating students:', studentIds);

        try {
            const response = await fetch(this.deactivateUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    courseId: this.courseIdValue,
                    studentIds: studentIds
                })
            });

            if (response.ok) {
                const data = await response.json();
                console.log('Deactivated successfully:', data);

                // Show a subtle notification
                this.showNotification('Students deactivated from course', 'info');
            } else {
                console.error('Failed to deactivate students');
                this.showNotification('Failed to deactivate students', 'error');
            }
        } catch (error) {
            console.error('Error deactivating students:', error);
        }
    }

    async submitEnrollment(event) {
        console.log('🚀 Submit enrollment clicked!', event);
        event.preventDefault();

        const selectedStudents = this.tomSelect.getValue();
        console.log('Selected students for enrollment:', selectedStudents);

        if (!Array.isArray(selectedStudents) || selectedStudents.length === 0) {
            this.showNotification('Please select at least one student', 'warning');
            return;
        }

        // Determine what changed
        const initiallyEnrolled = this.hasCurrentEnrollmentsValue ? this.currentEnrollmentsValue : [];
        const newStudents = selectedStudents.filter(id => !initiallyEnrolled.includes(id));
        const removedStudents = initiallyEnrolled.filter(id => !selectedStudents.includes(id));

        console.log('Initially enrolled (active):', initiallyEnrolled);
        console.log('Currently selected:', selectedStudents);
        console.log('New students to enroll:', newStudents);
        console.log('Students to deactivate:', removedStudents);

        // If no changes, just close
        if (newStudents.length === 0 && removedStudents.length === 0) {
            console.log('ℹ️ No changes detected');
            this.showNotification('No changes to enrollment', 'info');
            return;
        }

        console.log(`📝 Processing: +${newStudents.length} enrollments, -${removedStudents.length} deactivations`);
        this.submitButtonTarget.disabled = true;
        this.submitButtonTarget.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Saving...';

        try {
            let enrolledCount = 0;
            let deactivatedCount = 0;

            // Step 1: Deactivate removed students
            if (removedStudents.length > 0) {
                console.log('🔴 Deactivating removed students:', removedStudents);
                const deactivateResponse = await fetch(this.deactivateUrlValue, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        courseId: this.courseIdValue,
                        studentIds: removedStudents
                    })
                });

                if (deactivateResponse.ok) {
                    const data = await deactivateResponse.json();
                    deactivatedCount = data.deactivated || 0;
                    console.log('✅ Deactivated:', deactivatedCount, 'students');
                } else {
                    console.error('❌ Deactivation failed');
                }
            }

            // Step 2: Enroll new students
            if (newStudents.length > 0) {
                console.log('🟢 Enrolling new students:', newStudents);
                const enrollResponse = await fetch(this.enrollUrlValue, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        courseId: this.courseIdValue,
                        studentIds: newStudents,
                        active: true
                    })
                });

                if (enrollResponse.ok) {
                    const data = await enrollResponse.json();
                    enrolledCount = data.enrolled || 0;
                    console.log('✅ Enrolled:', enrolledCount, 'students');
                } else {
                    const data = await enrollResponse.json();
                    console.error('❌ Enrollment failed:', data);
                    this.showNotification(data.error || 'Failed to enroll students', 'error');
                    this.submitButtonTarget.disabled = false;
                    this.submitButtonTarget.innerHTML = '<i class="bi bi-person-plus me-2"></i>Save Changes';
                    return;
                }
            }

            // Update current enrollments to the new selection
            this.currentEnrollmentsValue = selectedStudents;

            // Show success message
            const messages = [];
            if (enrolledCount > 0) messages.push(`✅ Enrolled ${enrolledCount} student(s)`);
            if (deactivatedCount > 0) messages.push(`🔴 Deactivated ${deactivatedCount} student(s)`);

            const message = messages.length > 0
                ? messages.join(', ')
                : 'Enrollment updated successfully';

            this.showNotification(message, 'success');

            setTimeout(() => {
                if (typeof Turbo !== 'undefined') {
                    Turbo.cache.clear();
                    Turbo.visit(window.location, { action: 'replace' });
                } else {
                    window.location.reload();
                }
            }, 1500);

        } catch (error) {
            console.error('💥 Error processing enrollments:', error);
            this.showNotification('An error occurred while processing enrollments', 'error');
            this.submitButtonTarget.disabled = false;
            this.submitButtonTarget.innerHTML = '<i class="bi bi-person-plus me-2"></i>Save Changes';
        }
    }

    showNotification(message, type = 'info') {
        // Create a toast notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }
}
