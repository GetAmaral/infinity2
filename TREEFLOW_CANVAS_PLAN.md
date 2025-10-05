# 🎨 TREEFLOW CANVAS EDITOR - IMPLEMENTATION PLAN

## 📋 OVERVIEW

Transform TreeFlow from accordion-based editor to n8n-like infinite canvas workflow editor with visual drag-and-drop connections.

### **Goals:**
- ✅ Infinite canvas with pan & zoom
- ✅ Visual step nodes with drag positioning
- ✅ Drag-to-connect from outputs to inputs
- ✅ Auto-save positions & connections
- ✅ Mobile responsive canvas
- ✅ Keep existing modal editing system

### **Key Decisions:**
1. ✅ **StepConnection entity** - One Output → One Connection, One Input → Many Connections
2. ✅ **Auto-input naming** - "On {output.name}" or fallback "From {step.name}"
3. ✅ **Connection colors** - Green (FULLY_COMPLETED), Red (NOT_COMPLETED_AFTER_ATTEMPTS), Blue (ANY)
4. ✅ **Delete behavior** - Click connection → delete immediately (no confirmation)
5. ✅ **Smart positioning** - New steps placed next to last step (not 0,0)
6. ✅ **Mobile support** - Canvas works on mobile (responsive touch support)

---

## 🗄️ DATABASE SCHEMA

### **New Entity: StepConnection**

```php
<?php
namespace App\Entity;

use App\Repository\StepConnectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StepConnectionRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_connection', columns: ['source_output_id', 'target_input_id'])]
class StepConnection extends EntityBase
{
    #[ORM\ManyToOne(targetEntity: StepOutput::class, inversedBy: 'connection')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StepOutput $sourceOutput;

    #[ORM\ManyToOne(targetEntity: StepInput::class, inversedBy: 'connections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StepInput $targetInput;

    // Future: visual path data
    // #[ORM\Column(type: 'json', nullable: true)]
    // private ?array $visualPath = null;
}
```

### **Modified Entity: Step**

```php
// Add position fields
#[ORM\Column(type: 'integer', nullable: true)]
private ?int $positionX = null;

#[ORM\Column(type: 'integer', nullable: true)]
private ?int $positionY = null;
```

### **Modified Entity: StepOutput**

```php
// Add inverse relationship
#[ORM\OneToOne(mappedBy: 'sourceOutput', targetEntity: StepConnection::class, cascade: ['persist', 'remove'])]
private ?StepConnection $connection = null;
```

### **Modified Entity: StepInput**

```php
// Add inverse relationship
#[ORM\OneToMany(mappedBy: 'targetInput', targetEntity: StepConnection::class, cascade: ['persist', 'remove'])]
private Collection $connections;

public function __construct()
{
    parent::__construct();
    $this->connections = new ArrayCollection();
}
```

### **SQL Migration**

```sql
-- Create step_connection table
CREATE TABLE step_connection (
    id UUID PRIMARY KEY,
    source_output_id UUID NOT NULL REFERENCES step_output(id) ON DELETE CASCADE,
    target_input_id UUID NOT NULL REFERENCES step_input(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    organization_id UUID NOT NULL REFERENCES organization(id),
    created_by_id UUID REFERENCES "user"(id),
    UNIQUE(source_output_id, target_input_id)
);

-- Add position fields to step
ALTER TABLE step ADD COLUMN position_x INTEGER DEFAULT NULL;
ALTER TABLE step ADD COLUMN position_y INTEGER DEFAULT NULL;
```

---

## 📐 ARCHITECTURE

### **Relationship Cardinality:**

```
StepOutput (1) ←→ (0..1) StepConnection
StepInput (1) ←→ (0..N) StepConnection

Rules:
- One StepOutput has AT MOST one StepConnection
- One StepInput can have MANY StepConnections
- No self-loops (Step A → Step A)
- No duplicates (same output → same input)
```

### **Cascade Delete Behavior:**

```
DELETE StepOutput → CASCADE delete StepConnection ✅
DELETE StepInput → CASCADE delete all StepConnection(s) ✅
DELETE StepConnection → Keep StepOutput & StepInput ✅
```

---

## 🔌 API ENDPOINTS

### **1. Save Step Position**
```http
POST /treeflow/{treeflowId}/step/{stepId}/position

Request Body:
{
  "x": 100,
  "y": 200
}

Response:
{
  "success": true,
  "step": {
    "id": "uuid",
    "positionX": 100,
    "positionY": 200
  }
}
```

