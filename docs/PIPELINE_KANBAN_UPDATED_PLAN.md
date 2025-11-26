# World-Class AI-Powered Pipeline Kanban Board
## Updated Implementation Plan - Luminai CRM

**Version:** 2.0 UPDATED
**Date:** 2025-01-19
**Based on:** Research of Pipedrive, HubSpot, Salesforce, Monday.com, and 2025 UI/UX trends

---

## Executive Summary

This updated plan delivers a **world-class, visual-first pipeline kanban board** that combines the best UI/UX patterns from industry leaders with cutting-edge AI capabilities.

### Design Philosophy (Based on 2025 Research)

**Visual-First Approach (Pipedrive-inspired):**
- Clean, uncluttered interface with focus on deal cards
- Horizontal stage flow (left-to-right progression)
- Color-coded visual indicators for instant recognition
- Minimal clicks, maximum insight

**Intelligent Automation (Salesforce Einstein + HubSpot):**
- Real-time AI health scoring and predictions
- Automated deal prioritization
- Smart next-action recommendations
- Predictive revenue forecasting

**Modern Interaction Patterns (Monday.com-inspired):**
- Smooth drag-and-drop with microinteractions
- Live updates without page refresh
- Contextual card editing
- Mobile-responsive touch gestures

---

## Phase 3: Frontend Kanban Board (UPDATED)

### Modern UI Architecture

```
Pipeline Board Layout:
┌─────────────────────────────────────────────────────────────────┐
│  PIPELINE HEADER                                                 │
│  [Pipeline Name] [Filters ▼] [Views ▼] [AI Insights] [+ Deal]  │
│  Total: $2.5M | Forecast: $1.2M | 45 deals | Avg: 28 days      │
├─────────────────────────────────────────────────────────────────┤
│  STAGE COLUMNS (Horizontal Scroll)                              │
│  ┌──────────┬──────────┬──────────┬──────────┬──────────┐      │
│  │ QUALIFY  │ PROPOSE  │ NEGOTIATE│  CLOSE   │   WON    │      │
│  │ 12 deals │  8 deals │  5 deals │  3 deals │ 17 deals │      │
│  │ $450K    │  $380K   │  $520K   │  $280K   │  $870K   │      │
│  │ 💹 Trend │ ⚠️ Risk  │ 🔥 Hot   │ ⏰ Aging │ ✅ Won   │      │
│  │          │          │          │          │          │      │
│  │ [Card]   │ [Card]   │ [Card]   │ [Card]   │ [Card]   │      │
│  │ [Card]   │ [Card]   │ [Card]   │ [Card]   │ [Card]   │      │
│  │ [Card]   │ [Card]   │ [Card]   │          │ [Card]   │      │
│  │ [Card]   │ [Card]   │          │          │ [Card]   │      │
│  │ [Card]   │          │          │          │ ...      │      │
│  │ ...      │          │          │          │          │      │
│  └──────────┴──────────┴──────────┴──────────┴──────────┘      │
└─────────────────────────────────────────────────────────────────┘
```

### Deal Card Design (Visual Hierarchy)

```
┌─────────────────────────────────────────────────┐
│ [🔥 85] Acme Corp - Enterprise License          │  ← AI Score Badge + Title
│ $45,000 USD                                     │  ← Amount (Large, Bold)
│                                                 │
│ [👤 Jane Smith] [📅 Jan 25] [⏱️ 12d]          │  ← Owner, Due Date, Days
│                                                 │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │  ← Progress Bar (AI Win %)
│ 72% Win Probability                             │  ← Probability Label
│                                                 │
│ ⚠️ No contact in 8 days                        │  ← Top Risk (Red)
│ ✅ Decision maker engaged                       │  ← Top Strength (Green)
│                                                 │
│ [💼 50 emp] [🏢 Tech] [#Priority]              │  ← Company Info + Tags
│                                                 │
│ 🎯 Next: Send proposal by EOD                  │  ← AI Recommendation
└─────────────────────────────────────────────────┘
```

---

## Modern Stimulus Controllers

### 1. `pipeline_board_controller.js` (UPDATED)

**Key Features Based on 2025 Research:**
- Idle → Hover → Grab → Move → Drop state machine
- Ghost card during drag (semi-transparent)
- Snap-to-grid positioning
- Smooth CSS transitions (300ms ease-out)
- Optimistic UI updates
- Undo/redo capability
- Real-time collaboration indicators

