# TreeFlow CRUD Implementation Plan

**Complete AI Agent Guidance System - Progressive Implementation**

---

## 📋 Overview

This document outlines the complete implementation plan for TreeFlow CRUD functionality, broken down into 6 progressive phases. Each phase is self-contained and builds upon the previous one.

**Technology Stack:**
- Symfony 7.3 Controllers
- Doctrine ORM with UUIDv7
- Bootstrap 5 + Stimulus
- Turbo Frames for modals
- Multi-tenant organization isolation

**Total Estimated Time:** 3.5-4 hours
**Total Files:** ~30 files
**Lines of Code:** ~3,500-4,000 LOC

---

## 🎯 Phase 1: Foundation (TreeFlow Basic CRUD)

**Goal:** Create basic TreeFlow CRUD with auto-versioning and organization isolation

**Estimated Time:** 60 minutes

### 1.1 Entity Updates

**File:** `src/Entity/Question.php`
```php
// Add viewOrder field for sorting
#[ORM\Column(type: 'integer')]
protected int $viewOrder = 1;

// Add getter/setter
public function getViewOrder(): int { return $this->viewOrder; }
public function setViewOrder(int $viewOrder): self { ... }
```

**File:** `src/Entity/TreeFlow.php`
```php
// Update version auto-increment logic
public function __construct()
{
    parent::__construct();
    $this->steps = new ArrayCollection();
    $this->version = '1'; // Start at version 1
}

#[ORM\PreUpdate]
public function incrementVersion(): void
{
    // Auto-increment version on each update
    $currentVersion = (int)$this->version;
    $this->version = (string)($currentVersion + 1);
}
```

**Migration:**
```bash
docker-compose exec app php bin/console make:migration
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

---

### 1.2 Security Voter

**File:** `src/Security/Voter/TreeFlowVoter.php`

**Permissions:**
- `TREEFLOW_LIST`: ROLE_ADMIN (all orgs) + ROLE_ORGANIZATION_ADMIN (own org)
- `TREEFLOW_CREATE`: ROLE_ADMIN (any org) + ROLE_ORGANIZATION_ADMIN (own org)
- `TREEFLOW_VIEW`: ROLE_ADMIN (all) + ROLE_ORGANIZATION_ADMIN (own org)
- `TREEFLOW_EDIT`: ROLE_ADMIN (all) + ROLE_ORGANIZATION_ADMIN (own org)
- `TREEFLOW_DELETE`: ROLE_ADMIN (all) + ROLE_ORGANIZATION_ADMIN (own org)

**Implementation:**
```php
final class TreeFlowVoter extends Voter
{
    public const LIST = 'TREEFLOW_LIST';
    public const CREATE = 'TREEFLOW_CREATE';
    public const VIEW = 'TREEFLOW_VIEW';
    public const EDIT = 'TREEFLOW_EDIT';
    public const DELETE = 'TREEFLOW_DELETE';

    private function canList(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_ORGANIZATION_ADMIN', $user->getRoles(), true);
    }

    private function canView(?TreeFlow $treeFlow, User $user): bool
    {
        if (!$treeFlow) return false;

        // Admin sees all
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) return true;

        // Org admin sees only their org
        if (in_array('ROLE_ORGANIZATION_ADMIN', $user->getRoles(), true)) {
            return $user->getOrganization()
                && $user->getOrganization()->getId()->equals($treeFlow->getOrganization()->getId());
        }

        return false;
    }

    // Similar logic for CREATE, EDIT, DELETE
}
```

---

### 1.3 Form Type

**File:** `src/Form/TreeFlowFormType.php`

**Fields:**
- `name` - TextType (required, 2-255 chars)
- `active` - CheckboxType (default: true)
- ~~`version`~~ - AUTO-MANAGED (backend only)
- ~~`organization`~~ - AUTO-SET (backend only)

```php
class TreeFlowFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'treeflow.form.name',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 2, max: 255),
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'treeflow.form.active',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => $options['is_edit'] ? 'button.update' : 'button.create',
                'attr' => ['class' => 'btn infinity-btn-primary'],
            ]);
    }
}
```

---

### 1.4 Controller

**File:** `src/Controller/TreeFlowController.php`

**Routes:**
```php
#[Route('/treeflow')]
final class TreeFlowController extends BaseApiController
{
    // GET /treeflow - List view
    #[Route('', name: 'treeflow_index', methods: ['GET'])]
    public function index(): Response

    // GET /treeflow/new - Create form
    // POST /treeflow/new - Handle creation
    #[Route('/new', name: 'treeflow_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Auto-set organization from current user
        $user = $this->getUser();
        $treeFlow = new TreeFlow();
        $treeFlow->setOrganization($user->getOrganization());
        $treeFlow->setVersion('1'); // Initial version