### **2. Create Connection**
```http
POST /treeflow/{treeflowId}/connection

Request Body:
{
  "outputId": "uuid",
  "inputId": "uuid"
}

Response Success:
{
  "success": true,
  "connection": {
    "id": "uuid",
    "sourceOutput": {...},
    "targetInput": {...}
  }
}

Response Error:
{
  "success": false,
  "error": "Cannot connect step to itself"
}
```

### **3. Delete Connection**
```http
DELETE /treeflow/{treeflowId}/connection/{connectionId}

Response:
{
  "success": true
}
```

### **4. Auto-create Input (Internal)**
```http
POST /treeflow/{treeflowId}/step/{stepId}/input/auto

Request Body:
{
  "sourceStepName": "Step A",
  "outputName": "Success"
}

Response:
{
  "success": true,
  "input": {
    "id": "uuid",
    "name": "On Success",
    "type": "any"
  }
}
```

---

## ⚙️ VALIDATION SERVICE

### **StepConnectionValidator**

```php
<?php
namespace App\Service;

use App\Entity\StepConnection;
use App\Entity\StepOutput;
use App\Entity\StepInput;
use App\Repository\StepConnectionRepository;

class StepConnectionValidator
{
    public function __construct(
        private StepConnectionRepository $connectionRepository
    ) {}

    public function validate(StepOutput $output, StepInput $input): array
    {
        // Rule 1: No self-loops
        if ($output->getStep()->getId() === $input->getStep()->getId()) {
            return [
                'valid' => false,
                'error' => 'Cannot connect step to itself'
            ];
        }

        // Rule 2: Output can only have one connection
        if ($output->getConnection() !== null) {
            return [
                'valid' => false,
                'error' => 'Output already has a connection. Delete existing connection first.'
            ];
        }

        // Rule 3: No duplicate output→input pairs (should be prevented by unique constraint)
        if ($this->connectionRepository->connectionExists($output, $input)) {
            return [
                'valid' => false,
                'error' => 'Connection already exists between this output and input'
            ];
        }

        return ['valid' => true];
    }
}
```

---

## 🎯 AUTO-INPUT NAMING STRATEGY

### **Naming Algorithm:**

```php
function generateInputName(StepOutput $output): string
{
    // Priority 1: Use output name if meaningful
    if ($output->getName() && !in_array(strtolower($output->getName()), ['output', 'default', 'out'])) {
        return "On " . $output->getName(); // "On Success", "On Error"
    }

    // Priority 2: Use source step name
    return "From " . $output->getStep()->getName(); // "From API Call"
}

// Examples:
// Output "Success" → Input "On Success"
// Output "Error" → Input "On Error"
// Output "Completed" → Input "On Completed"
// Output "output_1" → Input "From Step A"
```

---

## 📍 SMART POSITIONING ALGORITHM

### **New Step Position Calculation:**

```javascript
function calculateSmartPosition(existingSteps) {
    if (existingSteps.length === 0) {
        return { x: 100, y: 100 }; // First step
    }

    // Get last step position
    const lastStep = existingSteps[existingSteps.length - 1];
    const lastX = lastStep.positionX || 0;
    const lastY = lastStep.positionY || 0;

    // Place 300px to the right of last step
    return {
        x: lastX + 300,
        y: lastY
    };
}

// Result: Horizontal left-to-right flow by default
// Step 1 (100, 100) → Step 2 (400, 100) → Step 3 (700, 100)
```

---

## 🎨 FRONTEND ARCHITECTURE

### **Technology Stack:**

```bash
# Core Libraries
- Rete.js 2.x - Node editor framework
- Rete Area Plugin - Pan/zoom/drag
- Rete Connection Plugin - Visual connections
- Rete Minimap Plugin - Navigation overview
- Stimulus 3.x - Controller integration

# Installation
php bin/console importmap:require rete
php bin/console importmap:require rete-area-plugin
php bin/console importmap:require rete-connection-plugin
php bin/console importmap:require rete-minimap-plugin
```

### **Stimulus Controller Structure:**

```javascript
// assets/controllers/treeflow_canvas_controller.js
import { Controller } from '@hotwired/stimulus';
import { NodeEditor } from 'rete';
import { AreaPlugin } from 'rete-area-plugin';
import { ConnectionPlugin } from 'rete-connection-plugin';
import { MinimapPlugin } from 'rete-minimap-plugin';

export default class extends Controller {
    static values = {
        treeflowId: String,
        steps: Array,
        connections: Array
    }

    connect() {
        this.initEditor();
        this.renderSteps();
        this.renderConnections();
        this.setupInteractions();
    }
}
```