```javascript
import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs'; // Modern drag-drop library

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
        this.initializeSortable();
        this.loadKanbanData();
        this.startAutoRefresh();
        this.initializeWebSocket(); // Real-time updates
        this.trackViewportVisibility(); // Performance optimization
    }

    initializeSortable() {
        // Initialize Sortable.js for each column
        this.columnTargets.forEach(column => {
            const cardsContainer = column.querySelector('[data-cards]');

            Sortable.create(cardsContainer, {
                group: 'deals', // Allow inter-column movement
                animation: 200,
                easing: 'cubic-bezier(0.4, 0.0, 0.2, 1)',
                ghostClass: 'deal-card--ghost',
                chosenClass: 'deal-card--chosen',
                dragClass: 'deal-card--dragging',

                // Enhanced drag feedback
                onChoose: (evt) => this.onDragStart(evt),
                onStart: (evt) => this.showDropZones(evt),
                onEnd: (evt) => this.hideDropZones(evt),
                onAdd: (evt) => this.onDrop(evt),
                onUpdate: (evt) => this.onReorder(evt),

                // Smooth animations
                forceFallback: false,
                fallbackTolerance: 3,

                // Performance
                delay: 100,
                delayOnTouchOnly: true,
                touchStartThreshold: 3,
            });
        });
    }

    onDragStart(evt) {
        const card = evt.item;

        // Visual feedback
        card.style.opacity = '0.5';
        card.classList.add('is-dragging');

        // Store original position for undo
        this.originalPosition = {
            columnId: card.closest('[data-stage-id]').dataset.stageId,
            index: evt.oldIndex
        };

        // Haptic feedback on mobile
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }

        // Track analytics
        this.trackEvent('deal_drag_start', {
            deal_id: card.dataset.dealId,
            from_stage: this.originalPosition.columnId
        });
    }

    showDropZones(evt) {
        // Highlight valid drop zones
        this.columnTargets.forEach(col => {
            col.classList.add('drop-zone--active');

            // Show stage info tooltip
            this.showStageTooltip(col);
        });
    }

    async onDrop(evt) {
        const card = evt.item;
        const dealId = card.dataset.dealId;
        const newStageId = evt.to.closest('[data-stage-id]').dataset.stageId;
        const oldStageId = evt.from.closest('[data-stage-id]').dataset.stageId;

        // Restore visual state
        card.style.opacity = '1';
        card.classList.remove('is-dragging');

        // Optimistic UI - card already moved by Sortable

        try {
            // Persist to backend
            const response = await fetch(`/api/deal/${dealId}/move`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    stageId: newStageId,
                    previousStageId: oldStageId,
                    timestamp: new Date().toISOString()
                })
            });

            if (!response.ok) {
                throw new Error('Failed to move deal');
            }

            const data = await response.json();

            // Success feedback
            this.showToast('Deal moved successfully', 'success');
            this.playSuccessAnimation(card);

            // Update metrics
            await this.refreshMetrics();

            // Trigger AI re-analysis badge
            this.showAiAnalyzingBadge(card);

            // Track analytics
            this.trackEvent('deal_moved', {
                deal_id: dealId,
                from_stage: oldStageId,
                to_stage: newStageId,
                duration: Date.now() - this.dragStartTime
            });

        } catch (error) {
            console.error('Error moving deal:', error);

            // Revert optimistic update
            this.revertCardMove(card, evt.from);

            // Error feedback
            this.showToast(error.message, 'error');
            this.playErrorAnimation(card);
        }
    }

    async refreshMetrics() {
        // Fetch updated stage metrics
        const response = await fetch(`/api/pipeline/view/${this.pipelineIdValue}/metrics`);
        const metrics = await response.json();

        // Animate metric changes
        this.updateMetricsWithAnimation(metrics);
    }

    updateMetricsWithAnimation(metrics) {
        this.columnTargets.forEach((column, index) => {
            const stageMetric = metrics.stages[index];
            const countEl = column.querySelector('[data-count]');
            const valueEl = column.querySelector('[data-value]');

            // Count up animation
            this.animateNumber(countEl, stageMetric.count);
            this.animateNumber(valueEl, stageMetric.value, true); // Currency

            // Trend indicator
            this.updateTrendIndicator(column, stageMetric.trend);
        });
    }

    animateNumber(element, targetValue, isCurrency = false) {
        const currentValue = parseFloat(element.textContent.replace(/[^0-9.-]+/g, ''));
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
        // Subtle bounce effect
        card.style.animation = 'bounce 0.5s ease-out';
        setTimeout(() => {
            card.style.animation = '';
        }, 500);
    }

    // Real-time collaboration
    initializeWebSocket() {
        if (!this.enableCollaborationValue) return;

        // Connect to WebSocket for real-time updates
        this.ws = new WebSocket(`wss://${location.host}/pipeline/${this.pipelineIdValue}`);

        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);

            switch (data.type) {
                case 'deal_moved':
                    this.handleRemoteDealMove(data);
                    break;
                case 'deal_updated':
                    this.refreshDealCard(data.dealId);
                    break;
                case 'user_viewing':
                    this.showCollaboratorCursor(data);
                    break;
            }
        };
    }

    handleRemoteDealMove(data) {
        // Show notification that someone else moved a deal
        if (data.userId !== this.currentUserId) {
            this.showToast(
                `${data.userName} moved ${data.dealName}`,
                'info'
            );

            // Refresh the affected columns
            this.refreshColumn(data.fromStageId);
            this.refreshColumn(data.toStageId);
        }
    }

    // Advanced filtering
    applyFilters(event) {
        event.preventDefault();
        const formData = new FormData(this.filterTarget);
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

        // Update metrics after filtering
        this.recalculateVisibleMetrics();
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

    // AI Insights Panel
    async loadAiInsights() {
        const response = await fetch(`/api/pipeline/${this.pipelineIdValue}/ai-insights`);
        const insights = await response.json();

        this.renderAiInsights(insights);
    }

    renderAiInsights(insights) {
        const panel = this.aiInsightsTarget;

        panel.innerHTML = `
            <div class="ai-insights">
                <h3>🤖 AI Insights</h3>

                <div class="insight-card insight-card--hot">
                    <strong>🔥 Hot Deals (${insights.hotDeals.length})</strong>
                    <p>High-value deals with strong win probability</p>
                    <button data-action="click->pipeline-board#filterHot">
                        View Hot Deals →
                    </button>
                </div>

                <div class="insight-card insight-card--risk">
                    <strong>⚠️ At Risk (${insights.atRiskDeals.length})</strong>
                    <p>Deals requiring immediate attention</p>
                    <button data-action="click->pipeline-board#filterRisk">
                        View At Risk →
                    </button>
                </div>

                <div class="insight-card insight-card--forecast">
                    <strong>📊 Forecast</strong>
                    <p>Expected Revenue: ${this.formatCurrency(insights.forecast.expected)}</p>
                    <p>Confidence: ${insights.forecast.confidence}%</p>
                </div>

                <div class="insight-card insight-card--recommendations">
                    <strong>💡 Top Recommendations</strong>
                    <ul>
                        ${insights.recommendations.map(rec => `
                            <li>${rec.icon} ${rec.text}</li>
                        `).join('')}
                    </ul>
                </div>
            </div>
        `;
    }

    // Utilities
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    showToast(message, type = 'info') {
        // Modern toast notification with icon
        const icons = {
            success: '✅',
            error: '❌',
            info: 'ℹ️',
            warning: '⚠️'
        };

        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.innerHTML = `
            <span class="toast__icon">${icons[type]}</span>
            <span class="toast__message">${message}</span>
        `;

        document.body.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('toast--show');
        });

        // Auto-dismiss
        setTimeout(() => {
            toast.classList.remove('toast--show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    trackEvent(eventName, data) {
        // Analytics tracking (Plausible, Mixpanel, etc.)
        if (window.plausible) {
            window.plausible(eventName, { props: data });
        }
    }

    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
    }
}
```

---

## Modern SCSS Styles

### `pipeline.scss` (UPDATED)

**Design Principles:**
- Soft shadows for depth (not flat)
- Smooth transitions (ease-out curves)
- Color psychology (green=healthy, red=risk, orange=attention)
- Consistent spacing (8px grid system)
- High contrast for accessibility
- Dark mode support

```scss
// ============================================
// MODERN PIPELINE KANBAN BOARD STYLES
// Based on 2025 UI/UX Research
// ============================================

