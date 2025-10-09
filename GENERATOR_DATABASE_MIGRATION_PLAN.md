# Generator Database Migration Plan
## Abandon CSV, Embrace Full Database-Driven Architecture

**Goal**: Transform the Generator system from CSV-based to a fully database-driven solution with a modern web UI.

---

## 🎯 Vision

Replace manual CSV editing with a sleek web-based admin panel where you can:
- ✅ Manage entities and properties visually
- ✅ Build relationships with drag-and-drop
- ✅ Validate configurations in real-time
- ✅ Preview generated code before creation
- ✅ Track changes with version history
- ✅ Import/Export for backup and sharing

---

## 📊 Phase 1: Database Schema Design (Day 1)

### New Entities

#### **GeneratorEntity** (replaces EntityNew.csv)
```php
#[ORM\Entity(repositoryClass: GeneratorEntityRepository::class)]
#[ApiResource]
class GeneratorEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    // Multi-tenant
    #[ORM\ManyToOne(targetEntity: Organization::class)]
    private Organization $organization;

    // Basic Information (5 columns)
    #[ORM\Column(length: 100)]
    private string $entityName;           // e.g., "Contact"

    #[ORM\Column(length: 100)]
    private string $entityLabel;          // e.g., "Contact"

    #[ORM\Column(length: 100)]
    private string $pluralLabel;          // e.g., "Contacts"

    #[ORM\Column(length: 50)]
    private string $icon;                 // e.g., "bi-person"

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // Multi-Tenancy
    #[ORM\Column]
    private bool $hasOrganization = true;

    // API Configuration (10 columns)
    #[ORM\Column]
    private bool $apiEnabled = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $operations = null;    // ['GetCollection', 'Get', 'Post', ...]

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $security = null;     // "is_granted('ROLE_USER')"

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $normalizationContext = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $denormalizationContext = null;

    #[ORM\Column]
    private bool $paginationEnabled = true;

    #[ORM\Column]
    private int $itemsPerPage = 30;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $order = null;         // {"name": "asc"}

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $searchableFields = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $filterableFields = null;

    // Security & Authorization (2 columns)
    #[ORM\Column]
    private bool $voterEnabled = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $voterAttributes = null; // ['VIEW', 'EDIT', 'DELETE']

    // Form Configuration
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formTheme = 'bootstrap_5_layout.html.twig';

    // UI Templates (3 columns)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $indexTemplate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formTemplate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $showTemplate = null;

    // Navigation (2 columns)
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $menuGroup = null;

    #[ORM\Column]
    private int $menuOrder = 100;

    // Testing
    #[ORM\Column]
    private bool $testEnabled = true;

    // Relationships
    #[ORM\OneToMany(mappedBy: 'entity', targetEntity: GeneratorProperty::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['propertyOrder' => 'ASC'])]
    private Collection $properties;

    // Version Control
    #[ORM\Column]
    private int $version = 1;

    #[ORM\Column]
    private bool $isGenerated = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastGeneratedAt = null;

    // Audit fields
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;
}
```

