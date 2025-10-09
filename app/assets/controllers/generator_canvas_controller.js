import { Controller } from '@hotwired/stimulus';

/**
 * Generator Canvas Controller - Visual Database Designer
 *
 * Adapted from TreeFlow Canvas Controller
 * Provides:
 * - Draggable entity nodes
 * - Pan canvas (drag background)
 * - Zoom canvas (mouse wheel + buttons)
 * - Auto-save node positions and canvas state
 * - Visual SVG relationship lines
 * - Auto-layout algorithm
 * - Fullscreen support
 */
export default class extends Controller {
    static targets = [
        'canvas',
        'canvasContainer'
    ];

    static values = {
        entities: Array,
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
        this.nodes = new Map(); // entityId -> nodeElement
        this.relationships = []; // Array of relationship data

        // Loading state
        this.isLoading = false;

        // Bind methods
        this.handleWheel = this.handleWheel.bind(this);
        this.handleMouseDown = this.handleMouseDown.bind(this);
        this.handleMouseMove = this.handleMouseMove.bind(this);
        this.handleMouseUp = this.handleMouseUp.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);

        // Initialize canvas
        this.initializeCanvas();
    }

    disconnect() {
        // Cleanup event listeners
        if (this.canvasTarget) {
            this.canvasTarget.removeEventListener('wheel', this.handleWheel);
            this.canvasTarget.removeEventListener('mousedown', this.handleMouseDown);
            document.removeEventListener('mousemove', this.handleMouseMove);
            document.removeEventListener('mouseup', this.handleMouseUp);
            document.removeEventListener('keydown', this.handleKeyDown);
        }
    }

    initializeCanvas() {
        // Create transform container
        this.transformContainer = document.createElement('div');
        this.transformContainer.className = 'generator-canvas-transform';
        this.transformContainer.style.position = 'absolute';
        this.transformContainer.style.transformOrigin = '0 0';
        this.transformContainer.style.width = '100%';
        this.transformContainer.style.height = '100%';

        // Create SVG layer for connections
        this.svgLayer = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.svgLayer.id = 'connections-svg';
        this.svgLayer.style.position = 'absolute';
        this.svgLayer.style.top = '0';
        this.svgLayer.style.left = '0';
        this.svgLayer.style.width = '100%';
        this.svgLayer.style.height = '100%';
        this.svgLayer.style.pointerEvents = 'none';
        this.svgLayer.style.overflow = 'visible';
        this.svgLayer.style.zIndex = '1';
        this.svgLayer.style.transformOrigin = '0 0';

        this.canvasTarget.appendChild(this.svgLayer);
        this.canvasTarget.appendChild(this.transformContainer);

        // Set up event listeners
        this.canvasTarget.addEventListener('wheel', this.handleWheel, { passive: false });
        this.canvasTarget.addEventListener('mousedown', this.handleMouseDown);
        document.addEventListener('mousemove', this.handleMouseMove);
        document.addEventListener('mouseup', this.handleMouseUp);
        document.addEventListener('keydown', this.handleKeyDown);

        // Render entities
        this.renderEntities();

        // Apply saved transform
        this.updateTransform(true); // skipSave=true on init
    }

    renderEntities() {
        this.transformContainer.innerHTML = '';
        this.nodes.clear();

        if (!this.entitiesValue || this.entitiesValue.length === 0) {
            // Show empty state
            const emptyState = document.createElement('div');
            emptyState.className = 'canvas-empty-state';
            emptyState.innerHTML = `
                <i class="bi bi-diagram-3" style="font-size: 4rem; color: #adb5bd;"></i>
                <h3 style="color: #6c757d; margin-top: 1rem;">No Entities Yet</h3>
                <p style="color: #adb5bd;">Create your first entity to start building your data model</p>
            `;
            this.transformContainer.appendChild(emptyState);
            return;
        }

        this.entitiesValue.forEach(entity => {
            const node = this.createEntityNode(entity);
            this.transformContainer.appendChild(node);
            this.nodes.set(entity.id, node);
        });

        // Render relationships
        this.renderRelationships();
    }

    createEntityNode(entity) {
        const node = document.createElement('div');
        node.className = 'generator-entity-node';
        node.dataset.entityId = entity.id;
        node.style.left = `${entity.canvasX || 100}px`;
        node.style.top = `${entity.canvasY || 100}px`;

        // Header
        const header = document.createElement('div');
        header.className = 'entity-node-header';
        header.innerHTML = `
            <i class="${entity.icon || 'bi-table'}"></i>
            <span class="node-title">${this.escapeHtml(entity.entityName)}</span>
            <button class="entity-edit-btn" data-action="click->generator-canvas#openEditEntityModal" data-entity-id="${entity.id}">
                <i class="bi bi-pencil"></i>
            </button>
        `;

        // Badges
        const badges = document.createElement('div');
        badges.className = 'entity-node-badges';
        let badgesHtml = '';

        if (entity.apiEnabled) {
            badgesHtml += '<span class="badge bg-primary">API</span>';
        }
        if (entity.voterEnabled) {
            badgesHtml += '<span class="badge bg-success">Voter</span>';
        }
        if (entity.isGenerated) {
            badgesHtml += '<span class="badge bg-info">Generated</span>';
        }

        badges.innerHTML = badgesHtml;

        // Properties list
        const body = document.createElement('div');
        body.className = 'entity-node-body';

        const propertiesList = document.createElement('div');
        propertiesList.className = 'properties-list';

        if (entity.properties && entity.properties.length > 0) {
            entity.properties.forEach(property => {
                const propItem = document.createElement('div');
                propItem.className = 'property-item';

                let relBadge = '';
                if (property.relationshipType) {
                    relBadge = `<span class="badge badge-rel bg-secondary">${property.relationshipType}</span>`;
                }

                propItem.innerHTML = `
                    <span>${this.escapeHtml(property.propertyName)}</span>
                    <span class="type">${property.propertyType} ${relBadge}</span>
                `;
                propertiesList.appendChild(propItem);
            });
        } else {
            propertiesList.innerHTML = '<div class="empty-list">No properties</div>';
        }

        body.appendChild(propertiesList);

        // Add property button
        const addPropertyBtn = document.createElement('button');
        addPropertyBtn.className = 'btn btn-sm btn-outline-primary mt-2 w-100';
        addPropertyBtn.innerHTML = '<i class="bi bi-plus-lg"></i> Add Property';
        addPropertyBtn.dataset.action = 'click->generator-canvas#openCreatePropertyModal';
        addPropertyBtn.dataset.entityId = entity.id;
        body.appendChild(addPropertyBtn);

        // Assemble node
        node.appendChild(header);
        if (badgesHtml) {
            node.appendChild(badges);
        }
        node.appendChild(body);

        // Make draggable
        this.makeDraggable(node, entity);

        return node;
    }

    makeDraggable(node, entity) {
        const header = node.querySelector('.entity-node-header');

        let isDragging = false;
        let startX, startY, initialLeft, initialTop;

        const onMouseDown = (e) => {
            // Don't drag if clicking on edit button
            if (e.target.closest('.entity-edit-btn')) {
                return;
            }

            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialLeft = parseInt(node.style.left);
            initialTop = parseInt(node.style.top);
            node.style.cursor = 'grabbing';
            node.style.zIndex = '1000';
        };

        const onMouseMove = (e) => {
            if (!isDragging) return;

            const dx = (e.clientX - startX) / this.scale;
            const dy = (e.clientY - startY) / this.scale;

            node.style.left = (initialLeft + dx) + 'px';
            node.style.top = (initialTop + dy) + 'px';

            // Update relationships
            this.renderRelationships();
        };

        const onMouseUp = (e) => {
            if (!isDragging) return;

            isDragging = false;
            node.style.cursor = '';
            node.style.zIndex = '';

            // Save position
            const x = parseInt(node.style.left);
            const y = parseInt(node.style.top);
            this.saveEntityPosition(entity.id, x, y);
        };

        header.addEventListener('mousedown', onMouseDown);
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    }

    renderRelationships() {
        // Clear existing connections
        this.svgLayer.innerHTML = '';

        // Build relationships from properties
        this.entitiesValue.forEach(entity => {
            if (entity.properties) {
                entity.properties.forEach(property => {
                    if (property.relationshipType && property.targetEntity) {
                        // Find target entity
                        const targetEntity = this.entitiesValue.find(e => e.entityName === property.targetEntity);

                        if (targetEntity) {
                            this.drawRelationship(entity, targetEntity, property.relationshipType);
                        }
                    }
                });
            }
        });
    }

    drawRelationship(sourceEntity, targetEntity, relationshipType) {
        const sourceNode = this.nodes.get(sourceEntity.id);
        const targetNode = this.nodes.get(targetEntity.id);

        if (!sourceNode || !targetNode) return;

        const sourceRect = sourceNode.getBoundingClientRect();
        const targetRect = targetNode.getBoundingClientRect();
        const containerRect = this.canvasTarget.getBoundingClientRect();

        // Calculate positions (compensate for scale and offset)
        const sourceX = (sourceRect.right - containerRect.left - this.offsetX) / this.scale;
        const sourceY = (sourceRect.top - containerRect.top + sourceRect.height / 2 - this.offsetY) / this.scale;
        const targetX = (targetRect.left - containerRect.left - this.offsetX) / this.scale;
        const targetY = (targetRect.top - containerRect.top + targetRect.height / 2 - this.offsetY) / this.scale;

        // Create path
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

        // Calculate control points for Bezier curve
        const dx = targetX - sourceX;
        const controlPointOffset = Math.min(Math.abs(dx) / 2, 100);

        const pathData = `M ${sourceX} ${sourceY} C ${sourceX + controlPointOffset} ${sourceY}, ${targetX - controlPointOffset} ${targetY}, ${targetX} ${targetY}`;

        path.setAttribute('d', pathData);
        path.setAttribute('class', 'relationship-line');
        path.setAttribute('stroke-width', '3');
        path.setAttribute('fill', 'none');

        // Color by relationship type
        const colors = {
            'ManyToOne': '#3b82f6',
            'OneToMany': '#10b981',
            'ManyToMany': '#8b5cf6',
            'OneToOne': '#f59e0b'
        };
        path.setAttribute('stroke', colors[relationshipType] || '#6c757d');

        // Add to SVG
        this.svgLayer.appendChild(path);
    }

    // ======================================
    // PAN & ZOOM (from TreeFlow)
    // ======================================

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
        this.updateZoomDisplay();
    }

    handleMouseDown(e) {
        // Only start panning if clicking on canvas background or SVG layer
        if (e.target === this.canvasTarget || e.target === this.transformContainer || e.target === this.svgLayer) {
            this.isPanning = true;
            this.panStartX = e.clientX - this.offsetX;
            this.panStartY = e.clientY - this.offsetY;
            this.canvasTarget.style.cursor = 'grabbing';
        }
    }

    handleMouseMove(e) {
        if (!this.isPanning) return;

        this.offsetX = e.clientX - this.panStartX;
        this.offsetY = e.clientY - this.panStartY;
        this.updateTransform();
    }

    handleMouseUp(e) {
        if (this.isPanning) {
            this.isPanning = false;
            this.canvasTarget.style.cursor = 'default';
        }
    }

    handleKeyDown(e) {
        // Fullscreen toggle with F key
        if (e.key === 'f' || e.key === 'F') {
            this.toggleFullscreen();
        }
    }

    updateTransform(skipSave = false) {
        this.transformContainer.style.transform =
            `translate(${this.offsetX}px, ${this.offsetY}px) scale(${this.scale})`;
        this.svgLayer.style.transform =
            `translate(${this.offsetX}px, ${this.offsetY}px) scale(${this.scale})`;

        // Re-render relationships after transform
        this.renderRelationships();

        // Update zoom percentage display
        this.updateZoomDisplay();

        // Debounce canvas state saving
        if (!skipSave) {
            if (this.saveCanvasStateTimeout) {
                clearTimeout(this.saveCanvasStateTimeout);
            }
            this.saveCanvasStateTimeout = setTimeout(() => {
                this.saveCanvasState();
            }, 500);
        }
    }

    updateZoomDisplay() {
        const zoomPercentage = Math.round(this.scale * 100);
        const zoomDisplay = document.getElementById('zoom-percentage');
        if (zoomDisplay) {
            zoomDisplay.textContent = `${zoomPercentage}%`;
        }
    }

    // ======================================
    // ZOOM CONTROLS
    // ======================================

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
        if (this.nodes.size === 0) return;

        // Calculate bounding box of all nodes
        let minX = Infinity, minY = Infinity;
        let maxX = -Infinity, maxY = -Infinity;

        this.nodes.forEach(node => {
            const left = parseInt(node.style.left);
            const top = parseInt(node.style.top);
            const width = node.offsetWidth;
            const height = node.offsetHeight;

            minX = Math.min(minX, left);
            minY = Math.min(minY, top);
            maxX = Math.max(maxX, left + width);
            maxY = Math.max(maxY, top + height);
        });

        const contentWidth = maxX - minX;
        const contentHeight = maxY - minY;
        const containerRect = this.canvasTarget.getBoundingClientRect();

        // Calculate scale to fit (with padding)
        const padding = 50;
        const scaleX = (containerRect.width - padding * 2) / contentWidth;
        const scaleY = (containerRect.height - padding * 2) / contentHeight;
        const newScale = Math.min(scaleX, scaleY, 1); // Don't zoom in beyond 100%

        // Center content
        const contentCenterX = minX + contentWidth / 2;
        const contentCenterY = minY + contentHeight / 2;

        this.scale = newScale;
        this.offsetX = containerRect.width / 2 - contentCenterX * newScale;
        this.offsetY = containerRect.height / 2 - contentCenterY * newScale;

        this.updateTransform();
    }

    toggleFullscreen() {
        const card = this.element.closest('.luminai-card');

        if (!document.fullscreenElement) {
            card.requestFullscreen().catch(err => {
                console.error('Error attempting to enable fullscreen:', err);
            });
        } else {
            document.exitFullscreen();
        }
    }

    // ======================================
    // AUTO-LAYOUT
    // ======================================

    async autoLayout() {
        this.showLoading();

        try {
            const response = await fetch('/admin/generator/auto-layout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                // Reload page to show new positions
                window.location.reload();
            } else {
                console.error('Auto-layout failed');
                this.hideLoading();
            }
        } catch (error) {
            console.error('Error during auto-layout:', error);
            this.hideLoading();
        }
    }

    // ======================================
    // API CALLS
    // ======================================

    async saveEntityPosition(entityId, x, y) {
        try {
            const url = `/admin/generator/entity/${entityId}/position`;
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ x, y })
            });

            if (!response.ok) {
                console.error('Failed to save entity position');
            }
        } catch (error) {
            console.error('Error saving entity position:', error);
        }
    }

    async saveCanvasState() {
        try {
            const url = '/admin/generator/canvas-state';
            const response = await fetch(url, {
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
            });

            if (!response.ok) {
                console.error('Failed to save canvas state');
            }
        } catch (error) {
            console.error('Error saving canvas state:', error);
        }
    }

    // ======================================
    // MODAL ACTIONS
    // ======================================

    openCreateEntityModal(e) {
        e.preventDefault();
        window.location.href = '/admin/generator/entity/create';
    }

    openEditEntityModal(e) {
        e.preventDefault();
        const entityId = e.currentTarget.dataset.entityId;
        window.location.href = `/admin/generator/entity/${entityId}/edit`;
    }

    openCreatePropertyModal(e) {
        e.preventDefault();
        const entityId = e.currentTarget.dataset.entityId;
        window.location.href = `/admin/generator/property/create/${entityId}`;
    }

    // ======================================
    // UTILITIES
    // ======================================

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showLoading() {
        this.isLoading = true;
        const overlay = document.createElement('div');
        overlay.className = 'canvas-loading';
        overlay.innerHTML = '<div class="spinner"></div>';
        this.canvasContainerTarget.appendChild(overlay);
    }

    hideLoading() {
        this.isLoading = false;
        const overlay = this.canvasContainerTarget.querySelector('.canvas-loading');
        if (overlay) {
            overlay.remove();
        }
    }
}