// Design Tokens
$spacing-unit: 8px;
$border-radius: 12px;
$border-radius-sm: 8px;
$transition-fast: 150ms ease-out;
$transition-normal: 300ms ease-out;
$transition-slow: 500ms ease-out;

// Color Palette (AI-Driven)
$color-hot: #FF6B6B;      // Hot deals (red-orange)
$color-healthy: #51CF66;  // Healthy deals (green)
$color-risk: #FAB005;     // At risk (yellow-orange)
$color-cold: #748FFC;     // Cold/inactive (blue)
$color-won: #20C997;      // Won deals (teal)
$color-lost: #ADB5BD;     // Lost deals (gray)

// Shadows (Soft, Modern)
$shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
$shadow-md: 0 4px 12px rgba(0, 0, 0, 0.10);
$shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
$shadow-lift: 0 12px 32px rgba(0, 0, 0, 0.15);

// ============================================
// PIPELINE BOARD CONTAINER
// ============================================

.pipeline-board {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 120px);
    background: #F8F9FA;
    border-radius: $border-radius;
    overflow: hidden;

    // Dark mode support
    @media (prefers-color-scheme: dark) {
        background: #1A1B1E;
        color: #E9ECEF;
    }
}

// ============================================
// PIPELINE HEADER
// ============================================

