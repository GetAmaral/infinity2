import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        lectureId: String,
        url: String
    }

    static targets = ['toggle']

    connect() {
        console.log('[CompletionToggle] Controller connected');
        console.log('[CompletionToggle] Lecture ID:', this.lectureIdValue);
        console.log('[CompletionToggle] URL:', this.urlValue);
    }

    async toggle(event) {
        const isCompleted = event.target.checked;
        const originalState = !isCompleted;

        console.log('[CompletionToggle] Toggle changed! New state:', isCompleted);

        // Disable toggle during request
        event.target.disabled = true;

        try {
            const url = this.urlValue;
            console.log('[CompletionToggle] Sending request to:', url, 'with completed:', isCompleted);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    completed: isCompleted
                })
            });

            console.log('[CompletionToggle] Response status:', response.status);
            const data = await response.json();
            console.log('[CompletionToggle] Response data:', data);

            if (data.success) {
                console.log('[CompletionToggle] Toggle successful:', isCompleted ? 'Completed' : 'Incomplete');
                console.log('[CompletionToggle] Lecture progress:', data.completion, '% | Course progress:', data.courseProgress, '%');

                // Update lecture progress UI
                this.updateLectureProgress(data.completion, isCompleted);

                // Update course progress UI
                if (data.courseProgress !== undefined) {
                    this.updateCourseProgress(data.courseProgress, isCompleted, data.completed);
                }

                // Re-enable toggle
                event.target.disabled = false;
            } else {
                // Revert toggle state on error
                event.target.checked = originalState;
                event.target.disabled = false;
                alert('Failed to update completion status');
            }
        } catch (error) {
            console.error('[CompletionToggle] Error:', error);
            // Revert toggle state on error
            event.target.checked = originalState;
            event.target.disabled = false;
            alert('Error updating completion status');
        }
    }

    updateLectureProgress(completion, isCompleted) {
        // Update lecture progress texts
        const progressTexts = document.querySelectorAll('.lecture-progress-text');
        progressTexts.forEach(el => {
            el.textContent = `${Math.round(completion)}%`;
        });

        // Update lecture progress bar
        const progressBar = document.querySelector('.lecture-progress-bar');
        if (progressBar) {
            progressBar.style.width = `${completion}%`;
            progressBar.setAttribute('aria-valuenow', Math.round(completion));
        }

        // Update completion badge
        const completionBadge = document.querySelector('.lecture-completion-badge');
        if (completionBadge) {
            if (isCompleted) {
                completionBadge.textContent = completionBadge.textContent.includes('Concluída') ? 'Concluída' : 'Completed';
                completionBadge.classList.remove('bg-secondary', 'bg-primary');
                completionBadge.classList.add('bg-success');
            } else {
                completionBadge.textContent = completionBadge.textContent.includes('Em Progresso') ? 'Em Progresso' : 'In Progress';
                completionBadge.classList.remove('bg-secondary', 'bg-success');
                completionBadge.classList.add('bg-primary');
            }
        }
    }

    updateCourseProgress(courseProgress, isCompleted, lectureCompleted) {
        // Update course progress texts
        const courseProgressTexts = document.querySelectorAll('.course-progress-text');
        courseProgressTexts.forEach(el => {
            el.textContent = `${Math.round(courseProgress)}%`;
        });

        // Update course progress bar
        const courseProgressBar = document.querySelector('.course-progress-bar');
        if (courseProgressBar) {
            courseProgressBar.style.width = `${courseProgress}%`;
            courseProgressBar.setAttribute('aria-valuenow', Math.round(courseProgress));
        }

        // Update completed lecture count
        const courseLectureCount = document.querySelector('.course-lecture-count');
        if (courseLectureCount) {
            const currentText = courseLectureCount.textContent.trim();
            const match = currentText.match(/(\d+)\s*\/\s*(\d+)/);
            if (match) {
                let completedCount = parseInt(match[1]);
                const totalCount = parseInt(match[2]);

                // Increment or decrement based on toggle state
                if (isCompleted && lectureCompleted) {
                    completedCount = Math.min(completedCount + 1, totalCount);
                } else if (!isCompleted && !lectureCompleted) {
                    completedCount = Math.max(completedCount - 1, 0);
                }

                courseLectureCount.textContent = `${completedCount} / ${totalCount}`;
                console.log('[CompletionToggle] Updated lecture count:', courseLectureCount.textContent);
            }
        }
    }
}