        // ... form handling
    }

    // GET /treeflow/{id} - Show details
    #[Route('/{id}', name: 'treeflow_show', methods: ['GET'])]
    public function show(TreeFlow $treeFlow): Response

    // GET/POST /treeflow/{id}/edit - Edit form
    #[Route('/{id}/edit', name: 'treeflow_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TreeFlow $treeFlow): Response
    {
        // Version auto-increments via PreUpdate callback
        // ... form handling
    }

    // POST /treeflow/{id}/delete - Delete
    #[Route('/{id}/delete', name: 'treeflow_delete', methods: ['POST'])]
    public function delete(Request $request, TreeFlow $treeFlow): Response

    // GET /treeflow/api/search - JSON API
    #[Route('/api/search', name: 'treeflow_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
}
```

**API Response (Basic):**
```php
protected function entityToArray(object $entity): array
{
    assert($entity instanceof TreeFlow);

    return [
        'id' => $entity->getId()?->toString(),
        'name' => $entity->getName(),
        'version' => $entity->getVersion(),
        'active' => $entity->isActive(),
        'organizationId' => $entity->getOrganization()->getId()?->toString(),
        'organizationName' => $entity->getOrganization()->getName(),
        'stepsCount' => $entity->getSteps()->count(),
        'createdAt' => $entity->getCreatedAt()->format('c'),
        'createdAtFormatted' => $entity->getCreatedAt()->format('M d, Y'),
        'updatedAtFormatted' => $entity->getUpdatedAt()->format('M d, Y'),
        'createdByName' => $entity->getCreatedBy()?->getName(),
    ];
}
```

---

### 1.5 Templates

**File:** `templates/treeflow/index.html.twig`
- Extends `_base_entity_list.html.twig`
- Grid/List/Table views with data-bind
- Shows: Name, Version badge, Active status, Organization, Steps count
- Actions: View, Edit, Delete

**File:** `templates/treeflow/show.html.twig` (Basic version)
- Header with Name + Version badge + Active status
- Stats: Total steps, Active status
- List of Steps (basic, just names)
- Actions: Edit TreeFlow, Delete TreeFlow

**File:** `templates/treeflow/new.html.twig`
- Standalone form (fallback)

**File:** `templates/treeflow/edit.html.twig`
- Standalone form (fallback)

**File:** `templates/treeflow/_form_modal.html.twig`
- Modal form with Turbo Frame
- Live validation

---

### 1.6 Translations

**File:** `translations/messages.en.yaml`
```yaml
treeflow:
  singular: TreeFlow
  plural: TreeFlows
  name: Name
  version: Version
  active: Active
  inactive: Inactive
  steps: Steps

  flash:
    created_successfully: TreeFlow created successfully
    updated_successfully: TreeFlow updated successfully (Version %version%)
    deleted_successfully: TreeFlow deleted successfully

  form:
    name: TreeFlow Name
    name_placeholder: Enter TreeFlow name
    active: Active
    active_help: Inactive TreeFlows are hidden from users