#### **GeneratorProperty** (replaces PropertyNew.csv)
```php
#[ORM\Entity(repositoryClass: GeneratorPropertyRepository::class)]
#[ApiResource]
class GeneratorProperty
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    // Parent entity
    #[ORM\ManyToOne(targetEntity: GeneratorEntity::class, inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: false)]
    private GeneratorEntity $entity;

    // Basic Information (4 columns)
    #[ORM\Column(length: 100)]
    private string $propertyName;         // e.g., "email"

    #[ORM\Column(length: 100)]
    private string $propertyLabel;        // e.g., "Email Address"

    #[ORM\Column(length: 50)]
    private string $propertyType;         // e.g., "string", "integer"

    #[ORM\Column]
    private int $propertyOrder = 0;       // Display order

    // Database Configuration (6 columns)
    #[ORM\Column]
    private bool $nullable = false;

    #[ORM\Column(nullable: true)]
    private ?int $length = null;

    #[ORM\Column(nullable: true)]
    private ?int $precision = null;

    #[ORM\Column(nullable: true)]
    private ?int $scale = null;

    #[ORM\Column]
    private bool $unique = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $defaultValue = null;

    // Relationships (8 columns)
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $relationshipType = null; // ManyToOne, OneToMany, ManyToMany

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $targetEntity = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $inversedBy = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mappedBy = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $cascade = null;

    #[ORM\Column]
    private bool $orphanRemoval = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $fetch = 'LAZY';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $orderBy = null;

    // Validation (2 columns)
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $validationRules = null; // ['NotBlank', 'Email']

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $validationMessage = null;

    // Form Configuration (5 columns)
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $formType = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $formOptions = null;

    #[ORM\Column]
    private bool $formRequired = false;

    #[ORM\Column]
    private bool $formReadOnly = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $formHelp = null;

    // UI Display (6 columns)
    #[ORM\Column]
    private bool $showInList = true;

    #[ORM\Column]
    private bool $showInDetail = true;

    #[ORM\Column]
    private bool $showInForm = true;

    #[ORM\Column]
    private bool $sortable = false;

    #[ORM\Column]
    private bool $searchable = false;

    #[ORM\Column]
    private bool $filterable = false;

    // API Configuration (3 columns)
    #[ORM\Column]
    private bool $apiReadable = true;

    #[ORM\Column]
    private bool $apiWritable = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiGroups = null;

    // Localization (2 columns)
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $translationKey = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $formatPattern = null;

    // Fixtures (2 columns)
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $fixtureType = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $fixtureOptions = null;

    // Audit fields
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;
}
```

#### **GeneratorHistory** (Version Control)
```php
#[ORM\Entity]
class GeneratorHistory
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: GeneratorEntity::class)]
    private GeneratorEntity $entity;

    #[ORM\Column]
    private int $version;

    #[ORM\Column(type: 'json')]
    private array $snapshot; // Full entity + properties JSON

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $changeDescription = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $changedBy;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;
}
```

---

## 🔧 Phase 2: Service Layer Refactoring (Day 2-3)

### Replace CSV Parser with Database Parser

**New Service: `DatabaseDefinitionService`**

```php
namespace App\Service\Generator\Database;

class DatabaseDefinitionService
{
    public function __construct(
        private GeneratorEntityRepository $entityRepository,
        private GeneratorPropertyRepository $propertyRepository
    ) {}

    /**
     * Get all entity definitions (replaces CSV parsing)
     */
    public function getAllDefinitions(?string $entityName = null): array
    {
        $entities = $entityName
            ? [$this->entityRepository->findOneBy(['entityName' => $entityName])]
            : $this->entityRepository->findAll();

        return array_map(
            fn(GeneratorEntity $e) => $this->convertToDto($e),
            $entities
        );
    }

    /**
     * Convert database entity to DTO (maintains compatibility)
     */
    private function convertToDto(GeneratorEntity $entity): EntityDefinitionDto
    {
        $dto = new EntityDefinitionDto();
        $dto->entityName = $entity->getEntityName();
        $dto->entityLabel = $entity->getEntityLabel();
        $dto->pluralLabel = $entity->getPluralLabel();
        // ... map all 25 fields

        // Convert properties
        $dto->properties = array_map(
            fn(GeneratorProperty $p) => $this->convertPropertyToDto($p),
            $entity->getProperties()->toArray()
        );

        return $dto;
    }

    private function convertPropertyToDto(GeneratorProperty $prop): PropertyDefinitionDto
    {
        $dto = new PropertyDefinitionDto();
        $dto->propertyName = $prop->getPropertyName();
        $dto->propertyLabel = $prop->getPropertyLabel();
        // ... map all 38 fields

        return $dto;
    }
}
```

### Update All Generators

**Before (CSV-based):**
```php
public function __construct(
    private CsvParserService $csvParser
) {}
```

**After (Database-based):**
```php
public function __construct(
    private DatabaseDefinitionService $definitionService
) {}
```

---

## 🎨 Phase 3: Admin UI (Day 4-6)

### Controller: `GeneratorAdminController`