.pipeline-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: $spacing-unit * 3;
    background: white;
    border-bottom: 1px solid #E9ECEF;
    box-shadow: $shadow-sm;
    z-index: 10;

    &__title {
        font-size: 24px;
        font-weight: 700;
        color: #212529;
        margin: 0;

        display: flex;
        align-items: center;
        gap: $spacing-unit * 2;
    }

    &__metrics {
        display: flex;
        gap: $spacing-unit * 4;
        font-size: 14px;

        .metric {
            display: flex;
            flex-direction: column;

            &__label {
                color: #868E96;
                font-size: 12px;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            &__value {
                font-size: 20px;
                font-weight: 700;
                color: #212529;
                margin-top: 4px;

                &--positive {
                    color: $color-healthy;
                }

                &--negative {
                    color: $color-risk;
                }
            }
        }
    }

    &__actions {
        display: flex;
        gap: $spacing-unit * 2;
    }

    @media (prefers-color-scheme: dark) {
        background: #25262B;
        border-color: #373A40;

        &__title {
            color: #E9ECEF;
        }
    }
}

// ============================================
// STAGE COLUMNS
// ============================================

.pipeline-columns {
    display: flex;
    gap: $spacing-unit * 2;
    padding: $spacing-unit * 3;
    overflow-x: auto;
    overflow-y: hidden;
    flex: 1;

    // Smooth scrolling
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;

    // Hide scrollbar but keep functionality
    scrollbar-width: thin;
    scrollbar-color: #DEE2E6 transparent;

    &::-webkit-scrollbar {
        height: 8px;
    }

    &::-webkit-scrollbar-track {
        background: transparent;
    }

    &::-webkit-scrollbar-thumb {
        background: #DEE2E6;
        border-radius: 4px;

        &:hover {
            background: #ADB5BD;
        }
    }
}

.stage-column {
    flex: 0 0 320px;
    min-width: 320px;
    max-width: 320px;
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: $border-radius;
    box-shadow: $shadow-sm;
    transition: box-shadow $transition-normal;

    // Drag over state
    &.drop-zone--active {
        box-shadow: $shadow-lg, 0 0 0 2px $color-healthy;

        .stage-column__header {
            background: linear-gradient(135deg, rgba($color-healthy, 0.1), rgba($color-healthy, 0.05));
        }
    }

    &:hover {
        box-shadow: $shadow-md;
    }

    @media (prefers-color-scheme: dark) {
        background: #25262B;
    }
}

.stage-column__header {
    padding: $spacing-unit * 2.5;
    border-bottom: 2px solid #F1F3F5;
    transition: background $transition-normal;

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: $spacing-unit * 1.5;
    }

    &__name {
        font-size: 16px;
        font-weight: 700;
        color: #212529;
        text-transform: uppercase;
        letter-spacing: 0.5px;

        // Stage color indicator
        &::before {
            content: '';
            display: inline-block;
            width: 3px;
            height: 16px;
            margin-right: $spacing-unit;
            border-radius: 2px;
            background: currentColor;
        }
    }

    &__count {
        font-size: 14px;
        font-weight: 600;
        color: #868E96;
        padding: 4px 12px;
        background: #F1F3F5;
        border-radius: 12px;
    }

    &__metrics {
        display: flex;
        justify-content: space-between;
        font-size: 13px;

        .metric {
            display: flex;
            flex-direction: column;

            &__label {
                color: #868E96;
                font-size: 11px;
                margin-bottom: 2px;
            }

            &__value {
                font-weight: 700;
                color: #212529;
                font-size: 16px;
            }
        }
    }

    &__trend {
        margin-top: $spacing-unit;
        padding: $spacing-unit;
        background: #F8F9FA;
        border-radius: $border-radius-sm;
        font-size: 12px;
        text-align: center;

        &--up {
            color: $color-healthy;
            background: rgba($color-healthy, 0.1);
        }

        &--down {
            color: $color-risk;
            background: rgba($color-risk, 0.1);
        }
    }
}

.stage-column__cards {
    flex: 1;
    padding: $spacing-unit * 2;
    overflow-y: auto;
    min-height: 200px;

    // Custom scrollbar
    &::-webkit-scrollbar {
        width: 6px;
    }

    &::-webkit-scrollbar-track {
        background: transparent;
    }

    &::-webkit-scrollbar-thumb {
        background: #DEE2E6;
        border-radius: 3px;
    }
}

// ============================================
// DEAL CARDS (Pipedrive-inspired)
// ============================================