### **Visual Design Specs:**

```css
/* Canvas Container */
#treeflow-canvas {
    width: 100%;
    height: 80vh;
    min-height: 600px;
    background: radial-gradient(
        circle,
        rgba(139, 92, 246, 0.1) 1px,
        transparent 1px
    );
    background-size: 20px 20px;
    background-position: 0 0;
    position: relative;
    overflow: hidden;
}

/* Step Node */
.treeflow-node {
    position: absolute;
    min-width: 220px;
    max-width: 300px;
    background: linear-gradient(
        135deg,
        rgba(139, 92, 246, 0.9),
        rgba(124, 58, 237, 0.9)
    );
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    cursor: move;
    transition: box-shadow 0.2s;
}

.treeflow-node:hover {
    box-shadow: 0 8px 24px rgba(139, 92, 246, 0.5);
}

.treeflow-node-header {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    gap: 12px;
}

.treeflow-node-body {
    padding: 8px;
}

/* Connection Points */
.connection-point {
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    cursor: crosshair;
    transition: transform 0.2s;
    z-index: 10;
}

.connection-point:hover {
    transform: scale(1.3);
}

.output-point {
    right: -10px;
    background: linear-gradient(135deg, #10b981, #059669);
    border: 2px solid #065f46;
}

.input-point {
    left: -10px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border: 2px solid #92400e;
}

/* Connection Lines */
.connection-line {
    fill: none;
    stroke-width: 3;
    stroke-linecap: round;
    transition: stroke-width 0.2s;
    pointer-events: stroke;
    cursor: pointer;
}

.connection-line:hover {
    stroke-width: 5;
}

.connection-FULLY_COMPLETED {
    stroke: #10b981;
}

.connection-NOT_COMPLETED_AFTER_ATTEMPTS {
    stroke: #ef4444;
}

.connection-ANY {
    stroke: #3b82f6;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    #treeflow-canvas {
        height: 70vh;
        min-height: 400px;
    }

    .treeflow-node {
        min-width: 180px;
        max-width: 250px;
    }

    .connection-point {
        width: 24px;
        height: 24px;
    }
}

/* Touch support */
.treeflow-node {
    touch-action: none;
}
```

---

## 📱 MOBILE SUPPORT

### **Touch Interaction Strategy:**

```javascript
// Touch gestures
- Single finger drag = Pan canvas
- Two finger pinch = Zoom canvas
- Tap node = Select
- Double tap node = Edit modal
- Long press output → drag → release on input = Create connection
- Tap connection = Delete connection
```

### **Mobile Optimizations:**

```javascript
// Larger touch targets
connectionPoint.style.width = isMobile ? '24px' : '20px';
connectionPoint.style.height = isMobile ? '24px' : '20px';

// Simplified minimap on mobile
if (window.innerWidth < 768) {
    minimap.hide();
}

// Prevent page scroll during canvas interaction
canvas.addEventListener('touchmove', (e) => {
    e.preventDefault();
}, { passive: false });
```

---

## 🎯 PHASED IMPLEMENTATION

### **PHASE 1: DATABASE FOUNDATION** (~2 hours)

**Goal:** Entities & API ready

**Tasks:**
1. Create `StepConnection` entity
2. Add `connection` relationship to `StepOutput` (OneToOne)
3. Add `connections` relationship to `StepInput` (OneToMany)
4. Add `positionX`, `positionY` to `Step` entity
5. Create and run migrations
6. Create `StepConnectionRepository`
7. Create `StepConnectionValidator` service
8. Create API endpoints:
   - `POST /treeflow/{id}/step/{stepId}/position`
   - `POST /treeflow/{id}/connection`
   - `DELETE /treeflow/{id}/connection/{connectionId}`

**Testing:**
```bash
# Test position save
curl -X POST http://localhost/treeflow/{id}/step/{stepId}/position \
  -H "Content-Type: application/json" \
  -d '{"x": 100, "y": 200}'

# Test connection create
curl -X POST http://localhost/treeflow/{id}/connection \
  -H "Content-Type: application/json" \
  -d '{"outputId": "uuid", "inputId": "uuid"}'

# Verify database
docker-compose exec app php bin/console doctrine:query:sql \
  "SELECT * FROM step_connection"
```

**✅ Phase 1 Complete When:**
- APIs return 200 status
- Data persists in database
- Validation blocks invalid connections

---