```php
#[Route('/admin/generator', name: 'admin_generator_')]
class GeneratorAdminController extends AbstractController
{
    // Dashboard
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $entities = $this->entityRepository->findBy(
            ['organization' => $this->getUser()->getOrganization()],
            ['menuOrder' => 'ASC']
        );

        return $this->render('admin/generator/index.html.twig', [
            'entities' => $entities,
            'stats' => $this->getStats(),
        ]);
    }

    // Create Entity
    #[Route('/entity/new', name: 'entity_new')]
    public function newEntity(Request $request): Response
    {
        $entity = new GeneratorEntity();
        $form = $this->createForm(GeneratorEntityType::class, $entity);

        // Handle form submission...

        return $this->render('admin/generator/entity_form.html.twig', [
            'form' => $form,
        ]);
    }

    // Edit Entity
    #[Route('/entity/{id}/edit', name: 'entity_edit')]
    public function editEntity(GeneratorEntity $entity, Request $request): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $entity);

        $form = $this->createForm(GeneratorEntityType::class, $entity);

        // Handle form submission...

        return $this->render('admin/generator/entity_form.html.twig', [
            'form' => $form,
            'entity' => $entity,
        ]);
    }

    // Manage Properties
    #[Route('/entity/{id}/properties', name: 'entity_properties')]
    public function manageProperties(GeneratorEntity $entity): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $entity);

        return $this->render('admin/generator/properties.html.twig', [
            'entity' => $entity,
            'properties' => $entity->getProperties(),
        ]);
    }

    // Add Property (AJAX)
    #[Route('/entity/{id}/property/new', name: 'property_new', methods: ['POST'])]
    public function newProperty(GeneratorEntity $entity, Request $request): Response
    {
        $property = new GeneratorProperty();
        $property->setEntity($entity);

        $form = $this->createForm(GeneratorPropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($property);
            $this->em->flush();

            return $this->render('admin/generator/_property_row.html.twig', [
                'property' => $property,
            ]);
        }

        return $this->render('admin/generator/_property_form.html.twig', [
            'form' => $form,
        ]);
    }

    // Generate Code
    #[Route('/entity/{id}/generate', name: 'entity_generate', methods: ['POST'])]
    public function generate(GeneratorEntity $entity): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $entity);

        // Get entity definition from database
        $definition = $this->definitionService->getDefinition($entity->getId());

        // Run all generators
        $results = $this->orchestrator->generate($definition);

        // Update generation timestamp
        $entity->setIsGenerated(true);
        $entity->setLastGeneratedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->addFlash('success', 'Entity generated successfully!');

        return $this->redirectToRoute('admin_generator_entity_edit', [
            'id' => $entity->getId()
        ]);
    }

    // Preview Code (AJAX)
    #[Route('/entity/{id}/preview', name: 'entity_preview')]
    public function preview(GeneratorEntity $entity): Response
    {
        $definition = $this->definitionService->getDefinition($entity->getId());

        // Generate previews without writing files
        $previews = [
            'entity' => $this->entityGenerator->preview($definition),
            'controller' => $this->controllerGenerator->preview($definition),
            'form' => $this->formGenerator->preview($definition),
            'template' => $this->templateGenerator->preview($definition),
        ];

        return $this->json($previews);
    }

    // Version History
    #[Route('/entity/{id}/history', name: 'entity_history')]
    public function history(GeneratorEntity $entity): Response
    {
        $history = $this->historyRepository->findBy(
            ['entity' => $entity],
            ['createdAt' => 'DESC']
        );

        return $this->render('admin/generator/history.html.twig', [
            'entity' => $entity,
            'history' => $history,
        ]);
    }

    // Restore Version
    #[Route('/history/{id}/restore', name: 'history_restore', methods: ['POST'])]
    public function restoreVersion(GeneratorHistory $history): Response
    {
        $snapshot = $history->getSnapshot();

        // Restore entity from snapshot
        $this->restoreService->restore($history->getEntity(), $snapshot);

        $this->addFlash('success', 'Version restored successfully!');

        return $this->redirectToRoute('admin_generator_entity_edit', [
            'id' => $history->getEntity()->getId()
        ]);
    }
}
```

### Modern UI Templates

**Dashboard: `admin/generator/index.html.twig`**