.deal-card {
    background: white;
    border-radius: $border-radius-sm;
    padding: $spacing-unit * 2;
    margin-bottom: $spacing-unit * 2;
    box-shadow: $shadow-sm;
    border-left: 4px solid transparent;
    cursor: grab;
    transition: all $transition-normal;
    position: relative;

    // Hover effect
    &:hover {
        box-shadow: $shadow-md;
        transform: translateY(-2px);

        .deal-card__actions {
            opacity: 1;
        }
    }

    // Active (clicked) state
    &:active {
        cursor: grabbing;
    }

    // AI Score color coding
    &--hot {
        border-left-color: $color-hot;
    }

    &--healthy {
        border-left-color: $color-healthy;
    }

    &--risk {
        border-left-color: $color-risk;
    }

    &--cold {
        border-left-color: $color-cold;
    }

    // Drag states
    &.is-dragging {
        opacity: 0.5;
        transform: rotate(3deg);
    }

    &--ghost {
        opacity: 0.3;
        background: #F8F9FA;
        border: 2px dashed #ADB5BD;
    }

    &--chosen {
        box-shadow: $shadow-lift;
    }

    @media (prefers-color-scheme: dark) {
        background: #2C2E33;
        color: #E9ECEF;
    }
}

.deal-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: $spacing-unit * 1.5;
}

.deal-card__ai-score {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-weight: 800;
    font-size: 16px;
    flex-shrink: 0;

    &--hot {
        background: linear-gradient(135deg, $color-hot, darken($color-hot, 10%));
        color: white;
        box-shadow: 0 4px 12px rgba($color-hot, 0.4);
    }

    &--healthy {
        background: linear-gradient(135deg, $color-healthy, darken($color-healthy, 10%));
        color: white;
        box-shadow: 0 4px 12px rgba($color-healthy, 0.4);
    }

    &--risk {
        background: linear-gradient(135deg, $color-risk, darken($color-risk, 10%));
        color: white;
        box-shadow: 0 4px 12px rgba($color-risk, 0.4);
    }

    &--cold {
        background: linear-gradient(135deg, $color-cold, darken($color-cold, 10%));
        color: white;
        box-shadow: 0 4px 12px rgba($color-cold, 0.4);
    }
}

.deal-card__title {
    flex: 1;
    margin-left: $spacing-unit * 1.5;

    h4 {
        font-size: 15px;
        font-weight: 700;
        color: #212529;
        margin: 0 0 4px 0;
        line-height: 1.3;

        // Truncate long titles
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .company-name {
        font-size: 13px;
        color: #868E96;
        font-weight: 500;
    }
}

.deal-card__amount {
    font-size: 20px;
    font-weight: 800;
    color: #212529;
    margin-bottom: $spacing-unit * 1.5;

    // Currency styling
    .currency {
        font-size: 14px;
        font-weight: 600;
        color: #868E96;
        margin-left: 4px;
    }
}

.deal-card__progress {
    margin-bottom: $spacing-unit * 1.5;

    &__bar {
        height: 6px;
        background: #E9ECEF;
        border-radius: 3px;
        overflow: hidden;
        position: relative;

        &__fill {
            height: 100%;
            background: linear-gradient(90deg, $color-healthy, darken($color-healthy, 10%));
            border-radius: 3px;
            transition: width $transition-slow;
            position: relative;

            // Shine effect
            &::after {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(
                    90deg,
                    transparent,
                    rgba(255, 255, 255, 0.3),
                    transparent
                );
                animation: shine 2s infinite;
            }
        }
    }

    &__label {
        display: flex;
        justify-content: space-between;
        margin-top: 4px;
        font-size: 12px;
        color: #868E96;
        font-weight: 600;
    }
}

@keyframes shine {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

.deal-card__meta {
    display: flex;
    align-items: center;
    gap: $spacing-unit * 1.5;
    margin-bottom: $spacing-unit * 1.5;
    font-size: 13px;
    color: #495057;
    flex-wrap: wrap;

    .meta-item {
        display: flex;
        align-items: center;
        gap: 4px;

        .icon {
            width: 16px;
            height: 16px;
            opacity: 0.7;
        }
    }
}

.deal-card__insights {
    margin-bottom: $spacing-unit * 1.5;

    .insight-item {
        display: flex;
        align-items: flex-start;
        gap: $spacing-unit;
        padding: $spacing-unit;
        margin-bottom: $spacing-unit;
        border-radius: $border-radius-sm;
        font-size: 13px;
        line-height: 1.4;

        &--risk {
            background: rgba($color-risk, 0.1);
            color: darken($color-risk, 20%);
            border-left: 3px solid $color-risk;
        }

        &--strength {
            background: rgba($color-healthy, 0.1);
            color: darken($color-healthy, 20%);
            border-left: 3px solid $color-healthy;
        }

        .icon {
            flex-shrink: 0;
            font-size: 16px;
        }
    }
}

.deal-card__company-info {
    display: flex;
    align-items: center;
    gap: $spacing-unit;
    margin-bottom: $spacing-unit * 1.5;
    padding: $spacing-unit;
    background: #F8F9FA;
    border-radius: $border-radius-sm;
    font-size: 12px;

    .info-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        background: white;
        border-radius: 4px;
        font-weight: 600;
        color: #495057;
    }
}

.deal-card__tags {
    display: flex;
    flex-wrap: wrap;
    gap: $spacing-unit;
    margin-bottom: $spacing-unit * 1.5;

    .tag {
        padding: 4px 10px;
        background: #E7F5FF;
        color: #1864AB;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
}

.deal-card__recommendation {
    display: flex;
    align-items: center;
    gap: $spacing-unit;
    padding: $spacing-unit * 1.5;
    background: linear-gradient(135deg, #667EEA, #764BA2);
    color: white;
    border-radius: $border-radius-sm;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);

    .icon {
        font-size: 18px;
    }
}

.deal-card__actions {
    position: absolute;
    top: $spacing-unit;
    right: $spacing-unit;
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity $transition-normal;

    button {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: none;
        background: white;
        color: #495057;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: $shadow-sm;
        transition: all $transition-fast;

        &:hover {
            background: #F8F9FA;
            transform: scale(1.1);
        }

        &:active {
            transform: scale(0.95);
        }
    }
}

// ============================================
// ANIMATIONS
// ============================================

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-10px);
    }
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

