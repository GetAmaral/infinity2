import { Controller } from '@hotwired/stimulus';

/**
 * TreeFlow Canvas Controller - Phase 3: Visual Connections
 *
 * Provides:
 * - View toggle (List ↔ Canvas)
 * - Draggable step nodes
 * - Pan canvas (drag background)
 * - Zoom canvas (mouse wheel)
 * - Auto-save node positions
 * - Output/Input connection points
 * - Visual SVG connections with color coding
 * - Hover tooltips on connections
 */
export default class extends Controller {
    static targets = [
        'canvas',
        'canvasContainer',
        'listContainer',
        'listViewBtn',
        'canvasViewBtn'
    ];

    static values = {
        treeflowId: String,
        steps: Array,
        canvasState: Object
    };

    connect() {
        // Initialize canvas state from saved state or defaults
        const savedState = this.canvasStateValue || {};
        this.scale = savedState.scale || 1;
        this.offsetX = savedState.offsetX || 0;
        this.offsetY = savedState.offsetY || 0;
        this.isPanning = false;
        this.panStartX = 0;
        this.panStartY = 0;
        this.nodes = new Map(); // stepId -> nodeElement
        this.outputPoints = new Map(); // outputId -> {element, step, output}
        this.inputPoints = new Map(); // inputId -> {element, step, input}
        this.connections = []; // Array of connection data

        // Connection drag state
        this.isDraggingConnection = false;
        this.dragSourceOutput = null;
        this.dragSourceInput = null;
        this.ghostLine = null;

        // Selection state
        this.selectedConnection = null;

        // Loading state
        this.isLoading = false;

        // Bind methods
        this.handleWheel = this.handleWheel.bind(this);
        this.handleMouseDown = this.handleMouseDown.bind(this);
        this.handleMouseMove = this.handleMouseMove.bind(this);
        this.handleMouseUp = this.handleMouseUp.bind(this);
        this.handleConnectionDragMove = this.handleConnectionDragMove.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);
    }

    handleKeyDown(e) {
        // Only handle if canvas is visible
        if (this.canvasContainerTarget.style.display === 'none') return;

        // Delete key - delete selected connection
        if (e.key === 'Delete' && this.selectedConnection) {
            e.preventDefault();
            this.deleteConnection(this.selectedConnection);
            this.selectedConnection = null;
        }

        // Escape key - close context menu or deselect
        if (e.key === 'Escape') {
            e.preventDefault();
            this.hideConnectionContextMenu();
            this.deselectConnection();
        }
    }

    deselectConnection() {
        if (this.selectedConnection) {
            // Remove highlight from previously selected connection
            const path = this.svgLayer.querySelector(`[data-connection-id="${this.selectedConnection.id}"]`);
            if (path) {
                path.classList.remove('selected');
            }
            this.selectedConnection = null;
        }
        // Also close context menu if open
        this.hideConnectionContextMenu();
    }

    showListView() {
        this.listContainerTarget.style.display = 'block';
        this.canvasContainerTarget.style.display = 'none';

        // Update button states
        this.listViewBtnTarget.classList.add('active');
        this.canvasViewBtnTarget.classList.remove('active');
    }

    showCanvasView() {
        this.listContainerTarget.style.display = 'none';
        this.canvasContainerTarget.style.display = 'block';

        // Update button states
        this.listViewBtnTarget.classList.remove('active');
        this.canvasViewBtnTarget.classList.add('active');

        // Initialize canvas if not already done
        if (this.nodes.size === 0) {
            this.initializeCanvas();
        }

        // Listen for modal close events to refresh canvas
        this.setupModalRefresh();
    }

    setupModalRefresh() {
        // Listen for Turbo Frame updates (when modals close and update content)
        document.addEventListener('turbo:frame-load', (event) => {
            // If canvas is visible, refresh it when modals close
            if (this.canvasContainerTarget.style.display !== 'none') {
                // Reload the page to get updated step data
                // In a more sophisticated implementation, we could fetch just the steps data
                setTimeout(() => {
                    window.location.reload();
                }, 300);
            }
        });
    }

    async initializeCanvas() {

        // Clear canvas
        this.canvasTarget.innerHTML = '';

        // Create SVG layer for connections (bottom layer)
        this.svgLayer = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.svgLayer.id = 'connections-svg';
        this.svgLayer.style.position = 'absolute';
        this.svgLayer.style.top = '0';
        this.svgLayer.style.left = '0';
        this.svgLayer.style.width = '100%';
        this.svgLayer.style.height = '100%';
        this.svgLayer.style.pointerEvents = 'none'; // SVG doesn't block clicks
        this.svgLayer.style.transformOrigin = '0 0';
        this.svgLayer.style.zIndex = '1'; // Below nodes
        this.svgLayer.style.overflow = 'visible'; // Ensure SVG content isn't clipped
        this.canvasTarget.appendChild(this.svgLayer);

        // Create transform container for nodes (top layer)
        const container = document.createElement('div');
        container.id = 'canvas-transform-container';
        container.style.position = 'absolute';
        container.style.width = '100%';
        container.style.height = '100%';
        container.style.transformOrigin = '0 0';
        container.style.zIndex = '2'; // Above SVG
        container.style.background = 'transparent'; // Explicitly transparent
        container.style.overflow = 'visible'; // Don't clip child nodes
        this.canvasTarget.appendChild(container);
        this.transformContainer = container;

        // Render all steps
        this.renderSteps();

        // Load and render connections
        await this.loadConnections();

        // Defer connection rendering to next frame to ensure DOM is fully laid out
        requestAnimationFrame(() => {
            this.renderConnections();
            // Apply saved canvas state transform (skip saving on init)
            this.updateTransform(true);
        });

        // Add canvas event listeners
        this.canvasTarget.addEventListener('wheel', this.handleWheel, { passive: false });
        this.canvasTarget.addEventListener('mousedown', this.handleMouseDown);
        document.addEventListener('mousemove', this.handleMouseMove);
        document.addEventListener('mouseup', this.handleMouseUp);
        document.addEventListener('keydown', this.handleKeyDown);

        // Add touch event listeners for connection dragging
        document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        document.addEventListener('touchend', this.handleTouchEnd.bind(this));

        // Add touch event listeners for mobile
        this.setupTouchSupport();
    }

    setupTouchSupport() {
        let lastTouchDistance = 0;
        let touchStartX = 0;
        let touchStartY = 0;

        this.canvasTarget.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                // Two finger pinch for zoom
                lastTouchDistance = this.getTouchDistance(e.touches[0], e.touches[1]);
            } else if (e.touches.length === 1) {
                // Single finger pan
                const touch = e.touches[0];
                touchStartX = touch.clientX - this.offsetX;
                touchStartY = touch.clientY - this.offsetY;
            }
        }, { passive: true });

        this.canvasTarget.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2) {
                // Two finger pinch zoom
                e.preventDefault();
                const currentDistance = this.getTouchDistance(e.touches[0], e.touches[1]);
                const delta = currentDistance / lastTouchDistance;
                const newScale = Math.max(0.1, Math.min(3, this.scale * delta));
                this.scale = newScale;
                lastTouchDistance = currentDistance;
                this.updateTransform();
            } else if (e.touches.length === 1) {
                // Single finger pan
                e.preventDefault();
                const touch = e.touches[0];
                this.offsetX = touch.clientX - touchStartX;
                this.offsetY = touch.clientY - touchStartY;
                this.updateTransform();
            }
        }, { passive: false });
    }

    getTouchDistance(touch1, touch2) {
        const dx = touch1.clientX - touch2.clientX;
        const dy = touch1.clientY - touch2.clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }

    renderSteps() {
        // Parse steps if they're a string
        let steps = this.stepsValue;
        if (typeof steps === 'string') {
            try {
                steps = JSON.parse(steps);
            } catch (e) {
                console.error('Failed to parse steps:', e);
                return;
            }
        }

        steps.forEach((step, index) => {
            this.renderStep(step, index);
        });
    }

    renderStep(step, index) {
        const node = document.createElement('div');
        node.className = 'treeflow-node';
        node.dataset.stepId = step.id;

        // Calculate smart position
        let x = step.positionX;
        let y = step.positionY;

        if (x === null || y === null) {
            // Smart positioning: place new steps next to previous step
            if (index === 0) {
                x = 100;
                y = 100;
            } else {
                x = 100 + (index * 300);
                y = 100;
            }
        }

        node.style.left = x + 'px';
        node.style.top = y + 'px';

        // Build questions HTML
        let questionsHtml = '';
        const hasQuestions = step.questions && step.questions.length > 0;
        const questionsList = hasQuestions
            ? step.questions.map(q =>
                `<div class="question-item editable-item" data-item-type="question" data-item-id="${q.id}">
                    <i class="bi bi-patch-question-fill"></i>
                    <span>${this.escapeHtml(q.questionText || q.text || 'Question')}</span>
                </div>`
            ).join('')
            : '<div class="empty-list">No questions</div>';

        questionsHtml = `
            <div class="treeflow-questions">
                <div class="section-label section-label-add" data-section-type="question" data-step-id="${step.id}">Questions</div>
                <div class="questions-list">${questionsList}</div>
            </div>
        `;

        // Build node HTML
        node.innerHTML = `
            <div class="treeflow-node-header">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                     style="width: 30px; height: 30px; background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="bi bi-signpost text-white"></i>
                </div>
                <div class="treeflow-node-title">${this.escapeHtml(step.name)}</div>
            </div>
            <div class="treeflow-node-badges">
                ${step.first ? '<span class="badge bg-success">First</span>' : ''}
                ${step.questions && step.questions.length > 0 ? `<span class="badge bg-info">${step.questions.length} Q</span>` : ''}
            </div>
            ${questionsHtml}
            <div class="treeflow-node-body">
                <div class="treeflow-inputs">
                    <div class="section-label section-label-add" data-section-type="input" data-step-id="${step.id}">Inputs</div>
                    <div class="inputs-list"></div>
                </div>
                <div class="io-separator"></div>
                <div class="treeflow-outputs">
                    <div class="section-label section-label-add" data-section-type="output" data-step-id="${step.id}">Outputs</div>
                    <div class="outputs-list"></div>
                </div>
            </div>
        `;

        // Add drag functionality
        this.makeDraggable(node, step);

        // Add double-click to open edit modal
        node.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            this.openStepEditModal(step);
        });

        // Store reference
        this.nodes.set(step.id, node);

        // Add to canvas
        this.transformContainer.appendChild(node);

        // Add connection points
        this.addConnectionPoints(node, step);
    }

    openStepEditModal(step) {
        const editUrl = `/treeflow/${this.treeflowIdValue}/step/${step.id}/edit`;
        this.openModal(editUrl);
    }

    addConnectionPoints(node, step) {
        const inputsList = node.querySelector('.inputs-list');
        const outputsList = node.querySelector('.outputs-list');

        // Add input items
        if (step.inputs && step.inputs.length > 0) {
            step.inputs.forEach((input) => {
                const inputItem = document.createElement('div');
                inputItem.className = 'io-item input-item editable-item';
                inputItem.dataset.itemType = 'input';
                inputItem.dataset.itemId = input.id;

                // Connection circle
                const circle = document.createElement('div');
                circle.className = 'connection-point input-point';
                circle.dataset.inputId = input.id;
                circle.dataset.stepId = step.id;
                circle.dataset.inputType = input.type;
                circle.title = `${input.name} (${input.type})`;

                // Color code by input type
                if (input.type === 'fully_completed') {
                    circle.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                    circle.style.borderColor = '#065f46';
                } else if (input.type === 'not_completed_after_attempts') {
                    circle.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                    circle.style.borderColor = '#991b1b';
                } else {
                    circle.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)';
                    circle.style.borderColor = '#1e40af';
                }

                // Make input point draggable for creating connections
                this.makeInputDraggable(circle, step, input);

                // Label
                const label = document.createElement('span');
                label.className = 'io-label';
                label.textContent = input.name;

                inputItem.appendChild(circle);
                inputItem.appendChild(label);
                inputsList.appendChild(inputItem);

                this.inputPoints.set(input.id, { element: circle, step, input });
            });
        } else {
            inputsList.innerHTML = '<div class="empty-list">No inputs</div>';
        }

        // Add output items
        if (step.outputs && step.outputs.length > 0) {
            step.outputs.forEach((output) => {
                const outputItem = document.createElement('div');
                outputItem.className = 'io-item output-item editable-item';
                outputItem.dataset.itemType = 'output';
                outputItem.dataset.itemId = output.id;

                // Label
                const label = document.createElement('span');
                label.className = 'io-label';
                label.textContent = output.name;

                // Connection circle
                const circle = document.createElement('div');
                circle.className = 'connection-point output-point';
                circle.dataset.outputId = output.id;
                circle.dataset.stepId = step.id;
                circle.title = output.name;

                // Make output point draggable for creating connections
                this.makeOutputDraggable(circle, step, output);

                outputItem.appendChild(label);
                outputItem.appendChild(circle);
                outputsList.appendChild(outputItem);

                this.outputPoints.set(output.id, { element: circle, step, output });
            });
        } else {
            outputsList.innerHTML = '<div class="empty-list">No outputs</div>';
        }

        // Add click handlers for editable items
        this.addEditableItemHandlers(node, step);

        // Add click handlers for section labels (to add new items)
        this.addSectionLabelHandlers(node, step);
    }

    addEditableItemHandlers(node, step) {
        const editableItems = node.querySelectorAll('.editable-item');

        editableItems.forEach((item) => {
            item.addEventListener('click', async (e) => {
                // Don't open modal if clicking on connection circle
                if (e.target.classList.contains('connection-point')) {
                    return;
                }

                e.stopPropagation();
                e.preventDefault();

                const itemType = item.dataset.itemType;
                const itemId = item.dataset.itemId;

                if (!itemId) return;

                // Get treeflow ID and step ID
                const treeflowId = this.treeflowIdValue;
                const stepId = step.id;

                // Construct edit URL based on item type
                let editUrl;
                if (itemType === 'question') {
                    editUrl = `/treeflow/${treeflowId}/step/${stepId}/question/${itemId}/edit`;
                } else if (itemType === 'input') {
                    editUrl = `/treeflow/${treeflowId}/step/${stepId}/input/${itemId}/edit`;
                } else if (itemType === 'output') {
                    editUrl = `/treeflow/${treeflowId}/step/${stepId}/output/${itemId}/edit`;
                }

                if (!editUrl) return;

                // Open modal directly (same logic as modal_opener_controller)
                await this.openModal(editUrl);
            });
        });
    }

    addSectionLabelHandlers(node, step) {
        const sectionLabels = node.querySelectorAll('.section-label-add');

        sectionLabels.forEach((label) => {
            label.addEventListener('click', async (e) => {
                e.stopPropagation();
                e.preventDefault();

                const sectionType = label.dataset.sectionType;
                const stepId = label.dataset.stepId;

                if (!sectionType || !stepId) return;

                // Get treeflow ID
                const treeflowId = this.treeflowIdValue;

                // Construct new URL based on section type
                let newUrl;
                if (sectionType === 'question') {
                    newUrl = `/treeflow/${treeflowId}/step/${stepId}/question/new`;
                } else if (sectionType === 'input') {
                    newUrl = `/treeflow/${treeflowId}/step/${stepId}/input/new`;
                } else if (sectionType === 'output') {
                    newUrl = `/treeflow/${treeflowId}/step/${stepId}/output/new`;
                }

                if (!newUrl) return;

                // Open modal
                await this.openModal(newUrl);
            });
        });
    }

    async openModal(url) {
        try {
            // Fetch the modal content
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load modal');
            }

            const html = await response.text();

            // Parse HTML and modify it BEFORE inserting into DOM
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Remove all Stimulus controllers from overlay and form
            const overlay = doc.querySelector('.modal-fullscreen-overlay');
            if (overlay) {
                overlay.removeAttribute('data-controller');
                overlay.removeAttribute('data-action');
            }

            const form = doc.querySelector('form');
            if (form) {
                // Store original action
                const originalAction = form.getAttribute('action');
                form.dataset.originalAction = originalAction;

                // CRITICAL: Disable Turbo Drive to prevent interception
                form.setAttribute('data-turbo', 'false');

                // Remove form action to prevent default submission
                form.removeAttribute('action');

                // Remove Stimulus controllers
                form.removeAttribute('data-controller');
                form.removeAttribute('data-action');

                // Remove inline scripts
                const scripts = doc.querySelectorAll('script');
                scripts.forEach(script => script.remove());
            }

            // Insert modified HTML into global modal container
            const container = document.getElementById('global-modal-container');
            if (container) {
                container.innerHTML = doc.body.innerHTML;

                // Now set up AJAX handler
                this.setupModalFormHandler(container);
            } else {
                console.error('Global modal container not found');
            }
        } catch (error) {
            console.error('Error opening modal:', error);
            alert('Failed to open form. Please try again.');
        }
    }

    setupModalFormHandler(container) {
        console.log('[CANVAS] Setting up modal form handler');
        const form = container.querySelector('form');
        if (!form) {
            console.warn('[CANVAS] No form found in modal container');
            return;
        }

        // Get the original action URL
        const actionUrl = form.dataset.originalAction;
        if (!actionUrl) {
            console.error('[CANVAS] No original action URL found');
            return;
        }

        console.log('[CANVAS] Form found, action:', actionUrl);

        // Track form changes for confirmation dialog
        let formChanged = false;
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
            input.addEventListener('input', () => {
                formChanged = true;
            });
        });

        // Add close/cancel button handlers and get closeModal function
        const closeModal = this.setupModalCloseHandlers(container, form, () => formChanged);

        // Add submit handler
        form.addEventListener('submit', async (e) => {
            console.log('[CANVAS] Form submit intercepted!');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('[CANVAS] Default prevented, handling AJAX submission');

            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');

            // Disable submit button
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            }

            try {
                const response = await fetch(actionUrl, {
                    method: form.method || 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Form submission failed');
                }

                const result = await response.json();
                console.log('[CANVAS] Server response:', result);

                if (result.success) {
                    console.log('[CANVAS] Save successful, closing modal and refreshing canvas');

                    // Mark form as saved (no confirmation needed)
                    formChanged = false;

                    // Close modal using the proper close function (cleans up handlers)
                    closeModal();

                    // Refresh the canvas to show updated data
                    await this.refreshCanvas();
                    console.log('[CANVAS] Canvas refreshed successfully');
                } else {
                    // Show validation errors
                    if (result.html) {
                        container.innerHTML = result.html;
                        this.setupModalFormHandler(container);
                    } else if (result.error) {
                        alert(result.error);
                    }
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                alert('Failed to save. Please try again.');
            } finally {
                // Re-enable submit button
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save';
                }
            }
        }, true); // Use capture phase to run before other handlers
    }

    setupModalCloseHandlers(container, form, isFormChanged) {
        const overlay = container.querySelector('.modal-fullscreen-overlay');
        const closeButton = container.querySelector('.modal-close-btn');
        const cancelButton = container.querySelector('.btn-modal-secondary');
        const footer = container.querySelector('.modal-footer-bar');

        // Store ESC handler reference and original footer HTML
        let escHandler = null;
        let originalFooterHTML = footer ? footer.innerHTML : '';

        const forceClose = () => {
            console.log('[CANVAS] Closing modal');

            // Remove ESC handler if exists
            if (escHandler) {
                document.removeEventListener('keydown', escHandler);
            }

            // Remove modal
            if (overlay) {
                overlay.remove();
            } else {
                container.innerHTML = '';
            }
        };

        const showInlineConfirmation = () => {
            if (!footer) return;

            // Replace with confirmation buttons
            footer.innerHTML = `
                <div class="w-100">
                    <div class="alert alert-warning d-flex align-items-center mb-3" style="background: rgba(251, 146, 60, 0.15); border: 1px solid rgba(251, 146, 60, 0.4); border-radius: 10px; padding: 0.875rem 1rem;">
                        <i class="bi bi-exclamation-triangle me-2" style="font-size: 1.25rem; color: #f97316; flex-shrink: 0;"></i>
                        <span style="color: #1a1a1a; font-weight: 600; line-height: 1.5;">You have unsaved changes. Are you sure you want to discard them?</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-modal-secondary flex-fill" data-action-cancel-close>
                            <i class="bi bi-arrow-left me-2"></i>
                            Continue Editing
                        </button>
                        <button type="button" class="btn-modal-danger flex-fill" data-action-confirm-close>
                            <i class="bi bi-trash me-2"></i>
                            Discard Changes
                        </button>
                    </div>
                </div>
            `;

            // Add danger button style if not exists
            if (!document.getElementById('modal-danger-style')) {
                const style = document.createElement('style');
                style.id = 'modal-danger-style';
                style.textContent = `
                    .btn-modal-danger {
                        padding: 0.75rem 1.75rem;
                        border-radius: 10px;
                        font-weight: 600;
                        font-size: 0.9375rem;
                        border: none;
                        cursor: pointer;
                        transition: all 0.2s;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        background: linear-gradient(135deg, #ef4444, #dc2626);
                        color: white;
                        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
                    }

                    .btn-modal-danger:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
                    }

                    .btn-modal-danger:active {
                        transform: translateY(0);
                    }
                `;
                document.head.appendChild(style);
            }

            // Attach event listeners to new buttons
            const cancelBtn = footer.querySelector('[data-action-cancel-close]');
            const confirmBtn = footer.querySelector('[data-action-confirm-close]');

            if (cancelBtn) {
                cancelBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    // Restore original footer
                    if (footer) {
                        footer.innerHTML = originalFooterHTML;
                        // Re-attach handlers
                        const newCancelButton = footer.querySelector('.btn-modal-secondary');
                        if (newCancelButton) {
                            newCancelButton.addEventListener('click', closeModal);
                        }
                    }
                });
            }

            if (confirmBtn) {
                confirmBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    forceClose();
                });
            }
        };

        const closeModal = (e) => {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            console.log('[CANVAS] Close requested, formChanged:', isFormChanged());

            // Check if form has unsaved changes
            if (isFormChanged()) {
                showInlineConfirmation();
                return; // Don't close yet
            }

            forceClose();
        };

        // Close button (X icon)
        if (closeButton) {
            closeButton.addEventListener('click', closeModal);
        }

        // Cancel button
        if (cancelButton) {
            cancelButton.addEventListener('click', closeModal);
        }

        // Backdrop click
        if (overlay) {
            overlay.addEventListener('click', (e) => {
                // Only close if clicking on overlay itself, not children
                if (e.target === overlay) {
                    closeModal(e);
                }
            });
        }

        // ESC key
        escHandler = (e) => {
            if (e.key === 'Escape') {
                closeModal(e);
            }
        };
        document.addEventListener('keydown', escHandler);

        // Return closeModal function for programmatic closing
        return closeModal;
    }

    async refreshCanvas() {
        // Reload steps data
        const response = await fetch(`/treeflow/${this.treeflowIdValue}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            // Fallback: reload page
            window.location.reload();
            return;
        }

        const data = await response.json();

        if (data.steps) {
            // Update steps data
            this.stepsValue = data.steps;

            // Clear and re-render canvas
            this.nodes.clear();
            this.inputPoints.clear();
            this.outputPoints.clear();
            this.connections = [];

            this.transformContainer.innerHTML = '';

            // Re-render steps
            this.renderSteps();

            // Reload connections
            await this.loadConnections();
            this.renderConnections();
        }
    }

    async loadConnections() {
        try {
            const response = await fetch(`/treeflow/${this.treeflowIdValue}/connections`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                console.error('Failed to load connections:', response.statusText);
                this.connections = [];
                return;
            }

            const data = await response.json();
            this.connections = data.connections || [];
        } catch (error) {
            console.error('Error loading connections:', error);
            this.connections = [];
        }
    }

    renderConnections() {
        // Clear existing connections
        this.svgLayer.innerHTML = '';

        if (this.connections.length === 0) {
            return;
        }

        this.connections.forEach(connection => {
            this.renderConnection(connection);
        });
    }

    renderConnection(connection) {
        const sourcePoint = this.outputPoints.get(connection.sourceOutput.id);
        const targetPoint = this.inputPoints.get(connection.targetInput.id);

        if (!sourcePoint || !targetPoint) {
            console.warn('Missing connection points for connection:', connection);
            return;
        }

        // Get positions
        const sourcePos = this.getConnectionPointPosition(sourcePoint.element, sourcePoint.step);
        const targetPos = this.getConnectionPointPosition(targetPoint.element, targetPoint.step);

        // Create SVG path
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

        // Calculate control points for cubic bezier curve
        const dx = targetPos.x - sourcePos.x;
        const controlPointOffset = Math.abs(dx) / 2;

        const pathData = `M ${sourcePos.x} ${sourcePos.y}
                          C ${sourcePos.x + controlPointOffset} ${sourcePos.y},
                            ${targetPos.x - controlPointOffset} ${targetPos.y},
                            ${targetPos.x} ${targetPos.y}`;

        path.setAttribute('d', pathData);
        path.setAttribute('class', 'connection-line');
        path.setAttribute('data-connection-id', connection.id);

        // Color based on input type
        const inputType = targetPoint.input.type;
        if (inputType === 'fully_completed') {
            path.style.stroke = '#10b981';
        } else if (inputType === 'not_completed_after_attempts') {
            path.style.stroke = '#ef4444';
        } else {
            path.style.stroke = '#3b82f6';
        }

        // Add hover effects
        path.style.pointerEvents = 'stroke';
        path.addEventListener('mouseenter', (e) => {
            path.style.strokeWidth = '5';
            this.showConnectionTooltip(e, connection, targetPoint.input);
        });
        path.addEventListener('mouseleave', () => {
            path.style.strokeWidth = '3';
            this.hideConnectionTooltip();
        });

        // Add click to select
        path.addEventListener('click', (e) => {
            e.stopPropagation();

            // Deselect previous
            this.deselectConnection();

            // Select this connection
            this.selectedConnection = connection;
            path.classList.add('selected');
        });

        // Add right-click context menu
        path.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            e.stopPropagation();

            // Select this connection
            this.deselectConnection();
            this.selectedConnection = connection;
            path.classList.add('selected');

            // Show context menu
            this.showConnectionContextMenu(e, connection);
        });

        this.svgLayer.appendChild(path);
    }

    getConnectionPointPosition(pointElement, step) {
        const node = this.nodes.get(step.id);
        if (!node) return { x: 0, y: 0 };

        // Get node position (already in untransformed coordinate space)
        const nodeX = parseInt(node.style.left) || 0;
        const nodeY = parseInt(node.style.top) || 0;

        // Walk up the DOM tree to find position relative to node
        let offsetX = 0;
        let offsetY = 0;
        let element = pointElement;

        while (element && element !== node) {
            offsetX += element.offsetLeft || 0;
            offsetY += element.offsetTop || 0;
            element = element.offsetParent;

            // Stop if we've reached the node or gone too far
            if (element === node) break;
        }

        // Add half the circle size to get center
        const centerX = nodeX + offsetX + (pointElement.offsetWidth / 2);
        const centerY = nodeY + offsetY + (pointElement.offsetHeight / 2);

        return { x: centerX, y: centerY };
    }

    showConnectionTooltip(event, connection, input) {
        // Remove existing tooltip
        this.hideConnectionTooltip();

        const tooltip = document.createElement('div');
        tooltip.id = 'connection-tooltip';
        tooltip.className = 'connection-tooltip';
        tooltip.innerHTML = `
            <div class="tooltip-header">${this.escapeHtml(connection.sourceOutput.stepName)} → ${this.escapeHtml(connection.targetInput.stepName)}</div>
            <div class="tooltip-body">
                <div><strong>Output:</strong> ${this.escapeHtml(connection.sourceOutput.name)}</div>
                <div><strong>Input:</strong> ${this.escapeHtml(connection.targetInput.name)}</div>
                <div><strong>Type:</strong> <span class="badge bg-${this.getTypeBadgeColor(input.type)}">${input.type}</span></div>
            </div>
        `;

        tooltip.style.position = 'fixed';
        tooltip.style.left = event.clientX + 10 + 'px';
        tooltip.style.top = event.clientY + 10 + 'px';

        document.body.appendChild(tooltip);
    }

    hideConnectionTooltip() {
        const tooltip = document.getElementById('connection-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    showConnectionContextMenu(event, connection) {
        // Remove existing context menu
        this.hideConnectionContextMenu();

        const menu = document.createElement('div');
        menu.id = 'connection-context-menu';
        menu.className = 'connection-context-menu';
        menu.innerHTML = `
            <button class="context-menu-item delete-btn">
                <i class="bi bi-trash"></i>
                Delete Connection
            </button>
        `;

        menu.style.position = 'fixed';
        menu.style.left = event.clientX + 'px';
        menu.style.top = event.clientY + 'px';

        // Add delete button handler
        const deleteBtn = menu.querySelector('.delete-btn');
        deleteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.deleteConnection(connection);
            this.hideConnectionContextMenu();
        });

        document.body.appendChild(menu);

        // Close menu on click outside
        const closeMenu = (e) => {
            if (!menu.contains(e.target)) {
                this.hideConnectionContextMenu();
                document.removeEventListener('click', closeMenu);
            }
        };
        setTimeout(() => {
            document.addEventListener('click', closeMenu);
        }, 0);
    }

    hideConnectionContextMenu() {
        const menu = document.getElementById('connection-context-menu');
        if (menu) {
            menu.remove();
        }
    }

    getTypeBadgeColor(type) {
        if (type === 'fully_completed') return 'success';
        if (type === 'not_completed_after_attempts') return 'danger';
        return 'primary';
    }

    makeOutputDraggable(outputPoint, step, output) {
        // Mouse drag
        outputPoint.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();

            // Start connection drag
            this.isDraggingConnection = true;
            this.dragSourceOutput = { element: outputPoint, step, output };

            // Create ghost line
            this.createGhostLine();

            // Add cursor style
            document.body.style.cursor = 'crosshair';
        });

        // Touch drag
        outputPoint.addEventListener('touchstart', (e) => {
            e.preventDefault();
            e.stopPropagation();

            // Start connection drag
            this.isDraggingConnection = true;
            this.dragSourceOutput = { element: outputPoint, step, output };

            // Create ghost line
            this.createGhostLine();
        }, { passive: false });
    }

    makeInputDraggable(inputPoint, step, input) {
        // Mouse drag
        inputPoint.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();

            // Start connection drag from input
            this.isDraggingConnection = true;
            this.dragSourceInput = { element: inputPoint, step, input };

            // Create ghost line
            this.createGhostLine();

            // Add cursor style
            document.body.style.cursor = 'crosshair';
        });

        // Touch drag
        inputPoint.addEventListener('touchstart', (e) => {
            e.preventDefault();
            e.stopPropagation();

            // Start connection drag from input
            this.isDraggingConnection = true;
            this.dragSourceInput = { element: inputPoint, step, input };

            // Create ghost line
            this.createGhostLine();
        }, { passive: false });
    }

    createGhostLine() {
        // Create temporary SVG line
        this.ghostLine = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        this.ghostLine.setAttribute('class', 'ghost-connection-line');
        this.ghostLine.style.stroke = '#8b5cf6';
        this.ghostLine.style.strokeWidth = '3';
        this.ghostLine.style.strokeDasharray = '5,5';
        this.ghostLine.style.fill = 'none';
        this.ghostLine.style.pointerEvents = 'none';
        this.svgLayer.appendChild(this.ghostLine);
    }

    handleConnectionDragMove(e) {
        if (!this.isDraggingConnection || !this.ghostLine) return;

        // Determine source (output or input)
        const dragSource = this.dragSourceOutput || this.dragSourceInput;
        if (!dragSource) return;

        // Get source position
        const sourcePos = this.getConnectionPointPosition(
            dragSource.element,
            dragSource.step
        );

        // Get mouse position in canvas coordinates
        const rect = this.canvasTarget.getBoundingClientRect();
        const mouseX = (e.clientX - rect.left - this.offsetX) / this.scale;
        const mouseY = (e.clientY - rect.top - this.offsetY) / this.scale;

        // Draw ghost line
        const dx = mouseX - sourcePos.x;
        const controlPointOffset = Math.abs(dx) / 2;

        const pathData = `M ${sourcePos.x} ${sourcePos.y}
                          C ${sourcePos.x + controlPointOffset} ${sourcePos.y},
                            ${mouseX - controlPointOffset} ${mouseY},
                            ${mouseX} ${mouseY}`;

        this.ghostLine.setAttribute('d', pathData);

        // Highlight valid drop targets
        this.highlightDropTargets(e);
    }

    highlightDropTargets(e) {
        const targetElement = document.elementFromPoint(e.clientX, e.clientY);

        // Remove previous highlights
        document.querySelectorAll('.input-point.highlight, .output-point.highlight, .treeflow-node.highlight-drop').forEach(el => {
            el.classList.remove('highlight', 'highlight-drop');
        });

        // Dragging from output → highlight inputs
        if (this.dragSourceOutput) {
            if (targetElement && targetElement.classList.contains('input-point')) {
                targetElement.classList.add('highlight');
            } else {
                // Check if over a node (for auto-input creation)
                const node = targetElement.closest('.treeflow-node');
                if (node) {
                    const stepId = node.dataset.stepId;
                    // Check if this step has no inputs
                    const stepsArray = typeof this.stepsValue === 'string'
                        ? JSON.parse(this.stepsValue)
                        : this.stepsValue;
                    const step = stepsArray.find(s => s.id === stepId);

                    if (step && (!step.inputs || step.inputs.length === 0)) {
                        node.classList.add('highlight-drop');
                    }
                }
            }
        }
        // Dragging from input → highlight outputs
        else if (this.dragSourceInput) {
            if (targetElement && targetElement.classList.contains('output-point')) {
                targetElement.classList.add('highlight');
            }
        }
    }

    async handleConnectionDrop(e) {
        if (!this.isDraggingConnection) return;

        const targetElement = document.elementFromPoint(e.clientX, e.clientY);

        // Dragging from output to input (normal direction)
        if (this.dragSourceOutput) {
            // Check if dropping on an input point
            if (targetElement && targetElement.classList.contains('input-point')) {
                const inputId = targetElement.dataset.inputId;
                const targetInput = this.inputPoints.get(inputId);

                if (targetInput) {
                    await this.createConnection(this.dragSourceOutput, targetInput);
                }
            } else {
                // Check if dropping on a node (for auto-input creation)
                const node = targetElement.closest('.treeflow-node');
                if (node) {
                    const stepId = node.dataset.stepId;
                    await this.handleDropOnStep(stepId);
                }
            }
        }
        // Dragging from input to output (reverse direction)
        else if (this.dragSourceInput) {
            // Check if dropping on an output point
            if (targetElement && targetElement.classList.contains('output-point')) {
                const outputId = targetElement.dataset.outputId;
                const targetOutput = this.outputPoints.get(outputId);

                if (targetOutput) {
                    // Create connection with reversed parameters (output → input)
                    await this.createConnection(targetOutput, this.dragSourceInput);
                }
            }
        }

        // Clean up
        this.cleanupConnectionDrag();
    }

    async handleDropOnStep(stepId) {
        const stepsArray = typeof this.stepsValue === 'string'
            ? JSON.parse(this.stepsValue)
            : this.stepsValue;
        const targetStep = stepsArray.find(s => s.id === stepId);

        if (!targetStep) return;

        // Check if step has inputs
        if (targetStep.inputs && targetStep.inputs.length > 0) {
            // Step has inputs - user should drop on specific input point
            this.showError('Please drop on a specific input point');
            return;
        }

        // Step has no inputs - auto-create one
        this.showLoading();

        try {
            const response = await fetch(
                `/treeflow/${this.treeflowIdValue}/step/${stepId}/input/auto`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        outputName: this.dragSourceOutput.output.name,
                        sourceStepName: this.dragSourceOutput.step.name
                    })
                }
            );

            const data = await response.json();

            if (!data.success) {
                this.hideLoading();
                this.showError(data.error || 'Failed to create input');
                return;
            }


            // Update step's inputs array in memory
            if (!targetStep.inputs) {
                targetStep.inputs = [];
            }
            targetStep.inputs.push(data.input);

            // Find the step node element
            const stepNode = document.querySelector(`.treeflow-node[data-step-id="${stepId}"]`);

            if (stepNode) {
                // Create new input point on canvas
                const inputPoint = document.createElement('div');
                inputPoint.className = 'input-point';
                inputPoint.dataset.inputId = data.input.id;
                inputPoint.dataset.stepId = stepId;
                inputPoint.title = `Input: ${data.input.name} (${data.input.type})`;

                // Style by type
                const typeColors = {
                    'fully_completed': 'linear-gradient(135deg, #10b981, #059669)',
                    'partial': 'linear-gradient(135deg, #3b82f6, #2563eb)',
                    'error': 'linear-gradient(135deg, #ef4444, #dc2626)',
                    'any': 'linear-gradient(135deg, #8b5cf6, #7c3aed)'
                };
                inputPoint.style.background = typeColors[data.input.type] || typeColors['any'];

                // Position on left side
                const inputContainer = stepNode.querySelector('.input-points-container');
                if (inputContainer) {
                    inputContainer.appendChild(inputPoint);
                } else {
                    // Create container if it doesn't exist
                    const container = document.createElement('div');
                    container.className = 'input-points-container';
                    container.style.cssText = 'position: absolute; left: -8px; top: 50%; transform: translateY(-50%); display: flex; flex-direction: column; gap: 8px;';
                    container.appendChild(inputPoint);
                    stepNode.appendChild(container);
                }

                // Store in inputPoints map
                const newInputData = {
                    element: inputPoint,
                    step: targetStep,
                    input: data.input
                };
                this.inputPoints.set(data.input.id, newInputData);

                // Now create the connection with the new input
                await this.createConnection(this.dragSourceOutput, newInputData);
            }

            this.hideLoading();

        } catch (error) {
            this.hideLoading();
            console.error('Error auto-creating input:', error);
            this.showError('Network error creating input');
        }
    }

    async createConnection(sourceOutput, targetInput) {
        // Validate
        const validation = this.validateConnection(sourceOutput, targetInput);
        if (!validation.valid) {
            this.showError(validation.error);
            return;
        }


        try {
            const response = await fetch(`/treeflow/${this.treeflowIdValue}/connection`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    outputId: sourceOutput.output.id,
                    inputId: targetInput.input.id
                })
            });

            const data = await response.json();

            if (!data.success) {
                this.showError(data.error || 'Failed to create connection');
                return;
            }

            // Add connection to array
            this.connections.push(data.connection);

            // Re-render connections
            this.renderConnections();

        } catch (error) {
            console.error('Error creating connection:', error);
            this.showError('Network error creating connection');
        }
    }

    async deleteConnection(connection) {

        try {
            const response = await fetch(
                `/treeflow/${this.treeflowIdValue}/connection/${connection.id}`,
                {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const data = await response.json();

            if (!data.success) {
                this.showError(data.error || 'Failed to delete connection');
                return;
            }

            // Remove connection from array
            const index = this.connections.findIndex(c => c.id === connection.id);
            if (index > -1) {
                this.connections.splice(index, 1);
            }

            // Re-render connections
            this.renderConnections();

        } catch (error) {
            console.error('Error deleting connection:', error);
            this.showError('Network error deleting connection');
        }
    }

    validateConnection(sourceOutput, targetInput) {
        // Rule 1: No self-loops
        if (sourceOutput.step.id === targetInput.step.id) {
            return {
                valid: false,
                error: 'Cannot connect step to itself'
            };
        }

        // Rule 2: Check if output already has a connection
        const existingConnection = this.connections.find(
            conn => conn.sourceOutput.id === sourceOutput.output.id
        );

        if (existingConnection) {
            return {
                valid: false,
                error: 'Output already has a connection. Delete existing connection first.'
            };
        }

        // Rule 3: Check for duplicate (same output → same input)
        const duplicate = this.connections.find(
            conn => conn.sourceOutput.id === sourceOutput.output.id &&
                    conn.targetInput.id === targetInput.input.id
        );

        if (duplicate) {
            return {
                valid: false,
                error: 'Connection already exists between this output and input'
            };
        }

        return { valid: true };
    }

    showError(message) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'connection-error-toast';
        toast.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            ${this.escapeHtml(message)}
        `;

        document.body.appendChild(toast);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    cleanupConnectionDrag() {
        this.isDraggingConnection = false;
        this.dragSourceOutput = null;
        this.dragSourceInput = null;

        if (this.ghostLine) {
            this.ghostLine.remove();
            this.ghostLine = null;
        }

        document.body.style.cursor = '';

        // Remove highlights
        document.querySelectorAll('.input-point.highlight, .output-point.highlight').forEach(el => {
            el.classList.remove('highlight');
        });
    }

    makeDraggable(node, step) {
        let isDragging = false;
        let startX, startY, initialLeft, initialTop;

        node.addEventListener('mousedown', (e) => {
            // Don't drag if clicking on connection points
            if (e.target.classList.contains('connection-point')) {
                return;
            }

            // Only start drag on the node itself, not on child elements like buttons
            if (e.target !== node && !e.target.classList.contains('treeflow-node-title') &&
                !e.target.classList.contains('treeflow-node-header')) {
                return;
            }

            e.preventDefault(); // Prevent any default browser behavior
            e.stopPropagation(); // Prevent canvas pan

            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialLeft = parseInt(node.style.left) || 0;
            initialTop = parseInt(node.style.top) || 0;

            node.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            e.preventDefault(); // Prevent any default behavior during drag

            const dx = (e.clientX - startX) / this.scale;
            const dy = (e.clientY - startY) / this.scale;

            node.style.left = (initialLeft + dx) + 'px';
            node.style.top = (initialTop + dy) + 'px';

            // Update connections while dragging
            this.renderConnections();
        });

        document.addEventListener('mouseup', (e) => {
            if (!isDragging) return;

            e.preventDefault(); // Prevent any default behavior

            isDragging = false;
            node.style.cursor = 'move';

            // Save position to backend
            const x = parseInt(node.style.left);
            const y = parseInt(node.style.top);
            this.saveStepPosition(step.id, x, y);
        });
    }

    handleWheel(e) {
        e.preventDefault();

        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        const newScale = Math.max(0.1, Math.min(3, this.scale * delta));

        // Zoom towards mouse position
        const rect = this.canvasTarget.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        // Calculate new offset to zoom towards mouse
        this.offsetX = mouseX - (mouseX - this.offsetX) * (newScale / this.scale);
        this.offsetY = mouseY - (mouseY - this.offsetY) * (newScale / this.scale);

        this.scale = newScale;
        this.updateTransform();
    }

    handleMouseDown(e) {
        // Only start panning if clicking on canvas background
        if (e.target === this.canvasTarget || e.target === this.transformContainer || e.target === this.svgLayer) {
            this.isPanning = true;
            this.panStartX = e.clientX - this.offsetX;
            this.panStartY = e.clientY - this.offsetY;
            this.canvasTarget.style.cursor = 'grabbing';
        }
    }

    handleMouseMove(e) {
        // Handle connection dragging first
        if (this.isDraggingConnection) {
            this.handleConnectionDragMove(e);
            return;
        }

        // Handle canvas panning
        if (!this.isPanning) return;

        this.offsetX = e.clientX - this.panStartX;
        this.offsetY = e.clientY - this.panStartY;
        this.updateTransform();
    }

    handleMouseUp(e) {
        // Handle connection drop
        if (this.isDraggingConnection) {
            this.handleConnectionDrop(e);
            return;
        }

        // Handle canvas panning
        if (this.isPanning) {
            this.isPanning = false;
            this.canvasTarget.style.cursor = 'default';
        }
    }

    handleTouchMove(e) {
        // Handle connection dragging
        if (this.isDraggingConnection) {
            e.preventDefault(); // Prevent scrolling
            // Convert touch event to have clientX/clientY
            const touch = e.touches[0];
            const syntheticEvent = {
                clientX: touch.clientX,
                clientY: touch.clientY
            };
            this.handleConnectionDragMove(syntheticEvent);
            return;
        }
    }

    handleTouchEnd(e) {
        // Handle connection drop
        if (this.isDraggingConnection) {
            e.preventDefault();
            // Use changedTouches for touchend (touches array is empty at touchend)
            const touch = e.changedTouches[0];
            const syntheticEvent = {
                clientX: touch.clientX,
                clientY: touch.clientY
            };
            this.handleConnectionDrop(syntheticEvent);
            return;
        }
    }

    updateTransform(skipSave = false) {
        this.transformContainer.style.transform =
            `translate(${this.offsetX}px, ${this.offsetY}px) scale(${this.scale})`;
        this.svgLayer.style.transform =
            `translate(${this.offsetX}px, ${this.offsetY}px) scale(${this.scale})`;

        // Re-render connections after transform
        this.renderConnections();

        // Debounce canvas state saving (save after 500ms of no changes)
        // Skip saving on initial load
        if (!skipSave) {
            if (this.saveCanvasStateTimeout) {
                clearTimeout(this.saveCanvasStateTimeout);
            }
            this.saveCanvasStateTimeout = setTimeout(() => {
                this.saveCanvasState();
            }, 500);
        }
    }

    async saveStepPosition(stepId, x, y) {
        try {

            const url = `/treeflow/${this.treeflowIdValue}/step/${stepId}/position`;
            const fetchOptions = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ x, y })
            };


            const response = await fetch(url, fetchOptions);

            if (!response.ok) {
                console.error('HTTP error saving position:', response.status, response.statusText);
                return;
            }

            const data = await response.json();

            if (!data.success) {
                console.error('Failed to save position:', data.error);
            } else {
            }
        } catch (error) {
            console.error('Error saving position:', error);
        }
    }

    async saveCanvasState() {
        try {
            const url = `/treeflow/${this.treeflowIdValue}/canvas-state`;
            const fetchOptions = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    scale: this.scale,
                    offsetX: this.offsetX,
                    offsetY: this.offsetY
                })
            };

            const response = await fetch(url, fetchOptions);

            if (!response.ok) {
                console.error('HTTP error saving canvas state:', response.status, response.statusText);
                return;
            }

            const data = await response.json();

            if (!data.success) {
                console.error('Failed to save canvas state:', data.error);
            }
        } catch (error) {
            console.error('Error saving canvas state:', error);
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    zoomIn() {
        const newScale = Math.min(3, this.scale * 1.2);
        this.setZoom(newScale);
    }

    zoomOut() {
        const newScale = Math.max(0.1, this.scale / 1.2);
        this.setZoom(newScale);
    }

    setZoom(newScale) {
        // Zoom towards center of canvas
        const rect = this.canvasTarget.getBoundingClientRect();
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        this.offsetX = centerX - (centerX - this.offsetX) * (newScale / this.scale);
        this.offsetY = centerY - (centerY - this.offsetY) * (newScale / this.scale);

        this.scale = newScale;
        this.updateTransform();
    }

    fitToScreen() {
        // Calculate bounding box of all nodes
        let minX = Infinity, minY = Infinity;
        let maxX = -Infinity, maxY = -Infinity;

        this.nodes.forEach((node) => {
            const x = parseInt(node.style.left) || 0;
            const y = parseInt(node.style.top) || 0;
            const width = node.offsetWidth || 220;
            const height = node.offsetHeight || 120;

            minX = Math.min(minX, x);
            minY = Math.min(minY, y);
            maxX = Math.max(maxX, x + width);
            maxY = Math.max(maxY, y + height);
        });

        if (this.nodes.size === 0) return;

        // Add padding
        const padding = 50;
        minX -= padding;
        minY -= padding;
        maxX += padding;
        maxY += padding;

        const contentWidth = maxX - minX;
        const contentHeight = maxY - minY;

        const canvasWidth = this.canvasTarget.offsetWidth;
        const canvasHeight = this.canvasTarget.offsetHeight;

        // Calculate scale to fit
        const scaleX = canvasWidth / contentWidth;
        const scaleY = canvasHeight / contentHeight;
        const newScale = Math.min(Math.min(scaleX, scaleY), 1); // Don't zoom in beyond 1x

        // Center content
        this.scale = newScale;
        this.offsetX = (canvasWidth - contentWidth * newScale) / 2 - minX * newScale;
        this.offsetY = (canvasHeight - contentHeight * newScale) / 2 - minY * newScale;

        this.updateTransform();
    }

    autoLayout() {
        this.showLoading();

        // Simple hierarchical left-to-right layout
        const steps = Array.from(this.nodes.keys()).map(id => {
            let step = null;
            const stepsArray = typeof this.stepsValue === 'string'
                ? JSON.parse(this.stepsValue)
                : this.stepsValue;
            step = stepsArray.find(s => s.id === id);
            return { id, step };
        }).filter(item => item.step);

        // Level assignment (simple: first step = level 0, connected steps increment)
        const levels = new Map();
        const visited = new Set();

        // Find first step
        const firstStep = steps.find(item => item.step.first);
        if (firstStep) {
            levels.set(firstStep.id, 0);
            visited.add(firstStep.id);
        }

        // Assign levels based on connections
        let changed = true;
        let iteration = 0;
        while (changed && iteration < 10) {
            changed = false;
            iteration++;

            this.connections.forEach(conn => {
                const sourceStepId = conn.sourceOutput.stepId;
                const targetStepId = conn.targetInput.stepId;

                const sourceLevel = levels.get(sourceStepId) ?? 0;
                const targetLevel = levels.get(targetStepId);

                if (targetLevel === undefined || targetLevel <= sourceLevel) {
                    levels.set(targetStepId, sourceLevel + 1);
                    changed = true;
                }
            });
        }

        // Position nodes
        const horizontalSpacing = 350;
        const verticalSpacing = 150;
        const startX = 100;
        const startY = 100;

        const nodesByLevel = new Map();

        steps.forEach(({id, step}) => {
            const level = levels.get(id) ?? 0;
            if (!nodesByLevel.has(level)) {
                nodesByLevel.set(level, []);
            }
            nodesByLevel.get(level).push({id, step});
        });

        // Position each level
        Array.from(nodesByLevel.keys()).sort((a, b) => a - b).forEach(level => {
            const nodesInLevel = nodesByLevel.get(level);
            const levelHeight = nodesInLevel.length * verticalSpacing;
            const levelStartY = startY + (verticalSpacing * 3 - levelHeight) / 2;

            nodesInLevel.forEach((item, index) => {
                const x = startX + level * horizontalSpacing;
                const y = levelStartY + index * verticalSpacing;

                const node = this.nodes.get(item.id);
                if (node) {
                    node.style.left = x + 'px';
                    node.style.top = y + 'px';

                    // Save position to backend
                    this.saveStepPosition(item.id, x, y);
                }
            });
        });

        // Re-render connections
        this.renderConnections();

        // Fit to screen
        setTimeout(() => {
            this.fitToScreen();
            this.hideLoading();
        }, 300);
    }

    showLoading() {
        if (this.isLoading) return;
        this.isLoading = true;

        const overlay = document.createElement('div');
        overlay.className = 'canvas-loading';
        overlay.innerHTML = '<div class="spinner"></div>';
        overlay.id = 'canvas-loading-overlay';

        this.canvasContainerTarget.appendChild(overlay);
    }

    hideLoading() {
        this.isLoading = false;
        const overlay = document.getElementById('canvas-loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    disconnect() {
        // Clean up event listeners
        if (this.canvasTarget) {
            this.canvasTarget.removeEventListener('wheel', this.handleWheel);
            this.canvasTarget.removeEventListener('mousedown', this.handleMouseDown);
        }
        document.removeEventListener('mousemove', this.handleMouseMove);
        document.removeEventListener('mouseup', this.handleMouseUp);
        document.removeEventListener('keydown', this.handleKeyDown);

        // Clean up tooltip
        this.hideConnectionTooltip();
    }
}