### **PHASE 2: BASIC CANVAS UI** (~3 hours)

**Goal:** Visual layout with draggable nodes

**Tasks:**
1. Add view toggle button (List ↔ Canvas) to template header
2. Create canvas container `<div id="treeflow-canvas">`
3. Add dotted grid background CSS
4. Install Rete.js via importmap
5. Create `treeflow_canvas_controller.js`
6. Initialize Rete.js NodeEditor
7. Create Step node HTML component
8. Render steps as nodes at `positionX/positionY`
9. Implement node dragging with AreaPlugin
10. Auto-save position on drag end
11. Add pan (drag background) and zoom (mouse wheel)

**Testing:**
- ✅ Toggle between List and Canvas views
- ✅ See steps as visual nodes on grid
- ✅ Drag nodes to new positions
- ✅ Pan canvas by dragging background
- ✅ Zoom with mouse wheel
- ✅ Refresh page → positions persist

**✅ Phase 2 Complete When:**
- Can drag nodes around canvas
- Positions save automatically
- Toggle switches views smoothly

---

### **PHASE 3: VISUAL CONNECTIONS** (~2 hours)

**Goal:** Read-only connection rendering

**Tasks:**
1. Add output connection points (right side of nodes)
2. Add input connection points (left side of nodes)
3. Style connection points with circles
4. Load existing `StepConnection` data from server
5. Render connections as SVG curved lines
6. Apply color coding based on `InputType`:
   - `FULLY_COMPLETED` → Green (#10b981)
   - `NOT_COMPLETED_AFTER_ATTEMPTS` → Red (#ef4444)
   - `ANY` → Blue (#3b82f6)
7. Add hover effect (highlight + tooltip)

**Testing:**
- ✅ See output points on right side of nodes
- ✅ See input points on left side of nodes
- ✅ Existing connections render as colored curves
- ✅ Hover connection → highlight + show metadata
- ✅ Colors match input types

**✅ Phase 3 Complete When:**
- All connections visible
- Colors correct
- Hover shows info

---

### **PHASE 4: DRAG-TO-CONNECT** (~4 hours)

**Goal:** Interactive connection creation

**Tasks:**
1. Implement drag start from output point
2. Show ghost line while dragging
3. Implement drop on input point
4. Add client-side validation (self-loop, duplicate)
5. Show error toast on validation failure
6. Auto-create `StepInput` if target step has none:
   - `type = ANY`
   - `name = "On {output.name}"` or `"From {step.name}"`
7. Show input selection modal if step has multiple inputs
8. Call connection API on successful drop
9. Render new connection immediately
10. Implement click connection → delete (no confirmation)

**Testing:**
- ✅ Drag from output → input creates connection
- ✅ Dragging to same step shows error
- ✅ Dragging already connected output shows error
- ✅ Empty step auto-creates input with smart name
- ✅ Step with inputs shows selection modal
- ✅ Click connection → deletes immediately
- ✅ New connection appears instantly
- ✅ Refresh page → connection persists

**✅ Phase 4 Complete When:**
- Can create connections by dragging
- Validation prevents invalid connections
- Can delete connections by clicking
- Auto-input creation works

---

### **PHASE 5: POLISH & INTEGRATION** (~3 hours)

**Goal:** Complete UX refinement

**Tasks:**
1. Add double-click node → open edit modal
2. Integrate minimap for canvas navigation
3. Add keyboard shortcuts:
   - `Delete` → Delete selected connection
   - `Esc` → Deselect
4. Show connection metadata tooltip on hover
5. Create auto-layout algorithm (force-directed or hierarchical)
6. Add "Auto-Layout" button to organize nodes
7. Add node badges (question count, input/output count)
8. Add zoom controls (+/- buttons, fit to screen)
9. Ensure modal edits refresh canvas
10. Mobile touch optimization
11. Loading states and error handling

**Testing:**
- ✅ Double-click node → modal opens
- ✅ Minimap shows canvas overview
- ✅ Delete key removes selected connection
- ✅ Hover connection → tooltip with type/condition
- ✅ Auto-Layout organizes nodes nicely
- ✅ Edit step in modal → canvas updates
- ✅ Zoom controls work
- ✅ Touch gestures work on mobile

**✅ Phase 5 Complete When:**
- All polish features working
- Mobile experience smooth
- UX feels professional

---

## 🎨 CANVAS INTERACTION FLOWS

### **Flow 1: Create Connection (Step with Existing Inputs)**

```
1. User drags from Output point
2. Ghost line follows cursor
3. User drops on Input point
4. Client validates:
   - Self-loop? → Show error
   - Output already connected? → Show error
   - Duplicate? → Show error
5. Call POST /connection API
6. Server validates again
7. Server creates StepConnection
8. Server returns connection data
9. Client renders new colored line
10. Success!
```

### **Flow 2: Create Connection (Empty Step)**

```
1. User drags from Output point "Success"
2. Ghost line follows cursor
3. User drops on Step (has no inputs)
4. Client detects: step.inputs.length === 0
5. Call POST /step/{id}/input/auto
   Body: {
     "sourceStepName": "API Call",
     "outputName": "Success"
   }
6. Server creates StepInput:
   - name: "On Success"
   - type: ANY
   - step: target step
7. Server returns new input
8. Call POST /connection API
   Body: {
     "outputId": output.id,
     "inputId": newInput.id
   }
9. Server creates StepConnection
10. Client renders input point + connection line
11. Success!
```

### **Flow 3: Create Connection (Multiple Inputs)**

```
1. User drags from Output point
2. Ghost line follows cursor
3. User drops on Step (has 3 inputs)
4. Client shows modal:
   ┌─────────────────────────┐
   │ Select Target Input     │
   ├─────────────────────────┤
   │ ○ On Success (ANY)      │
   │ ○ On Error (FAILED)     │
   │ ○ On Retry (ANY)        │
   ├─────────────────────────┤
   │ [Cancel]  [Connect]     │
   └─────────────────────────┘
5. User selects "On Error"
6. Call POST /connection API
7. Server creates StepConnection
8. Client renders connection line
9. Modal closes
10. Success!
```

### **Flow 4: Delete Connection**

```
1. User clicks connection line
2. Client gets connectionId from line data
3. Call DELETE /connection/{id} API
4. Server deletes StepConnection
5. Server returns success
6. Client removes line from canvas
7. Input and Output remain intact
8. Success!
```

### **Flow 5: Delete Input/Output**

```
1. User deletes StepInput via modal
2. Server CASCADE deletes all StepConnection where targetInput = this
3. Client receives success
4. Client removes all connection lines pointing to this input
5. Client removes input point from node
6. Success!

(Same for StepOutput deletion)
```

---

## 🧪 TESTING STRATEGY

### **Unit Tests:**
```php
// tests/Service/StepConnectionValidatorTest.php
public function testBlocksSelfLoop()
{
    $step = new Step();
    $output = (new StepOutput())->setStep($step);
    $input = (new StepInput())->setStep($step);

    $result = $this->validator->validate($output, $input);

    $this->assertFalse($result['valid']);
    $this->assertEquals('Cannot connect step to itself', $result['error']);
}

public function testBlocksDuplicateOutputConnection()
{
    $output = new StepOutput();
    $existingConnection = new StepConnection();
    $output->setConnection($existingConnection);

    $input = new StepInput();

    $result = $this->validator->validate($output, $input);

    $this->assertFalse($result['valid']);
    $this->assertStringContains('already has a connection', $result['error']);
}
```

### **Functional Tests:**
```php
// tests/Controller/TreeFlowCanvasTest.php
public function testCreateConnectionViaAPI()
{
    $client = static::createClient();

    $output = $this->createStepOutput();
    $input = $this->createStepInput();

    $client->request('POST', '/treeflow/' . $treeflow->getId() . '/connection', [
        'outputId' => $output->getId(),
        'inputId' => $input->getId()
    ]);

    $this->assertResponseIsSuccessful();
    $this->assertJsonContains(['success' => true]);
}
```

### **E2E Tests (Manual):**
```
✅ Drag node → Position saves
✅ Drag output to input → Connection created
✅ Drag output to same step → Error shown
✅ Click connection → Deleted
✅ Double-click node → Modal opens
✅ Edit in modal → Canvas updates
✅ Auto-layout → Nodes reorganize
✅ Mobile touch → All interactions work
```

---

## 📊 SUCCESS METRICS

### **Performance:**
- Canvas renders < 500ms for 50 steps
- Drag response < 16ms (60fps)
- Connection creation < 200ms
- Auto-save debounced to 300ms

### **UX:**
- ✅ View toggle works instantly
- ✅ Drag feels smooth (60fps)
- ✅ Connections render cleanly
- ✅ Validation errors are clear
- ✅ Mobile touch is responsive

### **Code Quality:**
- ✅ All entities have proper types
- ✅ API endpoints have validation
- ✅ Frontend has error handling
- ✅ CSS is responsive
- ✅ Tests cover critical paths

---

## 🚀 DEPLOYMENT CHECKLIST

**Before VPS Deploy:**
1. ✅ All phases tested locally
2. ✅ Migrations tested on local DB
3. ✅ API endpoints return correct responses
4. ✅ Canvas works on mobile browser
5. ✅ No console errors in browser
6. ✅ Commit all changes to git

**VPS Deployment:**
```bash
# Local: Commit changes
git add .
git commit -m "Add TreeFlow canvas editor with StepConnection entity"
git push origin main

# VPS: Deploy
ssh -i /home/user/.ssh/infinity_vps root@91.98.137.175 'cd /opt/infinity && \
  git pull origin main && \
  docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction --env=prod && \
  docker-compose exec -T app php bin/console cache:clear --env=prod && \
  docker-compose exec -T app php bin/console cache:warmup --env=prod && \
  docker-compose exec -T app php bin/console importmap:install && \
  docker-compose restart nginx'
```

**Post-Deploy Verification:**
```bash
# Check health
curl -k https://91.98.137.175/health/detailed

# Test canvas page loads
curl -I https://91.98.137.175/treeflow/{id}

# Check database
ssh -i /home/user/.ssh/infinity_vps root@91.98.137.175 \
  'cd /opt/infinity && docker-compose exec -T app php bin/console doctrine:query:sql "SELECT COUNT(*) FROM step_connection"'
```

---

## 📚 FUTURE ENHANCEMENTS

### **Phase 6 (Future):**
- [ ] Export workflow as image (PNG/SVG)
- [ ] Workflow templates (save/load canvas layouts)
- [ ] Collaborative editing (real-time multi-user)
- [ ] Workflow execution visualization (animate active path)
- [ ] Advanced auto-layout (Dagre, ELK algorithms)
- [ ] Connection path customization (user-drawn curves)
- [ ] Step grouping/containers
- [ ] Workflow versioning with canvas snapshots

### **Technical Debt Cleanup:**
- [ ] Remove deprecated `destinationStep` from StepOutput
- [ ] Remove deprecated `sourceStep` from StepInput
- [ ] Add visual path data to StepConnection
- [ ] Add connection-level permissions
- [ ] Add undo/redo system

---

## 📝 NOTES & CONSIDERATIONS

### **Why StepConnection Entity:**
- More flexible than direct relationships
- Allows future features (visual paths, metadata)
- Industry standard pattern (n8n, Zapier, etc.)
- Easier to query and validate

### **Why Rete.js:**
- Purpose-built for node editors
- Active development and community
- Framework agnostic (works with Stimulus)
- Similar to n8n's architecture (Vue Flow)
- Handles canvas, drag, connections out-of-box

### **Why Keep Accordion View:**
- Accessibility (screen readers)
- Mobile fallback (if touch issues)
- Quick text-based editing
- Users familiar with current UI
- Easier to see all details at once

### **Mobile Challenges:**
- Touch targets need to be larger (24px vs 20px)
- Gestures can conflict (pan vs drag)
- Small screens limit visible area
- Connection dragging harder without mouse
- Solution: Larger touch areas, clear visual feedback, simplified controls

---

## ✅ APPROVAL CHECKLIST

- [x] StepConnection entity design approved
- [x] API endpoints defined
- [x] Validation rules clear
- [x] Auto-input naming strategy approved
- [x] Connection colors approved (Green/Red/Blue)
- [x] Delete without confirmation approved
- [x] Smart positioning approved
- [x] Mobile support required
- [x] Phased implementation plan
- [x] Testing strategy defined
- [x] Deployment plan ready

---

## 🎯 FINAL DELIVERABLE

**When complete, users can:**

1. ✅ Toggle between List view and Canvas view
2. ✅ See workflow as visual node graph
3. ✅ Drag nodes to reposition (auto-saves)
4. ✅ Pan and zoom infinite canvas
5. ✅ Drag from output → input to connect
6. ✅ Auto-create inputs on empty steps
7. ✅ Select from multiple inputs
8. ✅ See colored connections by type
9. ✅ Click connection to delete
10. ✅ Double-click node to edit
11. ✅ Use minimap for navigation
12. ✅ Use keyboard shortcuts
13. ✅ Auto-layout to organize
14. ✅ Works on mobile devices

**This creates a professional n8n-like workflow editor within Infinity's Symfony architecture.** 🚀

---

_Last Updated: 2025-01-04_
_Status: Ready for Implementation_