// ============================================
// TOAST NOTIFICATIONS
// ============================================

.toast {
    position: fixed;
    top: $spacing-unit * 3;
    right: $spacing-unit * 3;
    display: flex;
    align-items: center;
    gap: $spacing-unit * 1.5;
    padding: $spacing-unit * 2 $spacing-unit * 3;
    background: white;
    border-radius: $border-radius-sm;
    box-shadow: $shadow-lg;
    transform: translateX(400px);
    opacity: 0;
    transition: all $transition-normal;
    z-index: 1000;
    max-width: 400px;

    &--show {
        transform: translateX(0);
        opacity: 1;
    }

    &__icon {
        font-size: 24px;
        flex-shrink: 0;
    }

    &__message {
        flex: 1;
        font-size: 14px;
        font-weight: 600;
        color: #212529;
    }

    &--success {
        border-left: 4px solid $color-healthy;
    }

    &--error {
        border-left: 4px solid $color-hot;
    }

    &--info {
        border-left: 4px solid $color-cold;
    }

    &--warning {
        border-left: 4px solid $color-risk;
    }
}

// ============================================
// RESPONSIVE DESIGN
// ============================================

@media (max-width: 768px) {
    .pipeline-columns {
        padding: $spacing-unit * 2;
    }

    .stage-column {
        flex: 0 0 280px;
        min-width: 280px;
        max-width: 280px;
    }

    .pipeline-header {
        flex-direction: column;
        gap: $spacing-unit * 2;

        &__metrics {
            width: 100%;
            justify-content: space-between;
        }

        &__actions {
            width: 100%;
        }
    }
}

// ============================================
// AI INSIGHTS PANEL
// ============================================