```twig
{% extends 'base.html.twig' %}

{% block title %}Generator Admin{% endblock %}

{% block body %}
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-gear-fill me-2"></i>Generator Admin</h1>
        <a href="{{ path('admin_generator_entity_new') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Entity
        </a>
    </div>

    {# Stats Cards #}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Entities</h5>
                    <h2>{{ stats.total }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Generated</h5>
                    <h2>{{ stats.generated }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Draft</h5>
                    <h2>{{ stats.draft }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Properties</h5>
                    <h2>{{ stats.properties }}</h2>
                </div>
            </div>
        </div>
    </div>

    {# Entities Table #}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Entities</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover" id="entitiesTable">
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Entity</th>
                        <th>Properties</th>
                        <th>Menu Group</th>
                        <th>Status</th>
                        <th>Last Generated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {% for entity in entities %}
                    <tr>
                        <td><i class="{{ entity.icon }} fs-4"></i></td>
                        <td>
                            <strong>{{ entity.entityLabel }}</strong><br>
                            <small class="text-muted">{{ entity.entityName }}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                {{ entity.properties|length }} properties
                            </span>
                        </td>
                        <td>{{ entity.menuGroup ?? '-' }}</td>
                        <td>
                            {% if entity.isGenerated %}
                                <span class="badge bg-success">Generated</span>
                            {% else %}
                                <span class="badge bg-warning">Draft</span>
                            {% endif %}
                        </td>
                        <td>
                            {% if entity.lastGeneratedAt %}
                                {{ entity.lastGeneratedAt|date('Y-m-d H:i') }}
                            {% else %}
                                <span class="text-muted">Never</span>
                            {% endif %}
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ path('admin_generator_entity_edit', {id: entity.id}) }}"
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ path('admin_generator_entity_properties', {id: entity.id}) }}"
                                   class="btn btn-outline-secondary" title="Properties">
                                    <i class="bi bi-list-ul"></i>
                                </a>
                                <button class="btn btn-outline-info preview-entity"
                                        data-entity-id="{{ entity.id }}" title="Preview">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-success generate-entity"
                                        data-entity-id="{{ entity.id }}" title="Generate">
                                    <i class="bi bi-play-fill"></i>
                                </button>
                                <a href="{{ path('admin_generator_entity_history', {id: entity.id}) }}"
                                   class="btn btn-outline-warning" title="History">
                                    <i class="bi bi-clock-history"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    </div>
</div>

{# Preview Modal #}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Code Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="previewTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#entity-preview">Entity</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#controller-preview">Controller</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#form-preview">Form</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#template-preview">Template</a>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="previewContent">
                    <div class="tab-pane fade show active" id="entity-preview">
                        <pre><code class="language-php"></code></pre>
                    </div>
                    <div class="tab-pane fade" id="controller-preview">
                        <pre><code class="language-php"></code></pre>
                    </div>
                    <div class="tab-pane fade" id="form-preview">
                        <pre><code class="language-php"></code></pre>
                    </div>
                    <div class="tab-pane fade" id="template-preview">
                        <pre><code class="language-twig"></code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{% block javascripts %}
    {{ parent() }}
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-twig.min.js"></script>
    <script>
        // Preview entity code
        document.querySelectorAll('.preview-entity').forEach(btn => {
            btn.addEventListener('click', async () => {
                const entityId = btn.dataset.entityId;
                const response = await fetch(`/admin/generator/entity/${entityId}/preview`);
                const previews = await response.json();

                document.querySelector('#entity-preview code').textContent = previews.entity;
                document.querySelector('#controller-preview code').textContent = previews.controller;
                document.querySelector('#form-preview code').textContent = previews.form;
                document.querySelector('#template-preview code').textContent = previews.template;

                Prism.highlightAll();

                new bootstrap.Modal(document.getElementById('previewModal')).show();
            });
        });

        // Generate entity
        document.querySelectorAll('.generate-entity').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Generate code for this entity?')) return;

                const entityId = btn.dataset.entityId;
                const response = await fetch(`/admin/generator/entity/${entityId}/generate`, {
                    method: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });

                if (response.ok) {
                    location.reload();
                }
            });
        });
    </script>
{% endblock %}
{% endblock %}
```