```

---

### 1.7 Navigation

**File:** `templates/base.html.twig`
```twig
{# Add to navbar #}
<li class="nav-item">
    <a class="nav-link" href="{{ path('treeflow_index') }}">
        <i class="bi bi-diagram-3 me-2"></i>{{ 'treeflow.plural'|trans }}
    </a>
</li>
```

---

## ✅ Phase 1 Deliverables

- [ ] TreeFlow entity with auto-versioning
- [ ] Question entity with viewOrder field
- [ ] TreeFlowVoter with org admin permissions
- [ ] TreeFlowController with basic CRUD
- [ ] TreeFlowFormType (no version/organization fields)
- [ ] Basic templates (index, show, new, edit, modal)
- [ ] API endpoint with basic JSON response
- [ ] Navigation link added
- [ ] Translations

**Testing:**
1. Create TreeFlow as admin → version starts at "1"
2. Edit TreeFlow → version increments to "2"
3. Delete TreeFlow → cascades to Steps
4. Org admin can only see/edit own organization's TreeFlows
5. API returns proper JSON with organization data

---

## 🎯 Phase 2: Step Management

**Goal:** Add full CRUD for Steps within TreeFlows

**Estimated Time:** 30 minutes

### 2.1 Form Type

**File:** `src/Form/StepFormType.php`

**Fields:**
- `name` - TextType (required)
- `first` - CheckboxType (mark as first step)
- `objective` - TextareaType (nullable)
- `prompt` - TextareaType (nullable)

```php
class StepFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'step.form.name',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('first', CheckboxType::class, [
                'label' => 'step.form.first',
                'required' => false,
                'help' => 'step.form.first_help',
            ])
            ->add('objective', TextareaType::class, [
                'label' => 'step.form.objective',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('prompt', TextareaType::class, [
                'label' => 'step.form.prompt',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('submit', SubmitType::class, [
                'label' => $options['is_edit'] ? 'button.update' : 'button.create',
            ]);
    }
}
```

---

### 2.2 Controller

**File:** `src/Controller/StepController.php`

**Routes:**
```php
#[Route('/treeflow')]
final class StepController extends AbstractController
{
    // GET /treeflow/{treeflowId}/step/new
    // POST /treeflow/{treeflowId}/step/new
    #[Route('/{treeflowId}/step/new', name: 'step_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $treeflowId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $step = new Step();
        $step->setTreeFlow($treeFlow);

        // ... form handling
    }

    // GET /treeflow/{treeflowId}/step/{stepId}/edit
    // POST /treeflow/{treeflowId}/step/{stepId}/edit
    #[Route('/{treeflowId}/step/{stepId}/edit', name: 'step_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $treeflowId, string $stepId): Response

    // POST /treeflow/{treeflowId}/step/{stepId}/delete
    #[Route('/{treeflowId}/step/{stepId}/delete', name: 'step_delete', methods: ['POST'])]
    public function delete(Request $request, string $treeflowId, string $stepId): Response
}
```

**Security:** All actions check `TreeFlowVoter::EDIT` on parent TreeFlow

---

### 2.3 Template

**File:** `templates/treeflow/step/_form_modal.html.twig`
- Modal form for Step creation/editing
- Checkbox for "first" with warning: "Only one step can be first"
- Turbo Frame support

---

### 2.4 Enhanced Show Page

**File:** `templates/treeflow/show.html.twig` (Updated)
```twig
{# Add Step management UI #}
<div class="d-flex justify-content-between mb-4">
    <h2>Steps ({{ treeFlow.steps|length }})</h2>
    <button class="btn infinity-btn-primary"
            data-controller="modal-opener"
            data-modal-opener-url-value="{{ path('step_new', {treeflowId: treeFlow.id}) }}">
        <i class="bi bi-plus-circle me-2"></i>Add Step
    </button>
</div>

{# Steps List #}
{% for step in treeFlow.steps %}
    <div class="infinity-card mb-3 p-3">
        <div class="d-flex justify-content-between">
            <div>
                <h5>
                    {{ step.name }}
                    {% if step.first %}
                        <span class="badge bg-success">FIRST</span>
                    {% endif %}
                </h5>
                {% if step.objective %}
                    <p class="text-muted">{{ step.objective }}</p>
                {% endif %}
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><button class="dropdown-item"
                                data-controller="modal-opener"
                                data-modal-opener-url-value="{{ path('step_edit', {treeflowId: treeFlow.id, stepId: step.id}) }}">
                        <i class="bi bi-pencil me-2"></i>Edit
                    </button></li>
                    <li><button class="dropdown-item text-danger"
                                onclick="if(confirm('Delete this step?')) { /* submit delete form */ }">
                        <i class="bi bi-trash me-2"></i>Delete
                    </button></li>
                </ul>
            </div>
        </div>
    </div>
{% endfor %}
```

---

### 2.5 Translations

```yaml
step:
  singular: Step
  plural: Steps
  form:
    name: Step Name
    first: First Step
    first_help: Mark this as the starting point of the TreeFlow
    objective: Objective
    prompt: Prompt
  flash:
    created_successfully: Step created successfully
    updated_successfully: Step updated successfully
    deleted_successfully: Step deleted successfully
```

---

## ✅ Phase 2 Deliverables

- [ ] StepController with CRUD operations
- [ ] StepFormType with first checkbox
- [ ] Modal form template
- [ ] Enhanced show page with Step management
- [ ] Translations
- [ ] FirstStepSubscriber working correctly

**Testing:**
1. Add Step to TreeFlow
2. Mark Step as "first" → other Steps auto-unset first=true
3. Edit Step
4. Delete Step
5. Security: Org admin can only manage Steps in own org TreeFlows

---

## 🎯 Phase 3: Question & FewShot Management

**Goal:** Add CRUD for Questions and FewShot Examples

**Estimated Time:** 45 minutes

### 3.1 Form Types

**File:** `src/Form/QuestionFormType.php`

**Fields:**
- `name` - TextType
- `prompt` - TextareaType
- `objective` - TextareaType
- `importance` - IntegerType (1-10, range slider)
- `viewOrder` - IntegerType (auto-set)

**File:** `src/Form/FewShotExampleFormType.php`

**Fields:**
- `type` - ChoiceType (Positive/Negative)
- `name` - TextType
- `prompt` - TextareaType
- `description` - TextareaType

---

### 3.2 Controllers

**File:** `src/Controller/QuestionController.php`

**Routes:**
- `GET /treeflow/{treeflowId}/step/{stepId}/question/new`
- `POST /treeflow/{treeflowId}/step/{stepId}/question/new`
- `GET /treeflow/{treeflowId}/step/{stepId}/question/{questionId}/edit`
- `POST /treeflow/{treeflowId}/step/{stepId}/question/{questionId}/edit`
- `POST /treeflow/{treeflowId}/step/{stepId}/question/{questionId}/delete`
- `POST /treeflow/{treeflowId}/step/{stepId}/question/reorder` - Update viewOrder

**File:** `src/Controller/FewShotExampleController.php`

**Routes:**
- `GET /treeflow/{treeflowId}/step/{stepId}/question/{questionId}/fewshot/new`
- `POST /treeflow/{treeflowId}/step/{stepId}/question/{questionId}/fewshot/new`
- `GET /treeflow/{treeflowId}/step/{stepId}/question/{questionId}/fewshot/{fewshotId}/edit`
- `POST /treeflow/{treeflowId}/step/{stepId}/question/{questionId}/fewshot/{fewshotId}/edit`
- `POST /treeflow/{treeflowId}/step/{stepId}/question/{questionId}/fewshot/{fewshotId}/delete`

**Security:** Check TreeFlowVoter::EDIT on root TreeFlow

---

### 3.3 Templates

**File:** `templates/treeflow/question/_form_modal.html.twig`
- Range slider for importance (1-10)
- Auto-calculate viewOrder from existing questions

**File:** `templates/treeflow/fewshot/_form_modal.html.twig`
- Radio buttons for Positive/Negative type
- Syntax highlighting for prompt field (optional)

---

### 3.4 Enhanced Show Page

**File:** `templates/treeflow/show.html.twig` (Updated)

**Add Questions section inside each Step:**
```twig
{# Inside Step card #}
<div class="mt-3">
    <h6>Questions ({{ step.questions|length }})</h6>

    {% for question in step.questions|sort((a, b) => a.viewOrder <=> b.viewOrder) %}
        <div class="ms-3 mb-2 p-2 border-start border-info">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>{{ question.name }}</strong>
                    <span class="badge bg-warning text-dark">⭐ {{ question.importance }}</span>
                    <small class="text-muted">(Order: {{ question.viewOrder }})</small>
                </div>
                <div>
                    <button class="btn btn-sm"
                            data-controller="modal-opener"
                            data-modal-opener-url-value="{{ path('question_edit', {...}) }}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm"
                            data-controller="modal-opener"
                            data-modal-opener-url-value="{{ path('fewshot_new', {...}) }}">
                        <i class="bi bi-plus-circle"></i> FewShot
                    </button>
                </div>
            </div>

            {# FewShot Examples #}
            {% if question.examples|length > 0 %}
                <div class="mt-2 ms-3">
                    <small class="text-muted">Examples:</small>
                    {% for example in question.examples %}
                        <span class="badge {{ example.type.value == 'positive' ? 'bg-success' : 'bg-danger' }}">
                            {{ example.name }}
                        </span>
                    {% endfor %}
                </div>
            {% endif %}
        </div>
    {% endfor %}

    <button class="btn btn-sm infinity-btn-ai ms-3"
            data-controller="modal-opener"
            data-modal-opener-url-value="{{ path('question_new', {treeflowId: treeFlow.id, stepId: step.id}) }}">
        <i class="bi bi-plus-circle me-1"></i>Add Question
    </button>
</div>
```

---

### 3.5 Translations

```yaml
question:
  singular: Question
  plural: Questions
  form:
    name: Question Name
    prompt: Prompt
    objective: Objective
    importance: Importance (1-10)
    view_order: Display Order
  flash:
    created_successfully: Question created successfully
    updated_successfully: Question updated successfully
    deleted_successfully: Question deleted successfully

fewshot:
  singular: Example
  plural: Examples
  type:
    positive: Positive Example
    negative: Negative Example
  form:
    type: Example Type
    name: Example Name
    prompt: Example Prompt
    description: Description
  flash:
    created_successfully: Example created successfully
```

---

## ✅ Phase 3 Deliverables

- [ ] QuestionController with CRUD + reorder
- [ ] FewShotExampleController with CRUD
- [ ] QuestionFormType with importance slider
- [ ] FewShotExampleFormType with type radio
- [ ] Modal templates
- [ ] Enhanced show page with Questions/FewShots
- [ ] Translations
- [ ] Questions sorted by viewOrder

**Testing:**
1. Add Question to Step → viewOrder auto-assigned
2. Reorder Questions → drag-and-drop updates viewOrder
3. Add FewShot (positive/negative) to Question
4. Edit/Delete Questions and FewShots
5. Verify cascade delete: Delete Question → FewShots deleted

---

## 🎯 Phase 4: Output & Input Management

**Goal:** Add CRUD for StepOutputs and StepInputs

**Estimated Time:** 45 minutes

### 4.1 Form Types

**File:** `src/Form/StepOutputFormType.php`

**Fields:**
- `name` - TextType
- `description` - TextareaType
- `conditional` - TextareaType (freeform, with examples)
- `destinationStep` - EntityType (select from same TreeFlow's Steps)

```php
->add('destinationStep', EntityType::class, [
    'class' => Step::class,
    'choices' => $options['available_steps'], // Pass from controller
    'choice_label' => 'name',
    'required' => false,
    'help' => 'output.form.destination_help',
])
```

**File:** `src/Form/StepInputFormType.php`

**Fields:**
- `name` - TextType
- `type` - ChoiceType (FULLY_COMPLETED / NOT_COMPLETED_AFTER_ATTEMPTS / ANY)
- `sourceStep` - EntityType (select from same TreeFlow's Steps)
- `prompt` - TextareaType

---

### 4.2 Controllers

**File:** `src/Controller/StepOutputController.php`

**Routes:**
- `GET /treeflow/{treeflowId}/step/{stepId}/output/new`
- `POST /treeflow/{treeflowId}/step/{stepId}/output/new`
- `GET /treeflow/{treeflowId}/step/{stepId}/output/{outputId}/edit`
- `POST /treeflow/{treeflowId}/step/{stepId}/output/{outputId}/edit`
- `POST /treeflow/{treeflowId}/step/{stepId}/output/{outputId}/delete`

**Special Logic:**
```php
public function new(Request $request, string $treeflowId, string $stepId): Response
{
    $treeFlow = $this->treeFlowRepository->find($treeflowId);
    $step = $this->stepRepository->find($stepId);

    // Get all steps from this TreeFlow for destination dropdown
    $availableSteps = $treeFlow->getSteps()->filter(
        fn(Step $s) => !$s->getId()->equals($step->getId()) // Exclude current step
    );

    $form = $this->createForm(StepOutputFormType::class, $output, [
        'available_steps' => $availableSteps,
    ]);

    // ...
}
```

**File:** `src/Controller/StepInputController.php`

**Routes:** Similar structure to StepOutputController

---

### 4.3 Templates

**File:** `templates/treeflow/output/_form_modal.html.twig`
- Conditional field with help text: "Examples: regex:/pattern/i, keywords:urgent,high-priority"
- Dropdown for destination step (with current step excluded)

**File:** `templates/treeflow/input/_form_modal.html.twig`
- Radio buttons for input type
- Dropdown for source step

---

### 4.4 Enhanced Show Page

**File:** `templates/treeflow/show.html.twig` (Updated)

**Add Outputs/Inputs sections inside each Step:**
```twig
{# Outputs Section #}
<div class="mt-3">
    <h6>Outputs ({{ step.outputs|length }})</h6>
    {% for output in step.outputs %}
        <div class="ms-3 mb-2 p-2 bg-dark border-start border-success">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>{{ output.name }}</strong>
                    {% if output.destinationStep %}
                        <span class="text-muted">→ {{ output.destinationStep.name }}</span>
                    {% endif %}
                    {% if output.conditional %}
                        <div class="mt-1">
                            <code class="text-info">{{ output.conditional }}</code>
                        </div>
                    {% endif %}
                </div>
                <div>
                    <button class="btn btn-sm" ...>Edit</button>
                    <button class="btn btn-sm text-danger" ...>Delete</button>
                </div>
            </div>
        </div>
    {% endfor %}
    <button class="btn btn-sm infinity-btn-ai" ...>Add Output</button>
</div>

{# Inputs Section #}
<div class="mt-3">
    <h6>Inputs ({{ step.inputs|length }})</h6>
    {% for input in step.inputs %}
        <div class="ms-3 mb-2 p-2 bg-dark border-start border-warning">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>{{ input.name }}</strong>
                    <span class="badge bg-secondary">{{ input.type.value }}</span>
                    {% if input.sourceStep %}
                        <span class="text-muted">← {{ input.sourceStep.name }}</span>
                    {% endif %}
                </div>
                <div>
                    <button class="btn btn-sm" ...>Edit</button>
                    <button class="btn btn-sm text-danger" ...>Delete</button>
                </div>
            </div>
        </div>
    {% endfor %}
    <button class="btn btn-sm infinity-btn-ai" ...>Add Input</button>
</div>
```

---

### 4.5 Translations

```yaml
output:
  singular: Output
  plural: Outputs
  form:
    name: Output Name
    description: Description
    conditional: Conditional Expression
    conditional_help: "Examples: regex:/urgent/i or keywords:high-priority,immediate"
    destination: Destination Step
    destination_help: Where to route when this condition matches
  flash:
    created_successfully: Output created successfully
    updated_successfully: Output updated successfully
    deleted_successfully: Output deleted successfully

input:
  singular: Input
  plural: Inputs
  form:
    name: Input Name
    type: Entry Type
    source: Source Step
    prompt: Entry Prompt
  type:
    fully_completed: Fully Completed
    not_completed_after_attempts: Not Completed After Attempts
    any: Any Status
  flash:
    created_successfully: Input created successfully
    updated_successfully: Input updated successfully
    deleted_successfully: Input deleted successfully
```

---

## ✅ Phase 4 Deliverables

- [ ] StepOutputController with CRUD
- [ ] StepInputController with CRUD
- [ ] StepOutputFormType with destination dropdown
- [ ] StepInputFormType with type radio + source dropdown
- [ ] Modal templates
- [ ] Enhanced show page with Outputs/Inputs
- [ ] Translations
- [ ] Conditional validation (freeform string)

**Testing:**
1. Add Output to Step with destination + conditional
2. Add Input to Step with source + type
3. Verify dropdown excludes current step from destinations
4. Edit/Delete Outputs and Inputs
5. Verify cascade delete

---

## 🎯 Phase 5: Enhanced UI & Deep API

**Goal:** Complete UI polish and full nested JSON API

**Estimated Time:** 30 minutes

### 5.1 Accordion UI (Show Page)

**File:** `templates/treeflow/show.html.twig` (Final version)

**Features:**
- Bootstrap Accordion for collapsible Steps
- Drag handles for reordering (Sortable.js integration)
- Nested hierarchy clearly visible
- Color-coded sections (Questions=blue, Outputs=green, Inputs=yellow)
- Empty states for each section

```twig
<div class="accordion" id="stepsAccordion">
    {% for step in treeFlow.steps %}
        <div class="accordion-item infinity-card">
            <h2 class="accordion-header">
                <button class="accordion-button {{ loop.first ? '' : 'collapsed' }}"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#step-{{ step.id }}">
                    <span class="drag-handle me-2">
                        <i class="bi bi-grip-vertical"></i>
                    </span>
                    <strong>{{ step.name }}</strong>
                    {% if step.first %}
                        <span class="badge bg-success ms-2">FIRST</span>
                    {% endif %}
                    <span class="badge bg-secondary ms-2">
                        {{ step.questions|length }} Questions
                    </span>
                </button>
            </h2>
            <div id="step-{{ step.id }}"
                 class="accordion-collapse collapse {{ loop.first ? 'show' : '' }}">
                <div class="accordion-body">
                    {# Step details, Questions, Outputs, Inputs #}
                    {# ... previous content ... #}
                </div>
            </div>
        </div>
    {% endfor %}
</div>
```

**Add Sortable.js:**
```twig
<script>
// Enable drag-and-drop reordering
const sortable = new Sortable(document.getElementById('stepsAccordion'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function(evt) {
        // Send reorder request to backend
        const stepOrder = [...document.querySelectorAll('.accordion-item')]
            .map((el, index) => ({
                id: el.dataset.stepId,
                order: index
            }));

        fetch('/treeflow/{{ treeFlow.id }}/step/reorder', {
            method: 'POST',
            body: JSON.stringify({ steps: stepOrder })
        });
    }
});
</script>
```

---

### 5.2 Deep JSON API

**File:** `src/Controller/TreeFlowController.php`

**Update `entityToArray()` method:**
```php
protected function entityToArray(object $entity): array
{
    assert($entity instanceof TreeFlow);

    return [
        'id' => $entity->getId()?->toString(),
        'name' => $entity->getName(),
        'version' => $entity->getVersion(),
        'active' => $entity->isActive(),
        'organizationId' => $entity->getOrganization()->getId()?->toString(),
        'organizationName' => $entity->getOrganization()->getName(),
        'stepsCount' => $entity->getSteps()->count(),
        'createdAt' => $entity->getCreatedAt()->format('c'),
        'createdAtFormatted' => $entity->getCreatedAt()->format('M d, Y'),
        'updatedAtFormatted' => $entity->getUpdatedAt()->format('M d, Y'),
        'createdByName' => $entity->getCreatedBy()?->getName(),

        // DEEP NESTED DATA
        'steps' => array_map(function(Step $step) {
            return [
                'id' => $step->getId()?->toString(),
                'name' => $step->getName(),
                'first' => $step->isFirst(),
                'objective' => $step->getObjective(),
                'prompt' => $step->getPrompt(),

                // Questions with FewShots
                'questions' => array_map(function(Question $q) {
                    return [
                        'id' => $q->getId()?->toString(),
                        'name' => $q->getName(),
                        'prompt' => $q->getPrompt(),
                        'objective' => $q->getObjective(),
                        'importance' => $q->getImportance(),
                        'viewOrder' => $q->getViewOrder(),

                        'examples' => array_map(function(FewShotExample $ex) {
                            return [
                                'id' => $ex->getId()?->toString(),
                                'type' => $ex->getType()->value,
                                'name' => $ex->getName(),
                                'prompt' => $ex->getPrompt(),
                                'description' => $ex->getDescription(),
                            ];
                        }, $q->getExamples()->toArray()),
                    ];
                }, $step->getQuestions()->toArray()),

                // Outputs
                'outputs' => array_map(function(StepOutput $out) {
                    return [
                        'id' => $out->getId()?->toString(),
                        'name' => $out->getName(),
                        'description' => $out->getDescription(),
                        'conditional' => $out->getConditional(),
                        'destinationStepId' => $out->getDestinationStep()?->getId()?->toString(),
                        'destinationStepName' => $out->getDestinationStep()?->getName(),
                    ];
                }, $step->getOutputs()->toArray()),

                // Inputs
                'inputs' => array_map(function(StepInput $in) {
                    return [
                        'id' => $in->getId()?->toString(),
                        'name' => $in->getName(),
                        'type' => $in->getType()->value,
                        'sourceStepId' => $in->getSourceStep()?->getId()?->toString(),
                        'sourceStepName' => $in->getSourceStep()?->getName(),
                        'prompt' => $in->getPrompt(),
                    ];
                }, $step->getInputs()->toArray()),
            ];
        }, $entity->getSteps()->toArray()),
    ];
}
```

---

### 5.3 Performance Optimization

**Add Doctrine Query Optimization:**
```php
// In TreeFlowController::show()
$treeFlow = $this->treeFlowRepository->createQueryBuilder('t')
    ->where('t.id = :id')
    ->setParameter('id', $id)
    ->leftJoin('t.steps', 's')
    ->leftJoin('s.questions', 'q')
    ->leftJoin('q.examples', 'e')
    ->leftJoin('s.outputs', 'o')
    ->leftJoin('s.inputs', 'i')
    ->addSelect('s', 'q', 'e', 'o', 'i')
    ->getQuery()
    ->getOneOrNullResult();
```

This reduces N+1 queries from potentially hundreds to a single optimized query.

---

## ✅ Phase 5 Deliverables

- [ ] Accordion UI with collapsible Steps
- [ ] Drag-and-drop reordering (Sortable.js)
- [ ] Deep JSON API with full hierarchy
- [ ] Performance optimization (eager loading)
- [ ] Color-coded sections
- [ ] Empty states
- [ ] Responsive design verified

**Testing:**
1. Show page loads all data with single query
2. Accordion expands/collapses correctly
3. Drag-and-drop reorders Steps
4. API returns complete nested JSON
5. Large TreeFlows (10+ steps, 50+ questions) load quickly

---

## 🎯 Phase 6: Polish & Testing

**Goal:** Final touches, comprehensive testing, documentation

**Estimated Time:** 30 minutes

### 6.1 Error Handling

**Add validation messages:**
```yaml
validation:
  treeflow:
    name_required: TreeFlow name is required
    name_too_short: Name must be at least 2 characters
    name_too_long: Name cannot exceed 255 characters

  step:
    name_required: Step name is required
    cannot_delete_first: Cannot delete the first step

  question:
    importance_range: Importance must be between 1 and 10

  output:
    destination_required: Destination step is required

  input:
    type_required: Input type is required
```

---

### 6.2 User Feedback

**Enhanced Flash Messages:**
```php
// In controllers, after operations
$this->addFlash('success', $this->translator->trans('treeflow.flash.created_successfully', [
    '%name%' => $treeFlow->getName(),
    '%version%' => $treeFlow->getVersion(),
]));
```

**Loading States:**
```twig
{# Add to forms #}
<button type="submit"
        class="btn infinity-btn-primary"
        data-loading-text="{{ 'button.saving'|trans }}">
    {{ 'button.save'|trans }}
</button>
```

---

### 6.3 Testing Checklist

**Security Tests:**
- [ ] Regular user cannot access TreeFlow CRUD
- [ ] Org admin can only CRUD own organization's TreeFlows
- [ ] System admin can CRUD all TreeFlows
- [ ] Cannot delete TreeFlow from different organization
- [ ] Cannot edit Steps from different organization

**Functionality Tests:**
- [ ] Create TreeFlow → version starts at "1"
- [ ] Edit TreeFlow → version increments
- [ ] Delete TreeFlow → cascades to all nested entities
- [ ] Create Step → FirstStepSubscriber works
- [ ] Reorder Questions → viewOrder updates
- [ ] Add FewShot → type validation works
- [ ] Add Output with destination → dropdown excludes current step
- [ ] Add Input with source → type enum works
- [ ] Deep JSON API returns complete hierarchy

**UI Tests:**
- [ ] Grid/List/Table views work
- [ ] Modal forms open/close correctly
- [ ] Accordion expands/collapses
- [ ] Drag-and-drop reordering works
- [ ] Empty states show correctly
- [ ] Flash messages appear
- [ ] Responsive on mobile

**Performance Tests:**
- [ ] Large TreeFlow (50+ questions) loads in <2s
- [ ] API returns 10 TreeFlows in <500ms
- [ ] Single optimized query for show page

---

### 6.4 Documentation

**Add API documentation:**

**File:** `docs/api/treeflow.md`
```markdown
# TreeFlow API

## GET /treeflow/api/search

Returns paginated list of TreeFlows with complete nested data.

### Query Parameters
- `q` (string): Search query
- `page` (int): Page number (default: 1)
- `pageSize` (int): Items per page (default: 20)
- `sortBy` (string): Sort field (default: name)
- `sortOrder` (string): asc/desc (default: asc)

### Response
See Phase 5.2 for complete response structure.

### Security
Requires ROLE_ORGANIZATION_ADMIN or higher.
```

---

## ✅ Phase 6 Deliverables

- [ ] Enhanced error messages
- [ ] Loading states on buttons
- [ ] Complete testing checklist
- [ ] API documentation
- [ ] Performance verified
- [ ] Security audited

---

## 📊 Summary

### Total Files Created/Modified

**Entities (3):**
- Question.php (add viewOrder)
- TreeFlow.php (add version auto-increment)
- Migration

**Controllers (6):**
- TreeFlowController.php
- StepController.php
- QuestionController.php
- FewShotExampleController.php
- StepOutputController.php
- StepInputController.php

**Forms (6):**
- TreeFlowFormType.php
- StepFormType.php
- QuestionFormType.php
- FewShotExampleFormType.php
- StepOutputFormType.php
- StepInputFormType.php

**Security (1):**
- TreeFlowVoter.php

**Templates (12):**
- index.html.twig
- show.html.twig
- new.html.twig
- edit.html.twig
- _form_modal.html.twig
- step/_form_modal.html.twig
- question/_form_modal.html.twig
- fewshot/_form_modal.html.twig
- output/_form_modal.html.twig
- input/_form_modal.html.twig
- base.html.twig (navigation)
- messages.en.yaml (translations)

**Total: ~30 files**

---

## 🎯 Success Criteria

✅ **Phase 1:** TreeFlow basic CRUD working, version auto-increments
✅ **Phase 2:** Can manage Steps within TreeFlows
✅ **Phase 3:** Can manage Questions and FewShots
✅ **Phase 4:** Can manage Outputs and Inputs
✅ **Phase 5:** Beautiful accordion UI, deep JSON API
✅ **Phase 6:** All tests pass, documentation complete

---

## 🚀 Getting Started

```bash
# Start with Phase 1
cd /home/user/inf/app

# Update entities
# Edit src/Entity/Question.php - add viewOrder
# Edit src/Entity/TreeFlow.php - add version auto-increment

# Generate migration
docker-compose exec app php bin/console make:migration
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Create TreeFlowVoter
# Create TreeFlowFormType
# Create TreeFlowController
# Create templates
# Add translations
# Update navigation

# Test Phase 1
# Visit https://localhost/treeflow
# Create a TreeFlow
# Edit it → verify version increments
# Delete it

# Continue to Phase 2...
```

---

## 📞 Support

If you encounter issues during implementation:

1. Check entity relationships are correct
2. Verify security voter permissions
3. Ensure organization is auto-set from current user
4. Check cascade operations in entity annotations
5. Verify Turbo Frame targets match controller responses

Good luck! 🚀