.ai-insights {
    padding: $spacing-unit * 3;
    background: white;
    border-radius: $border-radius;
    box-shadow: $shadow-md;

    h3 {
        margin: 0 0 $spacing-unit * 2 0;
        font-size: 18px;
        font-weight: 700;
    }

    .insight-card {
        padding: $spacing-unit * 2;
        margin-bottom: $spacing-unit * 2;
        border-radius: $border-radius-sm;
        background: #F8F9FA;

        strong {
            display: block;
            margin-bottom: $spacing-unit;
            font-size: 15px;
        }

        p {
            margin: 0 0 $spacing-unit 0;
            font-size: 13px;
            color: #495057;
        }

        button {
            padding: $spacing-unit $spacing-unit * 2;
            background: #228BE6;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background $transition-fast;

            &:hover {
                background: darken(#228BE6, 10%);
            }
        }

        &--hot {
            background: rgba($color-hot, 0.1);
            border-left: 4px solid $color-hot;
        }

        &--risk {
            background: rgba($color-risk, 0.1);
            border-left: 4px solid $color-risk;
        }

        &--forecast {
            background: linear-gradient(135deg, rgba(#667EEA, 0.1), rgba(#764BA2, 0.1));
            border-left: 4px solid #667EEA;
        }
    }
}
```

---

## Updated Twig Templates

### `templates/pipeline/board.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ pipeline.name }} - Pipeline{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    {{ encore_entry_link_tags('pipeline') }}
{% endblock %}

{% block body %}
<div class="pipeline-board"
     data-controller="pipeline-board"
     data-pipeline-board-pipeline-id-value="{{ pipeline.id }}"
     data-pipeline-board-refresh-interval-value="30000"
     data-pipeline-board-enable-collaboration-value="true">

    {# Pipeline Header #}
    <div class="pipeline-header">
        <div class="pipeline-header__title">
            <i class="bi bi-{{ pipeline.icon }}"></i>
            <span>{{ pipeline.name }}</span>
        </div>

        <div class="pipeline-header__metrics" data-pipeline-board-target="metrics">
            <div class="metric">
                <span class="metric__label">Total Value</span>
                <span class="metric__value" data-value>
                    {{ metrics.totalValue|format_currency('USD') }}
                </span>
            </div>
            <div class="metric">
                <span class="metric__label">Forecast</span>
                <span class="metric__value metric__value--positive" data-value>
                    {{ metrics.forecast|format_currency('USD') }}
                </span>
            </div>
            <div class="metric">
                <span class="metric__label">Deals</span>
                <span class="metric__value" data-count>
                    {{ metrics.dealCount }}
                </span>
            </div>
            <div class="metric">
                <span class="metric__label">Avg Cycle</span>
                <span class="metric__value">
                    {{ metrics.avgCycle }} days
                </span>
            </div>
        </div>

        <div class="pipeline-header__actions">
            <button class="btn btn-outline-primary"
                    data-action="click->pipeline-board#toggleFilters">
                <i class="bi bi-filter"></i> Filters
            </button>
            <button class="btn btn-outline-secondary"
                    data-action="click->pipeline-board#loadAiInsights">
                <i class="bi bi-lightning"></i> AI Insights
            </button>
            <a href="{{ path('deal_new') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Deal
            </a>
        </div>
    </div>

    {# Stage Columns #}
    <div class="pipeline-columns">
        {% for stage in pipeline.stages|sort((a, b) => a.displayOrder <=> b.displayOrder) %}
            <div class="stage-column"
                 data-pipeline-board-target="column"
                 data-stage-id="{{ stage.id }}">

                <div class="stage-column__header" style="color: {{ stage.color }};">
                    <div class="stage-column__header__top">
                        <h3 class="stage-column__header__name">{{ stage.stageName }}</h3>
                        <span class="stage-column__header__count" data-count>
                            {{ stage.deals|length }}
                        </span>
                    </div>

                    <div class="stage-column__header__metrics">
                        <div class="metric">
                            <span class="metric__label">Value</span>
                            <span class="metric__value" data-value>
                                {{ stage.totalValue|format_currency('USD') }}
                            </span>
                        </div>
                        <div class="metric">
                            <span class="metric__label">Avg Days</span>
                            <span class="metric__value">
                                {{ stage.avgDays|round }}
                            </span>
                        </div>
                    </div>

                    {% if stage.trend %}
                        <div class="stage-column__header__trend stage-column__header__trend--{{ stage.trend > 0 ? 'up' : 'down' }}">
                            {{ stage.trend > 0 ? '📈' : '📉' }}
                            {{ stage.trend > 0 ? '+' : '' }}{{ stage.trend }}% this week
                        </div>
                    {% endif %}
                </div>

                <div class="stage-column__cards" data-cards>
                    {% for deal in stage.deals %}
                        {{ include('pipeline/_deal_card.html.twig', {deal: deal}) }}
                    {% endfor %}
                </div>
            </div>
        {% endfor %}
    </div>

    {# AI Insights Panel (Hidden by default) #}
    <div class="ai-insights-panel"
         data-pipeline-board-target="aiInsights"
         style="display: none;">
    </div>
</div>
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    {{ encore_entry_script_tags('pipeline') }}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
{% endblock %}
```

### `templates/pipeline/_deal_card.html.twig`

```twig
{% set aiScoreClass = deal.isHotDeal ? 'hot' : (deal.isAtRisk ? 'risk' : (deal.aiScore >= 60 ? 'healthy' : 'cold')) %}

<div class="deal-card deal-card--{{ aiScoreClass }}"
     data-pipeline-board-target="card"
     data-deal-id="{{ deal.id }}"
     data-ai-score="{{ deal.aiScore }}"
     data-amount="{{ deal.expectedAmount }}"
     data-days-stale="{{ deal.daysStale }}"
     data-owner-id="{{ deal.owner.id }}"
     data-action="click->deal-detail#open">

    {# Header: AI Score + Title #}
    <div class="deal-card__header">
        <div class="deal-card__ai-score deal-card__ai-score--{{ aiScoreClass }}">
            {% if deal.aiScore %}
                {{ deal.aiScore }}
            {% else %}
                <span style="font-size: 20px;">🤖</span>
            {% endif %}
        </div>

        <div class="deal-card__title">
            <h4>{{ deal.name }}</h4>
            <span class="company-name">
                <i class="bi bi-building"></i>
                {{ deal.company.name }}
            </span>
        </div>
    </div>

    {# Amount #}
    <div class="deal-card__amount">
        {{ deal.expectedAmount|format_currency(deal.currency ?? 'USD') }}
    </div>

    {# Win Probability Progress Bar #}
    {% if deal.winProbability %}
        <div class="deal-card__progress">
            <div class="deal-card__progress__bar">
                <div class="deal-card__progress__bar__fill"
                     style="width: {{ deal.winProbability }}%;
                            background: linear-gradient(90deg,
                                {{ deal.winProbability >= 75 ? '#51CF66' : (deal.winProbability >= 50 ? '#FAB005' : '#FF6B6B') }},
                                {{ deal.winProbability >= 75 ? '#40C057' : (deal.winProbability >= 50 ? '#F59F00' : '#FA5252') }});">
                </div>
            </div>
            <div class="deal-card__progress__label">
                <span>Win Probability</span>
                <span>{{ deal.winProbability|number_format(1) }}%</span>
            </div>
        </div>
    {% endif %}

    {# Meta Information #}
    <div class="deal-card__meta">
        <div class="meta-item">
            <i class="bi bi-person-circle icon"></i>
            {{ deal.owner.fullName }}
        </div>
        {% if deal.expectedClosureDate %}
            <div class="meta-item">
                <i class="bi bi-calendar-event icon"></i>
                {{ deal.expectedClosureDate|date('M d') }}
            </div>
        {% endif %}
        {% if deal.daysInCurrentStage %}
            <div class="meta-item">
                <i class="bi bi-clock icon"></i>
                {{ deal.daysInCurrentStage }}d
            </div>
        {% endif %}
    </div>

    {# AI Insights #}
    {% if deal.riskFactors|length > 0 or (deal.recommendedActions|length > 0 and deal.recommendedActions[0] is defined) %}
        <div class="deal-card__insights">
            {# Show top risk #}
            {% if deal.riskFactors|length > 0 and deal.riskFactors[0] is defined %}
                <div class="insight-item insight-item--risk">
                    <span class="icon">⚠️</span>
                    <span>{{ deal.riskFactors[0] }}</span>
                </div>
            {% endif %}
        </div>
    {% endif %}

    {# Company Info Badges #}
    {% if deal.company %}
        <div class="deal-card__company-info">
            {% if deal.company.companySize %}
                <span class="info-badge">
                    <i class="bi bi-people"></i>
                    {{ deal.company.companySize }}
                </span>
            {% endif %}
            {% if deal.company.industry %}
                <span class="info-badge">
                    <i class="bi bi-briefcase"></i>
                    {{ deal.company.industry }}
                </span>
            {% endif %}
        </div>
    {% endif %}

    {# Tags #}
    {% if deal.tags|length > 0 %}
        <div class="deal-card__tags">
            {% for tag in deal.tags|slice(0, 3) %}
                <span class="tag" style="background: {{ tag.color }}20; color: {{ tag.color }};">
                    {{ tag.name }}
                </span>
            {% endfor %}
        </div>
    {% endif %}

    {# AI Recommendation #}
    {% if deal.recommendedActions|length > 0 and deal.recommendedActions[0] is defined %}
        <div class="deal-card__recommendation">
            <span class="icon">🎯</span>
            <span>{{ deal.recommendedActions[0] }}</span>
        </div>
    {% endif %}

    {# Quick Actions (Show on hover) #}
    <div class="deal-card__actions">
        <button type="button"
                data-action="click->deal-detail#open"
                title="View Details">
            <i class="bi bi-eye"></i>
        </button>
        <button type="button"
                data-action="click->pipeline-board#scoreDeal"
                data-deal-id="{{ deal.id }}"
                title="Re-analyze with AI">
            <i class="bi bi-lightning"></i>
        </button>
    </div>
</div>
```

---

## Implementation Summary

### What Makes This World-Class

**1. Visual Design (Pipedrive-inspired):**
- Clean, card-first interface
- Clear visual hierarchy
- Color-coded health indicators
- Smooth animations and microinteractions

**2. AI Integration (Salesforce Einstein-level):**
- Real-time health scoring
- Predictive win probability
- Risk detection and recommendations
- Automated insights panel

**3. Modern UX (Monday.com-level):**
- Drag-and-drop with Sortable.js
- Optimistic UI updates
- Real-time collaboration (WebSocket)
- Mobile-responsive touch gestures

**4. Performance:**
- Lazy loading for large pipelines
- Virtual scrolling for 100+ deals
- Debounced filtering
- Efficient re-rendering

**5. Accessibility:**
- ARIA labels
- Keyboard navigation
- Screen reader support
- High contrast mode

### Technologies Used

- **Sortable.js**: Industry-standard drag-drop library
- **Stimulus 3.x**: Modern, lightweight JS framework
- **Bootstrap 5.3**: Responsive grid and utilities
- **SCSS**: Modern CSS with variables and mixins
- **WebSocket**: Real-time collaboration

---

## Next Steps

1. **Implement Stimulus controllers** (2-3 days)
2. **Add SCSS styles** (1-2 days)
3. **Create Twig templates** (1-2 days)
4. **Add WebSocket support** for real-time (1 day)
5. **Mobile optimization** (1 day)
6. **Testing & polish** (2-3 days)

**Total Estimated Time: 8-12 days for world-class frontend**