**Property Manager: `admin/generator/properties.html.twig`**

```twig
{% extends 'base.html.twig' %}

{% block body %}
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="{{ entity.icon }} me-2"></i>
            {{ entity.entityLabel }} - Properties
        </h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
            <i class="bi bi-plus-lg me-1"></i> Add Property
        </button>
    </div>

    {# Drag-and-drop sortable properties table #}
    <div class="card">
        <div class="card-body">
            <table class="table" id="propertiesTable">
                <thead>
                    <tr>
                        <th width="30"><i class="bi bi-arrows-move"></i></th>
                        <th>Property</th>
                        <th>Type</th>
                        <th>Nullable</th>
                        <th>Validation</th>
                        <th>Display</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sortableProperties">
                    {% for property in properties %}
                    <tr data-property-id="{{ property.id }}">
                        <td class="handle"><i class="bi bi-grip-vertical"></i></td>
                        <td>
                            <strong>{{ property.propertyLabel }}</strong><br>
                            <small class="text-muted">{{ property.propertyName }}</small>
                        </td>
                        <td>
                            <code>{{ property.propertyType }}</code>
                            {% if property.relationshipType %}
                                <span class="badge bg-info">{{ property.relationshipType }}</span>
                            {% endif %}
                        </td>
                        <td>
                            {% if property.nullable %}
                                <i class="bi bi-check-circle text-success"></i>
                            {% else %}
                                <i class="bi bi-x-circle text-danger"></i>
                            {% endif %}
                        </td>
                        <td>
                            {% if property.validationRules %}
                                {% for rule in property.validationRules %}
                                    <span class="badge bg-secondary">{{ rule }}</span>
                                {% endfor %}
                            {% else %}
                                <span class="text-muted">-</span>
                            {% endif %}
                        </td>
                        <td>
                            {% if property.showInList %}<i class="bi bi-list-ul" title="List"></i>{% endif %}
                            {% if property.showInForm %}<i class="bi bi-ui-checks" title="Form"></i>{% endif %}
                            {% if property.showInDetail %}<i class="bi bi-eye" title="Detail"></i>{% endif %}
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary edit-property"
                                        data-property-id="{{ property.id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger delete-property"
                                        data-property-id="{{ property.id }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    </div>
</div>

{# Add Property Modal with tabbed form #}
<div class="modal fade" id="addPropertyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Property</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#basic">Basic</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#database">Database</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#relationship">Relationship</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#validation">Validation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#ui">UI/Form</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#api">API</a>
                    </li>
                </ul>

                <form id="propertyForm">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="basic">
                            {# Basic property fields #}
                        </div>
                        <div class="tab-pane fade" id="database">
                            {# Database config fields #}
                        </div>
                        {# ... other tabs #}
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveProperty">Save Property</button>
            </div>
        </div>
    </div>
</div>

{% block javascripts %}
    {{ parent() }}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        // Make properties sortable
        new Sortable(document.getElementById('sortableProperties'), {
            handle: '.handle',
            animation: 150,
            onEnd: async (evt) => {
                const order = [...document.querySelectorAll('#sortableProperties tr')]
                    .map((tr, index) => ({
                        id: tr.dataset.propertyId,
                        order: index
                    }));

                await fetch('/admin/generator/properties/reorder', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({order})
                });
            }
        });
    </script>
{% endblock %}
{% endblock %}
```

---

## 🔄 Phase 4: Migration Strategy (Day 7)

### CSV to Database Import Command

