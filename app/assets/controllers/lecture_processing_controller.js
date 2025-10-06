import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        courseId: String,
        lectureId: String,
        statusUrl: String
    }

    static targets = ['status', 'progressBar', 'step', 'percentage', 'watchButton']

    connect() {
        console.log('Lecture processing controller connected');
        this.pollInterval = null;
        this.startPolling();
    }

    disconnect() {
        this.stopPolling();
    }

    startPolling() {
        // Check status immediately
        this.checkStatus();

        // Then poll every 10 seconds
        this.pollInterval = setInterval(() => {
            this.checkStatus();
        }, 10000);
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    async checkStatus() {
        try {
            const response = await fetch(this.statusUrlValue);

            if (!response.ok) {
                console.error('Failed to fetch processing status');
                return;
            }

            const data = await response.json();
            this.updateUI(data);

            // Stop polling if completed or failed
            if (data.completed || data.failed) {
                this.stopPolling();

                // Reload the page after a short delay to show the final state
                if (data.completed) {
                    setTimeout(() => {
                        if (typeof Turbo !== 'undefined') {
                            Turbo.cache.clear();
                            Turbo.visit(window.location, { action: 'replace' });
                        } else {
                            window.location.reload();
                        }
                    }, 2000);
                }
            }
        } catch (error) {
            console.error('Error checking processing status:', error);
        }
    }

    updateUI(data) {
        // Update status badge
        if (this.hasStatusTarget) {
            const badge = this.statusTarget;
            badge.className = 'badge';

            if (data.completed) {
                badge.classList.add('bg-success');
                badge.innerHTML = '<i class="bi bi-check-circle me-1"></i>Completed';
            } else if (data.failed) {
                badge.classList.add('bg-danger');
                badge.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Failed';
            } else if (data.status === 'processing') {
                badge.classList.add('bg-warning', 'text-dark');
                badge.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Processing...';
            } else {
                badge.classList.add('bg-secondary');
                badge.innerHTML = '<i class="bi bi-clock-history me-1"></i>Pending';
            }
        }

        // Update progress bar
        if (this.hasProgressBarTarget) {
            this.progressBarTarget.style.width = `${data.percentage}%`;
            this.progressBarTarget.setAttribute('aria-valuenow', data.percentage);
        }

        // Update step text
        if (this.hasStepTarget && data.step) {
            this.stepTarget.textContent = data.step;
        }

        // Update percentage text
        if (this.hasPercentageTarget) {
            this.percentageTarget.textContent = `${data.percentage}%`;
        }

        // Show/hide watch button
        if (this.hasWatchButtonTarget) {
            if (data.completed) {
                this.watchButtonTarget.style.display = 'inline-block';
            } else {
                this.watchButtonTarget.style.display = 'none';
            }
        }

        // Show error if failed
        if (data.failed && data.error) {
            if (this.hasStepTarget) {
                this.stepTarget.textContent = `Error: ${data.error}`;
                this.stepTarget.classList.add('text-danger');
            }
        }
    }
}
