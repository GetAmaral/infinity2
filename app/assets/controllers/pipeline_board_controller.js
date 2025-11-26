import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'board',
        'column',
        'card',
        'metrics',
        'filter',
        'aiInsights'
    ];

    static values = {
        pipelineId: String,
        refreshInterval: { type: Number, default: 30000 },
        enableCollaboration: { type: Boolean, default: true }
    };

    connect() {
        console.log('🎨 Pipeline Board Connected');
        this.loadKanbanData();
        this.startAutoRefresh();
        this.initDragDrop();
    }

    initDragDrop() {
        // Simple drag-drop without external library for now
        this.cardTargets.forEach(card => {
            card.draggable = true;
            card.addEventListener('dragstart', this.handleDragStart.bind(this));
            card.addEventListener('dragend', this.handleDragEnd.bind(this));
        });

        this.columnTargets.forEach(column => {
            const cardsContainer = column.querySelector('[data-cards]');
            if (cardsContainer) {
                cardsContainer.addEventListener('dragover', this.handleDragOver.bind(this));
                cardsContainer.addEventListener('drop', this.handleDrop.bind(this));
                cardsContainer.addEventListener('dragleave', this.handleDragLeave.bind(this));
            }
        });
    }

    handleDragStart(event) {
        this.draggedCard = event.target.closest('[data-deal-id]');
        if (!this.draggedCard) return;

        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('dealId', this.draggedCard.dataset.dealId);

        this.draggedCard.style.opacity = '0.5';
        this.draggedCard.classList.add('is-dragging');

        // Store original position for rollback
        this.originalParent = this.draggedCard.parentElement;
        this.originalNextSibling = this.draggedCard.nextSibling;

        // Show drop zones
        this.columnTargets.forEach(col => {
            col.classList.add('drop-zone--active');
        });

        // Haptic feedback on mobile
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
    }

    handleDragEnd(event) {
        if (this.draggedCard) {
            this.draggedCard.style.opacity = '1';
            this.draggedCard.classList.remove('is-dragging');
        }

        // Remove drop zone highlights
        this.columnTargets.forEach(col => {
            col.classList.remove('drop-zone--active');
        });
    }

    handleDragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';

        const dropZone = event.currentTarget;
        dropZone.classList.add('drag-over');
    }

    handleDragLeave(event) {
        event.currentTarget.classList.remove('drag-over');
    }

    async handleDrop(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('drag-over');

        const dealId = event.dataTransfer.getData('dealId');
        if (!dealId || !this.draggedCard) return;

        const targetColumn = event.currentTarget.closest('[data-stage-id]');
        const newStageId = targetColumn.dataset.stageId;
        const oldStageId = this.draggedCard.closest('[data-stage-id]').dataset.stageId;

        // Don't move if same column
        if (newStageId === oldStageId) {
            return;
        }

        // Optimistic UI - move card immediately
        const oldColumn = this.draggedCard.closest('[data-stage-id]');
        const newColumn = targetColumn;

        event.currentTarget.appendChild(this.draggedCard);

        // Update empty state messages and counts
        this.updateEmptyStates(oldColumn, newColumn);
        this.updateColumnCounts(oldColumn, newColumn);

        try {
            // Persist to backend
            const response = await fetch(`/deal/${dealId}/move`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    stageId: newStageId,
                    previousStageId: oldStageId
                })
            });

            if (!response.ok) {
                throw new Error('Failed to move deal');
            }

            const data = await response.json();

            // Success feedback
            this.showToast('✅ Deal moved successfully', 'success');
            this.playSuccessAnimation(this.draggedCard);

            // Update metrics
            this.refreshMetrics();

            // Show AI analyzing badge
            this.showAiAnalyzingBadge(this.draggedCard);

        } catch (error) {
            console.error('Error moving deal:', error);

            // Revert optimistic update
            if (this.originalParent && this.draggedCard) {
                if (this.originalNextSibling) {
                    this.originalParent.insertBefore(this.draggedCard, this.originalNextSibling);
                } else {
                    this.originalParent.appendChild(this.draggedCard);
                }

                // Revert empty states and counts
                this.updateEmptyStates(newColumn, oldColumn);
                this.updateColumnCounts(newColumn, oldColumn);
            }

            // Error feedback
            this.showToast('❌ Failed to move deal: ' + error.message, 'error');
        }
    }

    async loadKanbanData() {
        try {
            const params = new URLSearchParams(this.getFilters());
            const response = await fetch(`/api/pipeline/view/${this.pipelineIdValue}?${params}`, {
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Failed to load pipeline data');
            }

            const data = await response.json();
            this.updateMetrics(data.overallMetrics);

        } catch (error) {
            console.error('Failed to load kanban data:', error);
            this.showToast('❌ Failed to load pipeline data', 'error');
        }
    }

    getFilters() {
        const filters = {};
        if (this.hasFilterTarget) {
            const filterForm = this.filterTarget.querySelector('form');
            if (filterForm) {
                const formData = new FormData(filterForm);
                for (const [key, value] of formData.entries()) {
                    if (value) filters[key] = value;
                }
            }
        }
        return filters;
    }

    async refreshMetrics() {
        try {
            const response = await fetch(`/api/pipeline/view/${this.pipelineIdValue}`, {
                credentials: 'same-origin'
            });
            const data = await response.json();
            this.updateMetrics(data.overallMetrics);
        } catch (error) {
            console.error('Failed to refresh metrics:', error);
        }
    }

    updateMetrics(metrics) {
        if (!this.hasMetricsTarget || !metrics) return;

        // Update each metric with animation
        const metricElements = this.metricsTarget.querySelectorAll('[data-value]');
        metricElements.forEach(el => {
            const metricName = el.closest('.metric')?.querySelector('.metric__label')?.textContent;
            if (metricName) {
                const newValue = this.getMetricValue(metrics, metricName);
                if (newValue !== null) {
                    this.animateNumber(el, newValue, metricName.includes('Value') || metricName.includes('Forecast'));
                }
            }
        });
    }

    getMetricValue(metrics, metricName) {
        if (metricName.includes('Total') || metricName.includes('Value')) {
            return metrics.totalPipelineValue || 0;
        } else if (metricName.includes('Forecast')) {
            return metrics.forecastedRevenue || 0;
        } else if (metricName.includes('Deals')) {
            return metrics.activeDealsCount || 0;
        }
        return null;
    }

    animateNumber(element, targetValue, isCurrency = false) {
        const currentValue = parseFloat(element.textContent.replace(/[^0-9.-]+/g, '')) || 0;
        const duration = 600;
        const steps = 30;
        const stepValue = (targetValue - currentValue) / steps;
        const stepDuration = duration / steps;

        let step = 0;
        const timer = setInterval(() => {
            step++;
            const newValue = currentValue + (stepValue * step);

            if (isCurrency) {
                element.textContent = this.formatCurrency(newValue);
            } else {
                element.textContent = Math.round(newValue);
            }

            if (step >= steps) {
                clearInterval(timer);
                element.textContent = isCurrency ?
                    this.formatCurrency(targetValue) :
                    targetValue;
            }
        }, stepDuration);
    }

    playSuccessAnimation(card) {
        if (!card) return;

        // Add bounce animation
        card.style.animation = 'bounce 0.5s ease-out';
        setTimeout(() => {
            card.style.animation = '';
        }, 500);
    }

    showAiAnalyzingBadge(card) {
        if (!card) return;

        const badge = document.createElement('div');
        badge.className = 'ai-analyzing-badge';
        badge.innerHTML = '🤖 Analyzing...';
        badge.style.cssText = `
            position: absolute;
            top: 8px;
            right: 8px;
            background: linear-gradient(135deg, #667EEA, #764BA2);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            animation: pulse 1.5s infinite;
            z-index: 10;
        `;

        card.style.position = 'relative';
        card.appendChild(badge);

        // Remove after 3 seconds
        setTimeout(() => {
            badge.remove();
        }, 3000);
    }

    applyFilters(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const filters = Object.fromEntries(formData);

        // Apply filters with smooth transition
        this.cardTargets.forEach(card => {
            const matches = this.cardMatchesFilters(card, filters);

            if (matches) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.3s ease-out';
            } else {
                card.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        });
    }

    cardMatchesFilters(card, filters) {
        // AI Score filter
        if (filters.aiScore) {
            const score = parseInt(card.dataset.aiScore);
            switch (filters.aiScore) {
                case 'hot':
                    if (score < 75) return false;
                    break;
                case 'at_risk':
                    if (score >= 50) return false;
                    break;
                case 'stale':
                    const daysStale = parseInt(card.dataset.daysStale);
                    if (daysStale <= 30) return false;
                    break;
            }
        }

        // Owner filter
        if (filters.owner && card.dataset.ownerId !== filters.owner) {
            return false;
        }

        // Amount range
        const amount = parseFloat(card.dataset.amount);
        if (filters.minAmount && amount < parseFloat(filters.minAmount)) {
            return false;
        }
        if (filters.maxAmount && amount > parseFloat(filters.maxAmount)) {
            return false;
        }

        return true;
    }

    clearFilters(event) {
        event.preventDefault();

        if (this.hasFilterTarget) {
            const form = this.filterTarget.querySelector('form');
            if (form) {
                form.reset();

                // Show all cards
                this.cardTargets.forEach(card => {
                    card.style.display = 'block';
                    card.style.animation = 'fadeIn 0.3s ease-out';
                });
            }
        }
    }

    async scoreDeal(event) {
        event.preventDefault();
        event.stopPropagation();

        const dealId = event.currentTarget.dataset.dealId ||
                      event.currentTarget.closest('[data-deal-id]')?.dataset.dealId;

        if (!dealId) return;

        const card = this.cardTargets.find(c => c.dataset.dealId === dealId);
        if (card) {
            this.showAiAnalyzingBadge(card);
        }

        try {
            const response = await fetch(`/api/pipeline/view/deal/${dealId}/score`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (!response.ok) {
                throw new Error('Failed to analyze deal');
            }

            const data = await response.json();

            this.showToast('✅ Deal analyzed successfully', 'success');

            // Refresh the card (could reload or update in place)
            setTimeout(() => {
                window.location.reload();
            }, 1500);

        } catch (error) {
            console.error('Error analyzing deal:', error);
            this.showToast('❌ Failed to analyze deal', 'error');
        }
    }

    startAutoRefresh() {
        if (this.refreshIntervalValue > 0) {
            this.refreshTimer = setInterval(() => {
                this.refreshMetrics();
            }, this.refreshIntervalValue);
        }
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            container.style.cssText = `
                position: fixed;
                top: 24px;
                right: 24px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 12px;
            `;
            document.body.appendChild(container);
        }

        // Create toast
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.style.cssText = `
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 24px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s ease-out;
            max-width: 400px;
            border-left: 4px solid ${type === 'success' ? '#51CF66' : type === 'error' ? '#FF6B6B' : '#228BE6'};
        `;
        toast.textContent = message;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
            toast.style.opacity = '1';
        });

        // Auto-dismiss
        setTimeout(() => {
            toast.style.transform = 'translateX(400px)';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    updateEmptyStates(oldColumn, newColumn) {
        // Update old column (deal was removed)
        const oldCardsContainer = oldColumn.querySelector('[data-cards]');
        const oldDealCards = oldCardsContainer.querySelectorAll('.deal-card');

        let oldEmptyMessage = oldCardsContainer.querySelector('.text-center.text-muted');

        if (oldDealCards.length === 0) {
            // Show empty message if it doesn't exist
            if (!oldEmptyMessage) {
                oldEmptyMessage = document.createElement('div');
                oldEmptyMessage.className = 'text-center text-muted py-5';
                oldEmptyMessage.innerHTML = `
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mt-2">No deals in this stage</p>
                `;
                oldCardsContainer.appendChild(oldEmptyMessage);
            }
        } else {
            // Remove empty message if deals exist
            if (oldEmptyMessage) {
                oldEmptyMessage.remove();
            }
        }

        // Update new column (deal was added)
        const newCardsContainer = newColumn.querySelector('[data-cards]');
        const newDealCards = newCardsContainer.querySelectorAll('.deal-card');

        let newEmptyMessage = newCardsContainer.querySelector('.text-center.text-muted');

        if (newDealCards.length > 0 && newEmptyMessage) {
            // Remove empty message since we now have deals
            newEmptyMessage.remove();
        }
    }

    updateColumnCounts(oldColumn, newColumn) {
        // Update old column count
        const oldCardsContainer = oldColumn.querySelector('[data-cards]');
        const oldDealCards = oldCardsContainer.querySelectorAll('.deal-card');
        const oldCountBadge = oldColumn.querySelector('[data-count]');
        if (oldCountBadge) {
            oldCountBadge.textContent = oldDealCards.length;
        }

        // Update new column count
        const newCardsContainer = newColumn.querySelector('[data-cards]');
        const newDealCards = newCardsContainer.querySelectorAll('.deal-card');
        const newCountBadge = newColumn.querySelector('[data-count]');
        if (newCountBadge) {
            newCountBadge.textContent = newDealCards.length;
        }
    }

    disconnect() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
    }
}