```php
namespace App\Command;

#[AsCommand(name: 'app:import-csv-to-database')]
class ImportCsvToDatabaseCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Parse existing CSV files
        $csvEntities = $this->csvParser->parseAll();

        $io->title('Importing CSV to Database');
        $io->progressStart(count($csvEntities));

        foreach ($csvEntities as $csvEntity) {
            // Create GeneratorEntity
            $entity = new GeneratorEntity();
            $entity->setEntityName($csvEntity->entityName);
            $entity->setEntityLabel($csvEntity->entityLabel);
            // ... map all fields

            // Create GeneratorProperty entities
            foreach ($csvEntity->properties as $csvProperty) {
                $property = new GeneratorProperty();
                $property->setEntity($entity);
                $property->setPropertyName($csvProperty->propertyName);
                // ... map all fields

                $entity->addProperty($property);
            }

            $this->em->persist($entity);
            $io->progressAdvance();
        }

        $this->em->flush();

        $io->progressFinish();
        $io->success('CSV data imported successfully!');

        // Backup CSV files
        $this->filesystem->copy(
            'config/EntityNew.csv',
            'var/backup/EntityNew_' . date('Y-m-d') . '.csv'
        );

        return Command::SUCCESS;
    }
}
```

### Rollback Support

```php
#[AsCommand(name: 'app:export-database-to-csv')]
class ExportDatabaseToCsvCommand extends Command
{
    /**
     * Export database definitions back to CSV (for backup/compatibility)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entities = $this->entityRepository->findAll();

        // Generate CSV content
        $csvContent = $this->csvExporter->export($entities);

        // Write to files
        file_put_contents('config/EntityNew.csv', $csvContent['entities']);
        file_put_contents('config/PropertyNew.csv', $csvContent['properties']);

        $io->success('Database exported to CSV successfully!');

        return Command::SUCCESS;
    }
}
```

---

## 🚀 Phase 5: Advanced Features (Day 8-10)

### Feature 1: Relationship Visualizer

**Interactive graph showing entity relationships**

```javascript
// Using vis.js or D3.js
const nodes = entities.map(e => ({
    id: e.id,
    label: e.entityLabel,
    icon: e.icon
}));

const edges = properties
    .filter(p => p.relationshipType)
    .map(p => ({
        from: p.entity.id,
        to: p.targetEntity.id,
        label: p.relationshipType
    }));

const network = new vis.Network(container, {nodes, edges}, options);
```

### Feature 2: Bulk Operations

```php
#[Route('/admin/generator/bulk-generate', name: 'bulk_generate', methods: ['POST'])]
public function bulkGenerate(Request $request): Response
{
    $entityIds = $request->request->all('entities');

    $results = [];
    foreach ($entityIds as $id) {
        $entity = $this->entityRepository->find($id);
        $definition = $this->definitionService->getDefinition($id);
        $results[] = $this->orchestrator->generate($definition);
    }

    return $this->json(['success' => true, 'results' => $results]);
}
```

### Feature 3: Templates/Presets

```php
#[ORM\Entity]
class GeneratorTemplate
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column]
    private string $name; // e.g., "E-commerce Product"

    #[ORM\Column(type: 'json')]
    private array $entityConfig;

    #[ORM\Column(type: 'json')]
    private array $propertiesConfig;

    #[ORM\Column]
    private bool $isPublic = false;
}

// Use template
#[Route('/admin/generator/template/{id}/apply', name: 'apply_template')]
public function applyTemplate(GeneratorTemplate $template): Response
{
    $entity = $this->templateService->instantiateTemplate($template);
    // ...
}
```

### Feature 4: Validation Builder UI

**Visual rule builder instead of text input**

```twig
<div class="validation-builder">
    <button class="btn btn-sm btn-outline-primary add-rule">
        <i class="bi bi-plus"></i> Add Rule
    </button>

    <div class="rules-list">
        <div class="rule-item">
            <select name="rule_type">
                <option value="NotBlank">Not Blank</option>
                <option value="Email">Email</option>
                <option value="Length">Length</option>
                <option value="Range">Range</option>
                <option value="Regex">Regex</option>
            </select>

            <div class="rule-options" data-rule="Length">
                <input type="number" name="min" placeholder="Min">
                <input type="number" name="max" placeholder="Max">
            </div>

            <button class="btn btn-sm btn-danger remove-rule">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>
```

### Feature 5: Import/Export

```php
// Export entity as JSON
#[Route('/admin/generator/entity/{id}/export', name: 'entity_export')]
public function exportEntity(GeneratorEntity $entity): Response
{
    $json = $this->serializer->serialize($entity, 'json', [
        'groups' => ['export']
    ]);

    return new JsonResponse(json_decode($json), 200, [
        'Content-Disposition' => 'attachment; filename="' . $entity->getEntityName() . '.json"'
    ]);
}

// Import entity from JSON
#[Route('/admin/generator/import', name: 'entity_import', methods: ['POST'])]
public function importEntity(Request $request): Response
{
    $file = $request->files->get('file');
    $json = file_get_contents($file->getPathname());

    $entity = $this->serializer->deserialize(
        $json,
        GeneratorEntity::class,
        'json'
    );

    $this->em->persist($entity);
    $this->em->flush();

    return $this->redirectToRoute('admin_generator_index');
}
```

---

## 📋 Implementation Checklist

### Week 1: Database & Core Services
- [ ] Create `GeneratorEntity` entity
- [ ] Create `GeneratorProperty` entity
- [ ] Create `GeneratorHistory` entity
- [ ] Create repositories
- [ ] Create `DatabaseDefinitionService`
- [ ] Update all Generator services to use new service
- [ ] Create migration from Entity/Property tables
- [ ] Write unit tests

### Week 2: Admin UI - Part 1
- [ ] Create `GeneratorAdminController`
- [ ] Create `GeneratorEntityType` form
- [ ] Create `GeneratorPropertyType` form
- [ ] Create dashboard template
- [ ] Create entity form template
- [ ] Create property manager template
- [ ] Add security voters
- [ ] Add translations

### Week 3: Admin UI - Part 2
- [ ] Implement AJAX property CRUD
- [ ] Add drag-and-drop sorting
- [ ] Create preview modal
- [ ] Add code syntax highlighting
- [ ] Implement bulk operations
- [ ] Add relationship visualizer
- [ ] Create version history UI
- [ ] Add import/export features

### Week 4: Migration & Testing
- [ ] Create CSV import command
- [ ] Create CSV export command (backup)
- [ ] Migrate existing CSV data to database
- [ ] Write integration tests
- [ ] Test all UI features
- [ ] Load testing (1000+ entities)
- [ ] Documentation updates
- [ ] User training

---

## 🎯 Benefits of Database Approach

### Developer Experience
✅ **Visual Entity Management** - No more CSV editing
✅ **Real-time Validation** - Catch errors before generation
✅ **Code Preview** - See what will be generated
✅ **Version Control** - Track all changes
✅ **Templates** - Reuse common patterns

### Technical Benefits
✅ **Type Safety** - Database constraints ensure data integrity
✅ **Relationships** - Proper foreign keys validate entity references
✅ **Multi-tenant** - Organization isolation built-in
✅ **API Ready** - Expose via API Platform for external tools
✅ **Scalability** - Query optimization, indexing, caching

### Operational Benefits
✅ **Backup/Restore** - Point-in-time recovery
✅ **Audit Trail** - Who changed what and when
✅ **Rollback** - Restore previous versions easily
✅ **Import/Export** - Share entity definitions
✅ **Search** - Find entities/properties quickly

---

## 🔒 Security Considerations

1. **Authorization**: Only ROLE_ADMIN can access Generator admin
2. **Organization Isolation**: Users only see their org's entities
3. **Validation**: Strict validation prevents code injection
4. **Backups**: Automatic snapshots before changes
5. **Audit Logging**: Track all modifications

---

## 📝 Migration Plan Summary

1. **Create new entities** (GeneratorEntity, GeneratorProperty, GeneratorHistory)
2. **Build service layer** (DatabaseDefinitionService)
3. **Update generators** to use database instead of CSV
4. **Create admin UI** with all CRUD operations
5. **Import CSV data** to database (one-time migration)
6. **Add advanced features** (preview, history, templates)
7. **Keep CSV export** for compatibility/backup

---

## 🚦 Getting Started

```bash
# 1. Run this plan review
cat GENERATOR_DATABASE_MIGRATION_PLAN.md

# 2. Start implementation
php bin/console make:entity GeneratorEntity

# 3. Or let's build it together - just say:
"Let's implement Phase 1: Database Schema"
```

**Ready to abandon CSV for good? Let's build this! 🚀**
