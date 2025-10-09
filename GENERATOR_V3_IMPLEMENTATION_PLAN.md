# 🚀 Generator V3: Visual Database Designer
## Complete Implementation Guide - Canvas-Based Entity & Relationship Management

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Database Schema](#database-schema)
3. [Code Reuse from TreeFlow](#code-reuse-from-treeflow)
4. [Backend Implementation](#backend-implementation)
5. [Frontend Implementation](#frontend-implementation)
6. [Features Specification](#features-specification)
7. [Implementation Steps](#implementation-steps)
8. [Testing Strategy](#testing-strategy)
9. [Deployment Guide](#deployment-guide)

---

## 🎯 Overview

### **What We're Building**

A visual database designer that allows ADMIN users to:
- Design entities visually on a canvas
- Define properties with full configuration
- Create relationships by dragging connections
- Auto-generate complete Symfony CRUD code
- Preview generated code before creation
- Manage all generator settings in a modern UI

### **Core Technologies**

- **Backend**: Symfony 7.3 + Doctrine ORM + PostgreSQL
- **Frontend**: Stimulus 3.x + SVG Canvas + Bootstrap 5.3
- **Architecture**: System-wide (no multi-tenant), ROLE_ADMIN only
- **Code Reuse**: 70% from existing TreeFlow canvas implementation

### **Key Features**

✅ **Visual Canvas**
- Drag entities to position
- Pan/Zoom canvas (mouse + touch)
- Auto-layout algorithm
- Fullscreen mode
- Canvas state persistence

✅ **Entity Management**
- Create/Edit/Delete entities
- Configure all 25 entity settings
- Icon selector
- API configuration
- Security settings

✅ **Property Management**
- Visual property editor
- 38+ configurable fields
- Drag-to-reorder properties
- Inline editing
- Validation rule builder

✅ **Relationship System**
- Drag property → entity to create relationship
- ManyToOne, OneToMany, ManyToMany, OneToOne
- Visual relationship lines (SVG)
- Color-coded by type
- Hover tooltips
- Click to delete

✅ **Code Generation**
- Preview before generate
- Generate single entity
- Bulk generation
- Track generation status
- View generated files

---

## 🗄️ Database Schema

### **1. GeneratorEntity** (Main Entity Table)

```php
<?php

declare(strict_types=1);

namespace App\Entity\Generator;

use App\Doctrine\UuidV7Generator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ORM\Entity(repositoryClass: GeneratorEntityRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    security: "is_granted('ROLE_ADMIN')",
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]
class GeneratorEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private Uuid $id;

    // ====================================
    // BASIC INFORMATION (5 fields)
    // ====================================

    #[ORM\Column(length: 100, unique: true)]
    private string $entityName;           // "Contact" (PascalCase)

    #[ORM\Column(length: 100)]
    private string $entityLabel;          // "Contact"

    #[ORM\Column(length: 100)]
    private string $pluralLabel;          // "Contacts"

    #[ORM\Column(length: 50)]
    private string $icon;                 // "bi-person"

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // ====================================
    // CANVAS POSITIONING (2 fields)
    // ====================================

    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    private int $canvasX = 100;

    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    private int $canvasY = 100;

    // ====================================
    // MULTI-TENANCY (1 field)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    private bool $hasOrganization = true;

    // ====================================
    // API CONFIGURATION (10 fields)
    // ====================================

    #[ORM\Column(options: ['default' => false])]
    private bool $apiEnabled = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiOperations = null;    // ['GetCollection', 'Get', 'Post', 'Put', 'Delete']

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $apiSecurity = null;     // "is_granted('ROLE_USER')"

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiNormalizationContext = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiDenormalizationContext = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $apiPaginationEnabled = true;

    #[ORM\Column(type: 'integer', options: ['default' => 30])]
    private int $apiItemsPerPage = 30;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiDefaultOrder = null;  // {"name": "asc"}

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiSearchableFields = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiFilterableFields = null;

    // ====================================
    // SECURITY (2 fields)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    private bool $voterEnabled = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $voterAttributes = null;  // ['VIEW', 'EDIT', 'DELETE', 'CREATE']

    // ====================================
    // FORM (1 field)
    // ====================================

    #[ORM\Column(length: 255, options: ['default' => 'bootstrap_5_layout.html.twig'])]
    private string $formTheme = 'bootstrap_5_layout.html.twig';

    // ====================================
    // UI TEMPLATES (3 fields)
    // ====================================

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customIndexTemplate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customFormTemplate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customShowTemplate = null;

    // ====================================
    // NAVIGATION (2 fields)
    // ====================================

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $menuGroup = null;       // "CRM", "System", "Reports"

    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    private int $menuOrder = 100;

    // ====================================
    // TESTING (1 field)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    private bool $testEnabled = true;

    // ====================================
    // RELATIONSHIPS
    // ====================================

    #[ORM\OneToMany(
        mappedBy: 'entity',
        targetEntity: GeneratorProperty::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['propertyOrder' => 'ASC'])]
    private Collection $properties;

    // ====================================
    // GENERATION STATUS (3 fields)
    // ====================================

    #[ORM\Column(options: ['default' => false])]
    private bool $isGenerated = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastGeneratedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lastGenerationLog = null;

    // ====================================
    // AUDIT (2 fields)
    // ====================================

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters...
    public function getId(): Uuid { return $this->id; }
    public function getEntityName(): string { return $this->entityName; }
    public function setEntityName(string $entityName): self { $this->entityName = $entityName; return $this; }
    public function getEntityLabel(): string { return $this->entityLabel; }
    public function setEntityLabel(string $entityLabel): self { $this->entityLabel = $entityLabel; return $this; }
    public function getPluralLabel(): string { return $this->pluralLabel; }
    public function setPluralLabel(string $pluralLabel): self { $this->pluralLabel = $pluralLabel; return $this; }
    public function getIcon(): string { return $this->icon; }
    public function setIcon(string $icon): self { $this->icon = $icon; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getCanvasX(): int { return $this->canvasX; }
    public function setCanvasX(int $canvasX): self { $this->canvasX = $canvasX; return $this; }
    public function getCanvasY(): int { return $this->canvasY; }
    public function setCanvasY(int $canvasY): self { $this->canvasY = $canvasY; return $this; }
    public function isHasOrganization(): bool { return $this->hasOrganization; }
    public function setHasOrganization(bool $hasOrganization): self { $this->hasOrganization = $hasOrganization; return $this; }
    public function isApiEnabled(): bool { return $this->apiEnabled; }
    public function setApiEnabled(bool $apiEnabled): self { $this->apiEnabled = $apiEnabled; return $this; }
    public function getApiOperations(): ?array { return $this->apiOperations; }
    public function setApiOperations(?array $apiOperations): self { $this->apiOperations = $apiOperations; return $this; }
    public function getApiSecurity(): ?string { return $this->apiSecurity; }
    public function setApiSecurity(?string $apiSecurity): self { $this->apiSecurity = $apiSecurity; return $this; }
    public function getApiNormalizationContext(): ?string { return $this->apiNormalizationContext; }
    public function setApiNormalizationContext(?string $apiNormalizationContext): self { $this->apiNormalizationContext = $apiNormalizationContext; return $this; }
    public function getApiDenormalizationContext(): ?string { return $this->apiDenormalizationContext; }
    public function setApiDenormalizationContext(?string $apiDenormalizationContext): self { $this->apiDenormalizationContext = $apiDenormalizationContext; return $this; }
    public function isApiPaginationEnabled(): bool { return $this->apiPaginationEnabled; }
    public function setApiPaginationEnabled(bool $apiPaginationEnabled): self { $this->apiPaginationEnabled = $apiPaginationEnabled; return $this; }
    public function getApiItemsPerPage(): int { return $this->apiItemsPerPage; }
    public function setApiItemsPerPage(int $apiItemsPerPage): self { $this->apiItemsPerPage = $apiItemsPerPage; return $this; }
    public function getApiDefaultOrder(): ?array { return $this->apiDefaultOrder; }
    public function setApiDefaultOrder(?array $apiDefaultOrder): self { $this->apiDefaultOrder = $apiDefaultOrder; return $this; }
    public function getApiSearchableFields(): ?array { return $this->apiSearchableFields; }
    public function setApiSearchableFields(?array $apiSearchableFields): self { $this->apiSearchableFields = $apiSearchableFields; return $this; }
    public function getApiFilterableFields(): ?array { return $this->apiFilterableFields; }
    public function setApiFilterableFields(?array $apiFilterableFields): self { $this->apiFilterableFields = $apiFilterableFields; return $this; }
    public function isVoterEnabled(): bool { return $this->voterEnabled; }
    public function setVoterEnabled(bool $voterEnabled): self { $this->voterEnabled = $voterEnabled; return $this; }
    public function getVoterAttributes(): ?array { return $this->voterAttributes; }
    public function setVoterAttributes(?array $voterAttributes): self { $this->voterAttributes = $voterAttributes; return $this; }
    public function getFormTheme(): string { return $this->formTheme; }
    public function setFormTheme(string $formTheme): self { $this->formTheme = $formTheme; return $this; }
    public function getCustomIndexTemplate(): ?string { return $this->customIndexTemplate; }
    public function setCustomIndexTemplate(?string $customIndexTemplate): self { $this->customIndexTemplate = $customIndexTemplate; return $this; }
    public function getCustomFormTemplate(): ?string { return $this->customFormTemplate; }
    public function setCustomFormTemplate(?string $customFormTemplate): self { $this->customFormTemplate = $customFormTemplate; return $this; }
    public function getCustomShowTemplate(): ?string { return $this->customShowTemplate; }
    public function setCustomShowTemplate(?string $customShowTemplate): self { $this->customShowTemplate = $customShowTemplate; return $this; }
    public function getMenuGroup(): ?string { return $this->menuGroup; }
    public function setMenuGroup(?string $menuGroup): self { $this->menuGroup = $menuGroup; return $this; }
    public function getMenuOrder(): int { return $this->menuOrder; }
    public function setMenuOrder(int $menuOrder): self { $this->menuOrder = $menuOrder; return $this; }
    public function isTestEnabled(): bool { return $this->testEnabled; }
    public function setTestEnabled(bool $testEnabled): self { $this->testEnabled = $testEnabled; return $this; }
    public function getProperties(): Collection { return $this->properties; }
    public function addProperty(GeneratorProperty $property): self { if (!$this->properties->contains($property)) { $this->properties->add($property); $property->setEntity($this); } return $this; }
    public function removeProperty(GeneratorProperty $property): self { if ($this->properties->removeElement($property)) { if ($property->getEntity() === $this) { $property->setEntity(null); } } return $this; }
    public function isGenerated(): bool { return $this->isGenerated; }
    public function setIsGenerated(bool $isGenerated): self { $this->isGenerated = $isGenerated; return $this; }
    public function getLastGeneratedAt(): ?\DateTimeImmutable { return $this->lastGeneratedAt; }
    public function setLastGeneratedAt(?\DateTimeImmutable $lastGeneratedAt): self { $this->lastGeneratedAt = $lastGeneratedAt; return $this; }
    public function getLastGenerationLog(): ?string { return $this->lastGenerationLog; }
    public function setLastGenerationLog(?string $lastGenerationLog): self { $this->lastGenerationLog = $lastGenerationLog; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
```

### **2. GeneratorProperty** (Properties Table)

```php
<?php

declare(strict_types=1);

namespace App\Entity\Generator;

use App\Doctrine\UuidV7Generator;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ORM\Entity(repositoryClass: GeneratorPropertyRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    security: "is_granted('ROLE_ADMIN')",
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]
class GeneratorProperty
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private Uuid $id;

    // ====================================
    // PARENT ENTITY
    // ====================================

    #[ORM\ManyToOne(targetEntity: GeneratorEntity::class, inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GeneratorEntity $entity;

    // ====================================
    // BASIC INFORMATION (4 fields)
    // ====================================

    #[ORM\Column(length: 100)]
    private string $propertyName;         // "emailAddress" (camelCase)

    #[ORM\Column(length: 100)]
    private string $propertyLabel;        // "Email Address"

    #[ORM\Column(length: 50)]
    private string $propertyType;         // "string", "integer", "datetime"

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $propertyOrder = 0;       // Display order

    // ====================================
    // DATABASE CONFIGURATION (6 fields)
    // ====================================

    #[ORM\Column(options: ['default' => false])]
    private bool $nullable = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $length = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $precision = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $scale = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $unique = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $defaultValue = null;

    // ====================================
    // RELATIONSHIPS (8 fields)
    // ====================================

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $relationshipType = null; // 'ManyToOne', 'OneToMany', 'ManyToMany', 'OneToOne'

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $targetEntity = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $inversedBy = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mappedBy = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $cascade = null;       // ['persist', 'remove']

    #[ORM\Column(options: ['default' => false])]
    private bool $orphanRemoval = false;

    #[ORM\Column(length: 20, nullable: true, options: ['default' => 'LAZY'])]
    private ?string $fetch = 'LAZY';      // 'LAZY', 'EAGER', 'EXTRA_LAZY'

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $orderBy = null;       // {"name": "ASC"}

    // ====================================
    // VALIDATION (2 fields)
    // ====================================

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $validationRules = null;  // ['NotBlank', 'Email', 'Length' => ['min' => 5]]

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $validationMessage = null;

    // ====================================
    // FORM CONFIGURATION (5 fields)
    // ====================================

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $formType = null;     // "EmailType", "TextType", "EntityType"

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $formOptions = null;   // {"attr": {"placeholder": "Enter email"}}

    #[ORM\Column(options: ['default' => false])]
    private bool $formRequired = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $formReadOnly = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $formHelp = null;

    // ====================================
    // UI DISPLAY (6 fields)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    private bool $showInList = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showInDetail = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showInForm = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $sortable = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $searchable = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $filterable = false;

    // ====================================
    // API CONFIGURATION (3 fields)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    private bool $apiReadable = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $apiWritable = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiGroups = null;     // ["contact:read", "contact:write"]

    // ====================================
    // LOCALIZATION (2 fields)
    // ====================================

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $translationKey = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $formatPattern = null;

    // ====================================
    // FIXTURES (2 fields)
    // ====================================

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $fixtureType = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $fixtureOptions = null;

    // ====================================
    // AUDIT (2 fields)
    // ====================================

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters... (all 38 fields)
    public function getId(): Uuid { return $this->id; }
    public function getEntity(): GeneratorEntity { return $this->entity; }
    public function setEntity(GeneratorEntity $entity): self { $this->entity = $entity; return $this; }
    public function getPropertyName(): string { return $this->propertyName; }
    public function setPropertyName(string $propertyName): self { $this->propertyName = $propertyName; return $this; }
    public function getPropertyLabel(): string { return $this->propertyLabel; }
    public function setPropertyLabel(string $propertyLabel): self { $this->propertyLabel = $propertyLabel; return $this; }
    public function getPropertyType(): string { return $this->propertyType; }
    public function setPropertyType(string $propertyType): self { $this->propertyType = $propertyType; return $this; }
    public function getPropertyOrder(): int { return $this->propertyOrder; }
    public function setPropertyOrder(int $propertyOrder): self { $this->propertyOrder = $propertyOrder; return $this; }
    public function isNullable(): bool { return $this->nullable; }
    public function setNullable(bool $nullable): self { $this->nullable = $nullable; return $this; }
    public function getLength(): ?int { return $this->length; }
    public function setLength(?int $length): self { $this->length = $length; return $this; }
    public function getPrecision(): ?int { return $this->precision; }
    public function setPrecision(?int $precision): self { $this->precision = $precision; return $this; }
    public function getScale(): ?int { return $this->scale; }
    public function setScale(?int $scale): self { $this->scale = $scale; return $this; }
    public function isUnique(): bool { return $this->unique; }
    public function setUnique(bool $unique): self { $this->unique = $unique; return $this; }
    public function getDefaultValue(): ?string { return $this->defaultValue; }
    public function setDefaultValue(?string $defaultValue): self { $this->defaultValue = $defaultValue; return $this; }
    public function getRelationshipType(): ?string { return $this->relationshipType; }
    public function setRelationshipType(?string $relationshipType): self { $this->relationshipType = $relationshipType; return $this; }
    public function getTargetEntity(): ?string { return $this->targetEntity; }
    public function setTargetEntity(?string $targetEntity): self { $this->targetEntity = $targetEntity; return $this; }
    public function getInversedBy(): ?string { return $this->inversedBy; }
    public function setInversedBy(?string $inversedBy): self { $this->inversedBy = $inversedBy; return $this; }
    public function getMappedBy(): ?string { return $this->mappedBy; }
    public function setMappedBy(?string $mappedBy): self { $this->mappedBy = $mappedBy; return $this; }
    public function getCascade(): ?array { return $this->cascade; }
    public function setCascade(?array $cascade): self { $this->cascade = $cascade; return $this; }
    public function isOrphanRemoval(): bool { return $this->orphanRemoval; }
    public function setOrphanRemoval(bool $orphanRemoval): self { $this->orphanRemoval = $orphanRemoval; return $this; }
    public function getFetch(): ?string { return $this->fetch; }
    public function setFetch(?string $fetch): self { $this->fetch = $fetch; return $this; }
    public function getOrderBy(): ?array { return $this->orderBy; }
    public function setOrderBy(?array $orderBy): self { $this->orderBy = $orderBy; return $this; }
    public function getValidationRules(): ?array { return $this->validationRules; }
    public function setValidationRules(?array $validationRules): self { $this->validationRules = $validationRules; return $this; }
    public function getValidationMessage(): ?string { return $this->validationMessage; }
    public function setValidationMessage(?string $validationMessage): self { $this->validationMessage = $validationMessage; return $this; }
    public function getFormType(): ?string { return $this->formType; }
    public function setFormType(?string $formType): self { $this->formType = $formType; return $this; }
    public function getFormOptions(): ?array { return $this->formOptions; }
    public function setFormOptions(?array $formOptions): self { $this->formOptions = $formOptions; return $this; }
    public function isFormRequired(): bool { return $this->formRequired; }
    public function setFormRequired(bool $formRequired): self { $this->formRequired = $formRequired; return $this; }
    public function isFormReadOnly(): bool { return $this->formReadOnly; }
    public function setFormReadOnly(bool $formReadOnly): self { $this->formReadOnly = $formReadOnly; return $this; }
    public function getFormHelp(): ?string { return $this->formHelp; }
    public function setFormHelp(?string $formHelp): self { $this->formHelp = $formHelp; return $this; }
    public function isShowInList(): bool { return $this->showInList; }
    public function setShowInList(bool $showInList): self { $this->showInList = $showInList; return $this; }
    public function isShowInDetail(): bool { return $this->showInDetail; }
    public function setShowInDetail(bool $showInDetail): self { $this->showInDetail = $showInDetail; return $this; }
    public function isShowInForm(): bool { return $this->showInForm; }
    public function setShowInForm(bool $showInForm): self { $this->showInForm = $showInForm; return $this; }
    public function isSortable(): bool { return $this->sortable; }
    public function setSortable(bool $sortable): self { $this->sortable = $sortable; return $this; }
    public function isSearchable(): bool { return $this->searchable; }
    public function setSearchable(bool $searchable): self { $this->searchable = $searchable; return $this; }
    public function isFilterable(): bool { return $this->filterable; }
    public function setFilterable(bool $filterable): self { $this->filterable = $filterable; return $this; }
    public function isApiReadable(): bool { return $this->apiReadable; }
    public function setApiReadable(bool $apiReadable): self { $this->apiReadable = $apiReadable; return $this; }
    public function isApiWritable(): bool { return $this->apiWritable; }
    public function setApiWritable(bool $apiWritable): self { $this->apiWritable = $apiWritable; return $this; }
    public function getApiGroups(): ?array { return $this->apiGroups; }
    public function setApiGroups(?array $apiGroups): self { $this->apiGroups = $apiGroups; return $this; }
    public function getTranslationKey(): ?string { return $this->translationKey; }
    public function setTranslationKey(?string $translationKey): self { $this->translationKey = $translationKey; return $this; }
    public function getFormatPattern(): ?string { return $this->formatPattern; }
    public function setFormatPattern(?string $formatPattern): self { $this->formatPattern = $formatPattern; return $this; }
    public function getFixtureType(): ?string { return $this->fixtureType; }
    public function setFixtureType(?string $fixtureType): self { $this->fixtureType = $fixtureType; return $this; }
    public function getFixtureOptions(): ?array { return $this->fixtureOptions; }
    public function setFixtureOptions(?array $fixtureOptions): self { $this->fixtureOptions = $fixtureOptions; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
```

### **3. GeneratorCanvasState** (Canvas View State)

```php
<?php

declare(strict_types=1);

namespace App\Entity\Generator;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GeneratorCanvasState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private int $id = 1; // Singleton - always ID 1

    #[ORM\Column(type: 'float', options: ['default' => 1.0])]
    private float $scale = 1.0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $offsetX = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $offsetY = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getScale(): float { return $this->scale; }
    public function setScale(float $scale): self { $this->scale = $scale; $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function getOffsetX(): int { return $this->offsetX; }
    public function setOffsetX(int $offsetX): self { $this->offsetX = $offsetX; $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function getOffsetY(): int { return $this->offsetY; }
    public function setOffsetY(int $offsetY): self { $this->offsetY = $offsetY; $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
```

---

## 🔄 Code Reuse from TreeFlow

### **What to Extract from TreeFlow**

We'll create a base canvas controller by extracting proven code from `app/assets/controllers/treeflow_canvas_controller.js`

#### **1. Pan & Zoom System** ✅
**From TreeFlow Lines: 2102-2303**

```javascript
// Extract these methods:
handleWheel(e) {
    e.preventDefault();
    const delta = e.deltaY > 0 ? 0.9 : 1.1;
    const newScale = Math.max(0.1, Math.min(3, this.scale * delta));

    const rect = this.canvasTarget.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    const mouseY = e.clientY - rect.top;

    this.offsetX = mouseX - (mouseX - this.offsetX) * (newScale / this.scale);
    this.offsetY = mouseY - (mouseY - this.offsetY) * (newScale / this.scale);

    this.scale = newScale;
    this.updateTransform();
}

handleMouseDown(e) {
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

updateTransform(skipSave = false) {
    this.transformContainer.style.transform =
        `translate(${this.offsetX}px, ${this.offsetY}px) scale(${this.scale})`;
    this.svgLayer.style.transform =
        `translate(${this.offsetX}px, ${this.offsetY}px) scale(${this.scale})`;

    this.renderConnections();

    if (!skipSave) {
        if (this.saveCanvasStateTimeout) {
            clearTimeout(this.saveCanvasStateTimeout);
        }
        this.saveCanvasStateTimeout = setTimeout(() => {
            this.saveCanvasState();
        }, 500);
    }
}
```

#### **2. Touch Support** ✅
**From TreeFlow Lines: 229-271**

```javascript
// Extract touch support methods:
setupTouchSupport() {
    let lastTouchDistance = 0;
    let touchStartX = 0;
    let touchStartY = 0;

    this.canvasTarget.addEventListener('touchstart', (e) => {
        if (e.touches.length === 2) {
            lastTouchDistance = this.getTouchDistance(e.touches[0], e.touches[1]);
        } else if (e.touches.length === 1) {
            const touch = e.touches[0];
            touchStartX = touch.clientX - this.offsetX;
            touchStartY = touch.clientY - this.offsetY;
        }
    }, { passive: true });

    this.canvasTarget.addEventListener('touchmove', (e) => {
        if (e.touches.length === 2) {
            e.preventDefault();
            const currentDistance = this.getTouchDistance(e.touches[0], e.touches[1]);
            const delta = currentDistance / lastTouchDistance;
            const newScale = Math.max(0.1, Math.min(3, this.scale * delta));
            this.scale = newScale;
            lastTouchDistance = currentDistance;
            this.updateTransform();
        } else if (e.touches.length === 1) {
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
```

#### **3. Node Dragging** ✅
**From TreeFlow Lines: 2044-2100**

```javascript
// Extract node dragging:
makeDraggable(node, entity) {
    let isDragging = false;
    let startX, startY, initialLeft, initialTop;

    node.addEventListener('mousedown', (e) => {
        if (e.target.classList.contains('connection-point')) {
            return;
        }

        if (e.target !== node && !e.target.classList.contains('entity-node-title') &&
            !e.target.classList.contains('entity-node-header')) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        initialLeft = parseInt(node.style.left) || 0;
        initialTop = parseInt(node.style.top) || 0;

        node.style.cursor = 'grabbing';
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;

        e.preventDefault();

        const dx = (e.clientX - startX) / this.scale;
        const dy = (e.clientY - startY) / this.scale;

        node.style.left = (initialLeft + dx) + 'px';
        node.style.top = (initialTop + dy) + 'px';

        this.renderConnections();
    });

    document.addEventListener('mouseup', (e) => {
        if (!isDragging) return;

        e.preventDefault();

        isDragging = false;
        node.style.cursor = 'move';

        const x = parseInt(node.style.left);
        const y = parseInt(node.style.top);
        this.saveEntityPosition(entity.id, x, y);
    });
}
```

#### **4. SVG Connections** ✅
**From TreeFlow Lines: 1138-1215**

```javascript
// Extract connection rendering:
renderConnection(connection) {
    const sourcePoint = this.outputPoints.get(connection.sourceProperty.id);
    const targetPoint = this.inputPoints.get(connection.targetEntity.id);

    if (!sourcePoint || !targetPoint) {
        console.warn('Missing connection points for connection:', connection);
        return;
    }

    const sourcePos = this.getConnectionPointPosition(sourcePoint.element, sourcePoint.entity);
    const targetPos = this.getConnectionPointPosition(targetPoint.element, targetPoint.entity);

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

    const dx = targetPos.x - sourcePos.x;
    const controlPointOffset = Math.abs(dx) / 2;

    const pathData = `M ${sourcePos.x} ${sourcePos.y}
                      C ${sourcePos.x + controlPointOffset} ${sourcePos.y},
                        ${targetPos.x - controlPointOffset} ${targetPos.y},
                        ${targetPos.x} ${targetPos.y}`;

    path.setAttribute('d', pathData);
    path.setAttribute('class', 'relationship-line');
    path.setAttribute('data-relationship-id', connection.id);

    // Color based on relationship type
    if (connection.type === 'ManyToOne') {
        path.style.stroke = '#3b82f6';
    } else if (connection.type === 'OneToMany') {
        path.style.stroke = '#10b981';
    } else if (connection.type === 'ManyToMany') {
        path.style.stroke = '#8b5cf6';
    } else if (connection.type === 'OneToOne') {
        path.style.stroke = '#f59e0b';
    }

    path.style.pointerEvents = 'stroke';
    path.addEventListener('mouseenter', (e) => {
        path.style.strokeWidth = '5';
        this.showRelationshipTooltip(e, connection);
    });
    path.addEventListener('mouseleave', () => {
        path.style.strokeWidth = '3';
        this.hideRelationshipTooltip();
    });

    path.addEventListener('click', (e) => {
        e.stopPropagation();
        this.deselectConnection();
        this.selectedConnection = connection;
        path.classList.add('selected');
    });

    path.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.deselectConnection();
        this.selectedConnection = connection;
        path.classList.add('selected');
        this.showRelationshipContextMenu(e, connection);
    });

    this.svgLayer.appendChild(path);
}
```

#### **5. Auto-Layout Algorithm** ✅
**From TreeFlow Lines: 2402-2578**

```javascript
// Extract auto-layout:
autoLayout() {
    this.showLoading();

    const entities = Array.from(this.nodes.keys()).map(id => {
        const entitiesArray = this.entitiesValue;
        const entity = entitiesArray.find(e => e.id === id);
        return { id, entity };
    }).filter(item => item.entity);

    // Identify connected entities
    const connectedEntities = new Set();
    this.relationships.forEach(rel => {
        connectedEntities.add(rel.sourceEntity.id);
        connectedEntities.add(rel.targetEntity.id);
    });

    // Separate orphan entities (no relationships)
    const orphanEntities = entities.filter(({id}) => !connectedEntities.has(id));
    const regularEntities = entities.filter(({id}) => connectedEntities.has(id));

    // Level assignment for connected entities
    const levels = new Map();

    // Assign levels based on relationships
    let changed = true;
    let iteration = 0;
    while (changed && iteration < 10) {
        changed = false;
        iteration++;

        this.relationships.forEach(rel => {
            const sourceId = rel.sourceEntity.id;
            const targetId = rel.targetEntity.id;

            const sourceLevel = levels.get(sourceId) ?? 0;
            const targetLevel = levels.get(targetId);

            if (targetLevel === undefined || targetLevel <= sourceLevel) {
                levels.set(targetId, sourceLevel + 1);
                changed = true;
            }
        });
    }

    // Position constants
    const entityWidth = 280;
    const horizontalSpacing = 350;
    const verticalSpacing = 150;
    const startX = 100;
    const startY = 100;

    const nodesByLevel = new Map();

    // Organize regular entities by level
    regularEntities.forEach(({id, entity}) => {
        const level = levels.get(id) ?? 0;
        if (!nodesByLevel.has(level)) {
            nodesByLevel.set(level, []);
        }
        nodesByLevel.get(level).push({id, entity});
    });

    // Position connected entities level by level
    Array.from(nodesByLevel.keys()).sort((a, b) => a - b).forEach(level => {
        const nodesInLevel = nodesByLevel.get(level);

        const x = startX + level * horizontalSpacing;
        let currentY = startY;

        nodesInLevel.forEach((item, index) => {
            const node = this.nodes.get(item.id);
            if (node) {
                const y = currentY;

                node.style.left = x + 'px';
                node.style.top = y + 'px';

                this.saveEntityPosition(item.id, x, y);

                const nodeHeight = node.offsetHeight || 120;
                currentY = y + nodeHeight + verticalSpacing;
            }
        });
    });

    // Position orphan entities
    if (orphanEntities.length > 0) {
        const maxLevel = nodesByLevel.size > 0
            ? Math.max(...Array.from(nodesByLevel.keys()))
            : 0;

        const orphanX = startX + (maxLevel + 1) * horizontalSpacing;
        let currentY = startY;

        orphanEntities.forEach((item, index) => {
            const node = this.nodes.get(item.id);
            if (node) {
                node.style.left = orphanX + 'px';
                node.style.top = currentY + 'px';

                this.saveEntityPosition(item.id, orphanX, currentY);

                const nodeHeight = node.offsetHeight || 120;
                currentY += nodeHeight + verticalSpacing;
            }
        });
    }

    this.renderConnections();

    setTimeout(() => {
        this.fitToScreen();
        setTimeout(() => {
            this.saveCanvasState();
            this.hideLoading();
        }, 100);
    }, 500);
}
```

#### **6. Fit to Screen** ✅
**From TreeFlow Lines: 2344-2400**

```javascript
// Extract fit to screen:
fitToScreen() {
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

    const padding = 50;
    minX -= padding;
    minY -= padding;
    maxX += padding;
    maxY += padding;

    const contentWidth = maxX - minX;
    const contentHeight = maxY - minY;

    const canvasWidth = this.canvasTarget.offsetWidth;
    const canvasHeight = this.canvasTarget.offsetHeight;

    const scaleX = canvasWidth / contentWidth;
    const scaleY = canvasHeight / contentHeight;
    const newScale = Math.min(Math.min(scaleX, scaleY), 1);

    this.scale = newScale;
    this.offsetX = (canvasWidth - contentWidth * newScale) / 2 - minX * newScale;
    this.offsetY = (canvasHeight - contentHeight * newScale) / 2 - minY * newScale;

    this.updateTransform();
}
```

#### **7. Utilities** ✅
**From TreeFlow Various Lines**

```javascript
// Extract utility methods:

// Loading overlay (lines 2580-2598)
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

// Toast notifications (lines 1987-2003)
showError(message) {
    const toast = document.createElement('div');
    toast.className = 'connection-error-toast';
    toast.innerHTML = `
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        ${this.escapeHtml(message)}
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// HTML escape (line 2276-2280)
escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fullscreen (lines 2625-2661)
toggleFullscreen() {
    const card = document.getElementById('generator-studio-card');
    if (!card) return;

    if (!document.fullscreenElement) {
        card.requestFullscreen().then(() => {
            setTimeout(() => {
                this.adjustCanvasHeight();
            }, 100);
        }).catch(err => {
            console.error(`Error attempting to enable fullscreen: ${err.message}`);
        });
    } else {
        document.exitFullscreen().then(() => {
            setTimeout(() => {
                this.adjustCanvasHeight();
            }, 100);
        });
    }
}

// Canvas height adjustment (lines 2604-2623)
adjustCanvasHeight() {
    if (!this.canvasTarget) return;

    const viewportHeight = window.innerHeight;
    const canvasRect = this.canvasTarget.getBoundingClientRect();
    const canvasTop = canvasRect.top;
    const bottomSpace = 30;

    const canvasHeight = Math.max(250, viewportHeight - canvasTop - bottomSpace);
    this.canvasTarget.style.height = `${canvasHeight}px`;
}
```

---

## 🎨 Frontend Implementation

### **Base Canvas Controller** (Generalized from TreeFlow)

Create: `app/assets/controllers/base_canvas_controller.js`

```javascript
import { Controller } from '@hotwired/stimulus';

/**
 * BaseCanvasController - Reusable Canvas Logic
 *
 * Extracted and generalized from TreeFlow canvas implementation.
 * Provides pan, zoom, drag, connections, auto-layout for any canvas-based UI.
 */
export class BaseCanvasController extends Controller {
    static targets = ['canvas', 'canvasContainer'];
    static values = {
        canvasState: Object,
        items: Array
    };

    connect() {
        // Initialize state
        const savedState = this.canvasStateValue || {};
        this.scale = savedState.scale || 1;
        this.offsetX = savedState.offsetX || 0;
        this.offsetY = savedState.offsetY || 0;
        this.isPanning = false;
        this.nodes = new Map();
        this.connections = [];
        this.isLoading = false;

        // Bind methods
        this.handleWheel = this.handleWheel.bind(this);
        this.handleMouseDown = this.handleMouseDown.bind(this);
        this.handleMouseMove = this.handleMouseMove.bind(this);
        this.handleMouseUp = this.handleMouseUp.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);
        this.handleWindowResize = this.handleWindowResize.bind(this);

        // Setup
        this.adjustCanvasHeight();
        window.addEventListener('resize', this.handleWindowResize);

        this.initializeCanvas();
    }

    initializeCanvas() {
        this.canvasTarget.innerHTML = '';

        // SVG layer for connections
        this.svgLayer = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.svgLayer.id = 'connections-svg';
        this.svgLayer.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; transform-origin: 0 0; z-index: 1; overflow: visible;';
        this.canvasTarget.appendChild(this.svgLayer);

        // Transform container for nodes
        const container = document.createElement('div');
        container.id = 'canvas-transform-container';
        container.style.cssText = 'position: absolute; width: 100%; height: 100%; transform-origin: 0 0; z-index: 2; background: transparent; overflow: visible;';
        this.canvasTarget.appendChild(container);
        this.transformContainer = container;

        // Add event listeners
        this.canvasTarget.addEventListener('wheel', this.handleWheel, { passive: false });
        this.canvasTarget.addEventListener('mousedown', this.handleMouseDown);
        document.addEventListener('mousemove', this.handleMouseMove);
        document.addEventListener('mouseup', this.handleMouseUp);
        document.addEventListener('keydown', this.handleKeyDown);

        this.setupTouchSupport();

        // Apply saved transform
        requestAnimationFrame(() => {
            this.updateTransform(true);
        });
    }

    // ========================================
    // PAN & ZOOM (from TreeFlow)
    // ========================================

    handleWheel(e) {
        e.preventDefault();
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        const newScale = Math.max(0.1, Math.min(3, this.scale * delta));

        const rect = this.canvasTarget.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        this.offsetX = mouseX - (mouseX - this.offsetX) * (newScale / this.scale);
        this.offsetY = mouseY - (mouseY - this.offsetY) * (newScale / this.scale);

        this.scale = newScale;
        this.updateTransform();
    }

    handleMouseDown(e) {
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
        // Override in child class
    }

    updateTransform(skipSave = false) {
        this.transformContainer.style.transform =
            `translate(${this.offsetX}px, ${this.offsetY}px) scale(${this.scale})`;
        this.svgLayer.style.transform =
            `translate(${this.offsetX}px, ${this.offsetY}px) scale(${this.scale})`;

        this.renderConnections();

        if (!skipSave) {
            if (this.saveCanvasStateTimeout) {
                clearTimeout(this.saveCanvasStateTimeout);
            }
            this.saveCanvasStateTimeout = setTimeout(() => {
                this.saveCanvasState();
            }, 500);
        }
    }

    // ========================================
    // TOUCH SUPPORT (from TreeFlow)
    // ========================================

    setupTouchSupport() {
        let lastTouchDistance = 0;
        let touchStartX = 0;
        let touchStartY = 0;

        this.canvasTarget.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                lastTouchDistance = this.getTouchDistance(e.touches[0], e.touches[1]);
            } else if (e.touches.length === 1) {
                const touch = e.touches[0];
                touchStartX = touch.clientX - this.offsetX;
                touchStartY = touch.clientY - this.offsetY;
            }
        }, { passive: true });

        this.canvasTarget.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2) {
                e.preventDefault();
                const currentDistance = this.getTouchDistance(e.touches[0], e.touches[1]);
                const delta = currentDistance / lastTouchDistance;
                const newScale = Math.max(0.1, Math.min(3, this.scale * delta));
                this.scale = newScale;
                lastTouchDistance = currentDistance;
                this.updateTransform();
            } else if (e.touches.length === 1) {
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

    // ========================================
    // NODE DRAGGING (from TreeFlow)
    // ========================================

    makeDraggable(node, item) {
        let isDragging = false;
        let startX, startY, initialLeft, initialTop;

        node.addEventListener('mousedown', (e) => {
            if (e.target.classList.contains('connection-point')) {
                return;
            }

            if (e.target !== node && !e.target.classList.contains('node-title') &&
                !e.target.classList.contains('node-header')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialLeft = parseInt(node.style.left) || 0;
            initialTop = parseInt(node.style.top) || 0;

            node.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            e.preventDefault();

            const dx = (e.clientX - startX) / this.scale;
            const dy = (e.clientY - startY) / this.scale;

            node.style.left = (initialLeft + dx) + 'px';
            node.style.top = (initialTop + dy) + 'px';

            this.renderConnections();
        });

        document.addEventListener('mouseup', (e) => {
            if (!isDragging) return;

            e.preventDefault();

            isDragging = false;
            node.style.cursor = 'move';

            const x = parseInt(node.style.left);
            const y = parseInt(node.style.top);
            this.savePosition(item.id, x, y);
        });
    }

    // ========================================
    // FIT TO SCREEN (from TreeFlow)
    // ========================================

    fitToScreen() {
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

        const padding = 50;
        minX -= padding;
        minY -= padding;
        maxX += padding;
        maxY += padding;

        const contentWidth = maxX - minX;
        const contentHeight = maxY - minY;

        const canvasWidth = this.canvasTarget.offsetWidth;
        const canvasHeight = this.canvasTarget.offsetHeight;

        const scaleX = canvasWidth / contentWidth;
        const scaleY = canvasHeight / contentHeight;
        const newScale = Math.min(Math.min(scaleX, scaleY), 1);

        this.scale = newScale;
        this.offsetX = (canvasWidth - contentWidth * newScale) / 2 - minX * newScale;
        this.offsetY = (canvasHeight - contentHeight * newScale) / 2 - minY * newScale;

        this.updateTransform();
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
        const rect = this.canvasTarget.getBoundingClientRect();
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        this.offsetX = centerX - (centerX - this.offsetX) * (newScale / this.scale);
        this.offsetY = centerY - (centerY - this.offsetY) * (newScale / this.scale);

        this.scale = newScale;
        this.updateTransform();
    }

    // ========================================
    // UTILITIES (from TreeFlow)
    // ========================================

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

    showError(message) {
        const toast = document.createElement('div');
        toast.className = 'connection-error-toast';
        toast.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            ${this.escapeHtml(message)}
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    toggleFullscreen() {
        const card = this.canvasContainerTarget.closest('.luminai-card');
        if (!card) return;

        if (!document.fullscreenElement) {
            card.requestFullscreen().then(() => {
                setTimeout(() => {
                    this.adjustCanvasHeight();
                }, 100);
            }).catch(err => {
                console.error(`Error attempting to enable fullscreen: ${err.message}`);
            });
        } else {
            document.exitFullscreen().then(() => {
                setTimeout(() => {
                    this.adjustCanvasHeight();
                }, 100);
            });
        }
    }

    adjustCanvasHeight() {
        if (!this.canvasTarget) return;

        const viewportHeight = window.innerHeight;
        const canvasRect = this.canvasTarget.getBoundingClientRect();
        const canvasTop = canvasRect.top;
        const bottomSpace = 30;

        const canvasHeight = Math.max(250, viewportHeight - canvasTop - bottomSpace);
        this.canvasTarget.style.height = `${canvasHeight}px`;
    }

    handleWindowResize() {
        this.adjustCanvasHeight();
    }

    // ========================================
    // ABSTRACT METHODS (Override in child)
    // ========================================

    renderConnections() {
        // Override in child class
    }

    savePosition(itemId, x, y) {
        // Override in child class
    }

    saveCanvasState() {
        // Override in child class
    }

    disconnect() {
        if (this.canvasTarget) {
            this.canvasTarget.removeEventListener('wheel', this.handleWheel);
            this.canvasTarget.removeEventListener('mousedown', this.handleMouseDown);
        }
        document.removeEventListener('mousemove', this.handleMouseMove);
        document.removeEventListener('mouseup', this.handleMouseUp);
        document.removeEventListener('keydown', this.handleKeyDown);
        window.removeEventListener('resize', this.handleWindowResize);
    }
}
```

### **Generator Canvas Controller** (Extends Base)

Create: `app/assets/controllers/generator_canvas_controller.js`

```javascript
import { BaseCanvasController } from './base_canvas_controller';

export default class extends BaseCanvasController {
    static values = {
        ...BaseCanvasController.values,
        entities: Array
    };

    connect() {
        super.connect();

        this.outputPoints = new Map();
        this.inputPoints = new Map();
        this.selectedConnection = null;
        this.isDraggingRelationship = false;
        this.dragSourceProperty = null;
        this.ghostLine = null;

        this.renderEntities();
        this.loadRelationships();
    }

    // ========================================
    // RENDER ENTITIES
    // ========================================

    renderEntities() {
        const entities = this.entitiesValue;

        entities.forEach((entity, index) => {
            this.renderEntity(entity, index);
        });
    }

    renderEntity(entity, index) {
        const node = document.createElement('div');
        node.className = 'generator-entity-node';
        node.dataset.entityId = entity.id;

        let x = entity.canvasX;
        let y = entity.canvasY;

        if (x === null || y === null) {
            x = 100 + (index * 300);
            y = 100;
        }

        node.style.left = x + 'px';
        node.style.top = y + 'px';

        node.innerHTML = `
            <div class="entity-node-header">
                <i class="${entity.icon}"></i>
                <strong class="node-title">${this.escapeHtml(entity.entityLabel)}</strong>
                <button class="entity-edit-btn" data-entity-id="${entity.id}">
                    <i class="bi bi-pencil"></i>
                </button>
            </div>

            <div class="entity-node-badges">
                ${entity.isGenerated ? '<span class="badge bg-success">Generated</span>' : ''}
                ${entity.apiEnabled ? '<span class="badge bg-info">API</span>' : ''}
                ${entity.voterEnabled ? '<span class="badge bg-warning">Voter</span>' : ''}
                <span class="badge bg-secondary">${entity.properties ? entity.properties.length : 0} props</span>
            </div>

            <div class="entity-node-body">
                <div class="properties-list">
                    ${this.renderPropertiesList(entity)}
                </div>
            </div>
        `;

        this.makeDraggable(node, entity);

        node.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            this.openEntityEditModal(entity);
        });

        const editBtn = node.querySelector('.entity-edit-btn');
        if (editBtn) {
            editBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openEntityEditModal(entity);
            });
        }

        this.nodes.set(entity.id, node);
        this.transformContainer.appendChild(node);

        // Add connection points for relationships
        this.addRelationshipPoints(node, entity);
    }

    renderPropertiesList(entity) {
        if (!entity.properties || entity.properties.length === 0) {
            return '<div class="empty-list">No properties</div>';
        }

        const displayProps = entity.properties.slice(0, 5);
        let html = displayProps.map(prop => `
            <div class="property-item">
                <span>${prop.propertyName}</span>
                <span class="type">${prop.propertyType}</span>
                ${prop.relationshipType ? `<span class="badge badge-rel">${prop.relationshipType}</span>` : ''}
            </div>
        `).join('');

        if (entity.properties.length > 5) {
            html += `<div class="text-muted small">+ ${entity.properties.length - 5} more...</div>`;
        }

        return html;
    }

    addRelationshipPoints(node, entity) {
        if (!entity.properties) return;

        entity.properties.forEach((property) => {
            if (property.relationshipType) {
                const point = document.createElement('div');
                point.className = 'connection-point output-point';
                point.dataset.propertyId = property.id;
                point.dataset.entityId = entity.id;
                point.title = `${property.propertyName} (${property.relationshipType})`;

                // Color by relationship type
                if (property.relationshipType === 'ManyToOne') {
                    point.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)';
                } else if (property.relationshipType === 'OneToMany') {
                    point.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                } else if (property.relationshipType === 'ManyToMany') {
                    point.style.background = 'linear-gradient(135deg, #8b5cf6, #7c3aed)';
                } else if (property.relationshipType === 'OneToOne') {
                    point.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
                }

                // Make draggable for creating relationships
                this.makePropertyDraggable(point, entity, property);

                node.appendChild(point);

                this.outputPoints.set(property.id, { element: point, entity, property });
            }
        });
    }

    // ========================================
    // RELATIONSHIP DRAG & DROP
    // ========================================

    makePropertyDraggable(point, entity, property) {
        point.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();

            this.isDraggingRelationship = true;
            this.dragSourceProperty = { element: point, entity, property };

            // Expand all entity nodes as targets
            this.expandEntityTargets();

            // Create ghost line
            this.createGhostLine();

            document.body.style.cursor = 'crosshair';
        });

        // Add mousemove and mouseup handlers at document level
        const handleMove = (e) => {
            if (!this.isDraggingRelationship) return;
            this.handleRelationshipDragMove(e);
        };

        const handleUp = (e) => {
            if (!this.isDraggingRelationship) return;
            this.handleRelationshipDrop(e);
        };

        document.addEventListener('mousemove', handleMove);
        document.addEventListener('mouseup', handleUp);
    }

    createGhostLine() {
        this.ghostLine = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        this.ghostLine.setAttribute('class', 'ghost-relationship-line');
        this.ghostLine.style.stroke = '#8b5cf6';
        this.ghostLine.style.strokeWidth = '3';
        this.ghostLine.style.strokeDasharray = '5,5';
        this.ghostLine.style.fill = 'none';
        this.ghostLine.style.pointerEvents = 'none';
        this.svgLayer.appendChild(this.ghostLine);
    }

    handleRelationshipDragMove(e) {
        if (!this.isDraggingRelationship || !this.ghostLine) return;

        const sourcePos = this.getConnectionPointPosition(
            this.dragSourceProperty.element,
            this.dragSourceProperty.entity
        );

        const rect = this.canvasTarget.getBoundingClientRect();
        const mouseX = (e.clientX - rect.left - this.offsetX) / this.scale;
        const mouseY = (e.clientY - rect.top - this.offsetY) / this.scale;

        const dx = mouseX - sourcePos.x;
        const controlPointOffset = Math.abs(dx) / 2;

        const pathData = `M ${sourcePos.x} ${sourcePos.y}
                          C ${sourcePos.x + controlPointOffset} ${sourcePos.y},
                            ${mouseX - controlPointOffset} ${mouseY},
                            ${mouseX} ${mouseY}`;

        this.ghostLine.setAttribute('d', pathData);

        // Highlight target
        const targetElement = document.elementFromPoint(e.clientX, e.clientY);
        document.querySelectorAll('.generator-entity-node.highlight-drop').forEach(el => {
            el.classList.remove('highlight-drop');
        });

        const targetNode = targetElement.closest('.generator-entity-node');
        if (targetNode && targetNode.dataset.entityId !== this.dragSourceProperty.entity.id) {
            targetNode.classList.add('highlight-drop');
        }
    }

    async handleRelationshipDrop(e) {
        if (!this.isDraggingRelationship) return;

        const targetElement = document.elementFromPoint(e.clientX, e.clientY);
        const targetNode = targetElement.closest('.generator-entity-node');

        if (targetNode && targetNode.dataset.entityId !== this.dragSourceProperty.entity.id) {
            const targetEntityId = targetNode.dataset.entityId;

            // Show relationship type selector
            const type = this.dragSourceProperty.property.relationshipType;

            // Create relationship
            await this.createRelationship(
                this.dragSourceProperty.property.id,
                targetEntityId,
                type
            );
        }

        this.cleanupRelationshipDrag();
    }

    expandEntityTargets() {
        document.querySelectorAll('.generator-entity-node').forEach(node => {
            if (node.dataset.entityId !== this.dragSourceProperty.entity.id) {
                node.classList.add('drop-target');
            }
        });
    }

    cleanupRelationshipDrag() {
        this.isDraggingRelationship = false;
        this.dragSourceProperty = null;

        document.querySelectorAll('.generator-entity-node.drop-target').forEach(node => {
            node.classList.remove('drop-target');
        });

        document.querySelectorAll('.generator-entity-node.highlight-drop').forEach(node => {
            node.classList.remove('highlight-drop');
        });

        if (this.ghostLine) {
            this.ghostLine.remove();
            this.ghostLine = null;
        }

        document.body.style.cursor = '';
    }

    // ========================================
    // RENDER CONNECTIONS
    // ========================================

    async loadRelationships() {
        const entities = this.entitiesValue;

        this.connections = [];
        entities.forEach(entity => {
            if (!entity.properties) return;

            entity.properties.forEach(property => {
                if (property.relationshipType && property.targetEntity) {
                    const targetEntity = entities.find(e => e.entityName === property.targetEntity);

                    if (targetEntity) {
                        this.connections.push({
                            id: property.id,
                            type: property.relationshipType,
                            sourceProperty: property,
                            sourceEntity: entity,
                            targetEntity: targetEntity
                        });
                    }
                }
            });
        });

        this.renderConnections();
    }

    renderConnections() {
        if (!this.svgLayer) return;

        // Clear existing
        this.svgLayer.querySelectorAll('.relationship-line').forEach(line => line.remove());

        if (this.connections.length === 0) return;

        this.connections.forEach(connection => {
            this.renderConnection(connection);
        });
    }

    renderConnection(connection) {
        const sourcePoint = this.outputPoints.get(connection.sourceProperty.id);
        const targetNode = this.nodes.get(connection.targetEntity.id);

        if (!sourcePoint || !targetNode) {
            console.warn('Missing connection elements:', connection);
            return;
        }

        const sourcePos = this.getConnectionPointPosition(sourcePoint.element, sourcePoint.entity);

        // Target position is center-left of target entity
        const targetX = parseInt(targetNode.style.left) || 0;
        const targetY = parseInt(targetNode.style.top) || 0;
        const targetHeight = targetNode.offsetHeight || 120;
        const targetPos = {
            x: targetX,
            y: targetY + (targetHeight / 2)
        };

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

        const dx = targetPos.x - sourcePos.x;
        const controlPointOffset = Math.abs(dx) / 2;

        const pathData = `M ${sourcePos.x} ${sourcePos.y}
                          C ${sourcePos.x + controlPointOffset} ${sourcePos.y},
                            ${targetPos.x - controlPointOffset} ${targetPos.y},
                            ${targetPos.x} ${targetPos.y}`;

        path.setAttribute('d', pathData);
        path.setAttribute('class', 'relationship-line');
        path.setAttribute('data-relationship-id', connection.id);

        // Color by relationship type
        if (connection.type === 'ManyToOne') {
            path.style.stroke = '#3b82f6';
        } else if (connection.type === 'OneToMany') {
            path.style.stroke = '#10b981';
        } else if (connection.type === 'ManyToMany') {
            path.style.stroke = '#8b5cf6';
        } else if (connection.type === 'OneToOne') {
            path.style.stroke = '#f59e0b';
        }

        path.style.pointerEvents = 'stroke';
        path.addEventListener('mouseenter', (e) => {
            path.style.strokeWidth = '5';
            this.showRelationshipTooltip(e, connection);
        });
        path.addEventListener('mouseleave', () => {
            path.style.strokeWidth = '3';
            this.hideRelationshipTooltip();
        });

        path.addEventListener('click', (e) => {
            e.stopPropagation();
            this.deselectConnection();
            this.selectedConnection = connection;
            path.classList.add('selected');
        });

        path.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.deselectConnection();
            this.selectedConnection = connection;
            path.classList.add('selected');
            this.showRelationshipContextMenu(e, connection);
        });

        this.svgLayer.appendChild(path);
    }

    getConnectionPointPosition(pointElement, entity) {
        const node = this.nodes.get(entity.id);
        if (!node) return { x: 0, y: 0 };

        const nodeX = parseInt(node.style.left) || 0;
        const nodeY = parseInt(node.style.top) || 0;

        let offsetX = 0;
        let offsetY = 0;
        let element = pointElement;

        while (element && element !== node) {
            offsetX += element.offsetLeft || 0;
            offsetY += element.offsetTop || 0;
            element = element.offsetParent;

            if (element === node) break;
        }

        const centerX = nodeX + offsetX + (pointElement.offsetWidth / 2);
        const centerY = nodeY + offsetY + (pointElement.offsetHeight / 2);

        return { x: centerX, y: centerY };
    }

    showRelationshipTooltip(event, connection) {
        this.hideRelationshipTooltip();

        const tooltip = document.createElement('div');
        tooltip.id = 'relationship-tooltip';
        tooltip.className = 'relationship-tooltip';
        tooltip.innerHTML = `
            <div class="tooltip-header">${this.escapeHtml(connection.sourceEntity.entityLabel)} → ${this.escapeHtml(connection.targetEntity.entityLabel)}</div>
            <div class="tooltip-body">
                <div><strong>Property:</strong> ${this.escapeHtml(connection.sourceProperty.propertyName)}</div>
                <div><strong>Type:</strong> <span class="badge bg-primary">${connection.type}</span></div>
            </div>
        `;

        tooltip.style.position = 'fixed';
        tooltip.style.left = event.clientX + 10 + 'px';
        tooltip.style.top = event.clientY + 10 + 'px';

        document.body.appendChild(tooltip);
    }

    hideRelationshipTooltip() {
        const tooltip = document.getElementById('relationship-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    showRelationshipContextMenu(event, connection) {
        this.hideRelationshipContextMenu();

        const menu = document.createElement('div');
        menu.id = 'relationship-context-menu';
        menu.className = 'relationship-context-menu';
        menu.innerHTML = `
            <button class="context-menu-item delete-btn">
                <i class="bi bi-trash"></i>
                Delete Relationship
            </button>
        `;

        menu.style.position = 'fixed';
        menu.style.left = event.clientX + 'px';
        menu.style.top = event.clientY + 'px';

        const deleteBtn = menu.querySelector('.delete-btn');
        deleteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.deleteRelationship(connection);
            this.hideRelationshipContextMenu();
        });

        document.body.appendChild(menu);

        const closeMenu = (e) => {
            if (!menu.contains(e.target)) {
                this.hideRelationshipContextMenu();
                document.removeEventListener('click', closeMenu);
            }
        };
        setTimeout(() => {
            document.addEventListener('click', closeMenu);
        }, 0);
    }

    hideRelationshipContextMenu() {
        const menu = document.getElementById('relationship-context-menu');
        if (menu) {
            menu.remove();
        }
    }

    deselectConnection() {
        if (this.selectedConnection) {
            const path = this.svgLayer.querySelector(`[data-relationship-id="${this.selectedConnection.id}"]`);
            if (path) {
                path.classList.remove('selected');
            }
            this.selectedConnection = null;
        }
    }

    // ========================================
    // API CALLS
    // ========================================

    async savePosition(entityId, x, y) {
        try {
            const response = await fetch(`/admin/generator/entity/${entityId}/position`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ x, y })
            });

            if (!response.ok) {
                console.error('Failed to save position');
            }
        } catch (error) {
            console.error('Error saving position:', error);
        }
    }

    async saveCanvasState() {
        try {
            const response = await fetch('/admin/generator/canvas-state', {
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

    async createRelationship(propertyId, targetEntityId, type) {
        try {
            const response = await fetch('/admin/generator/relationship', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    propertyId,
                    targetEntityId,
                    relationshipType: type
                })
            });

            const data = await response.json();

            if (data.success) {
                await this.refreshCanvas();
            } else {
                this.showError(data.error || 'Failed to create relationship');
            }
        } catch (error) {
            console.error('Error creating relationship:', error);
            this.showError('Network error creating relationship');
        }
    }

    async deleteRelationship(connection) {
        try {
            const response = await fetch(`/admin/generator/relationship/${connection.id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                await this.refreshCanvas();
            } else {
                this.showError(data.error || 'Failed to delete relationship');
            }
        } catch (error) {
            console.error('Error deleting relationship:', error);
            this.showError('Network error deleting relationship');
        }
    }

    async refreshCanvas() {
        location.reload();
    }

    // ========================================
    // ENTITY EDIT MODAL
    // ========================================

    async openEntityEditModal(entity) {
        const url = `/admin/generator/entity/${entity.id}/edit`;

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load modal');
            }

            const html = await response.text();

            const container = document.getElementById('global-modal-container');
            if (container) {
                container.innerHTML = html;
            }
        } catch (error) {
            console.error('Error opening modal:', error);
            this.showError('Failed to open edit form');
        }
    }

    // ========================================
    // AUTO-LAYOUT
    // ========================================

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

            const data = await response.json();

            if (data.success) {
                await this.refreshCanvas();
            } else {
                this.showError('Auto-layout failed');
            }
        } catch (error) {
            console.error('Error auto-layout:', error);
            this.showError('Network error during auto-layout');
        } finally {
            this.hideLoading();
        }
    }
}
```

---

## 🔧 Backend Implementation

### **1. GeneratorCanvasController** (PHP)

Create: `app/src/Controller/Admin/GeneratorCanvasController.php`

```php
<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;
use App\Entity\Generator\GeneratorCanvasState;
use App\Repository\Generator\GeneratorEntityRepository;
use App\Repository\Generator\GeneratorPropertyRepository;
use App\Service\Generator\DatabaseDefinitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/generator')]
#[IsGranted('ROLE_ADMIN')]
class GeneratorCanvasController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GeneratorEntityRepository $entityRepository,
        private readonly GeneratorPropertyRepository $propertyRepository,
        private readonly DatabaseDefinitionService $definitionService
    ) {
    }

    #[Route('/studio', name: 'admin_generator_studio', methods: ['GET'])]
    public function studio(): Response
    {
        // Load all entities with properties
        $entities = $this->entityRepository->findAllWithProperties();

        // Load canvas state (singleton)
        $canvasState = $this->em->getRepository(GeneratorCanvasState::class)->find(1);

        if (!$canvasState) {
            $canvasState = new GeneratorCanvasState();
            $this->em->persist($canvasState);
            $this->em->flush();
        }

        return $this->render('generator/studio.html.twig', [
            'entities' => $entities,
            'canvasState' => $canvasState
        ]);
    }

    #[Route('/entity/{id}/position', name: 'admin_generator_entity_position', methods: ['PATCH'])]
    public function saveEntityPosition(GeneratorEntity $entity, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $entity->setCanvasX((int) $data['x']);
        $entity->setCanvasY((int) $data['y']);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/canvas-state', name: 'admin_generator_canvas_state', methods: ['POST'])]
    public function saveCanvasState(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $canvasState = $this->em->getRepository(GeneratorCanvasState::class)->find(1);

        if (!$canvasState) {
            $canvasState = new GeneratorCanvasState();
            $this->em->persist($canvasState);
        }

        $canvasState->setScale((float) $data['scale']);
        $canvasState->setOffsetX((int) $data['offsetX']);
        $canvasState->setOffsetY((int) $data['offsetY']);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/entity/create', name: 'admin_generator_entity_create', methods: ['GET', 'POST'])]
    public function createEntity(Request $request): Response
    {
        $entity = new GeneratorEntity();

        $form = $this->createForm(GeneratorEntityFormType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($entity);
            $this->em->flush();

            $this->addFlash('success', 'Entity created successfully');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'reload' => true]);
            }

            return $this->redirectToRoute('admin_generator_studio');
        }

        return $this->render('generator/entity_form_modal.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity
        ]);
    }

    #[Route('/entity/{id}/edit', name: 'admin_generator_entity_edit', methods: ['GET', 'POST'])]
    public function editEntity(GeneratorEntity $entity, Request $request): Response
    {
        $form = $this->createForm(GeneratorEntityFormType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Entity updated successfully');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'reload' => true]);
            }

            return $this->redirectToRoute('admin_generator_studio');
        }

        return $this->render('generator/entity_form_modal.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity
        ]);
    }

    #[Route('/entity/{id}/delete', name: 'admin_generator_entity_delete', methods: ['DELETE'])]
    public function deleteEntity(GeneratorEntity $entity): JsonResponse
    {
        $this->em->remove($entity);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/property/create/{entityId}', name: 'admin_generator_property_create', methods: ['GET', 'POST'])]
    public function createProperty(string $entityId, Request $request): Response
    {
        $entity = $this->entityRepository->find($entityId);
        if (!$entity) {
            throw $this->createNotFoundException();
        }

        $property = new GeneratorProperty();
        $property->setEntity($entity);

        $form = $this->createForm(GeneratorPropertyFormType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($property);
            $this->em->flush();

            $this->addFlash('success', 'Property created successfully');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'reload' => true]);
            }

            return $this->redirectToRoute('admin_generator_studio');
        }

        return $this->render('generator/property_form_modal.html.twig', [
            'form' => $form->createView(),
            'property' => $property,
            'entity' => $entity
        ]);
    }

    #[Route('/property/{id}/edit', name: 'admin_generator_property_edit', methods: ['GET', 'POST'])]
    public function editProperty(GeneratorProperty $property, Request $request): Response
    {
        $form = $this->createForm(GeneratorPropertyFormType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Property updated successfully');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'reload' => true]);
            }

            return $this->redirectToRoute('admin_generator_studio');
        }

        return $this->render('generator/property_form_modal.html.twig', [
            'form' => $form->createView(),
            'property' => $property,
            'entity' => $property->getEntity()
        ]);
    }

    #[Route('/property/{id}/delete', name: 'admin_generator_property_delete', methods: ['DELETE'])]
    public function deleteProperty(GeneratorProperty $property): JsonResponse
    {
        $this->em->remove($property);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/relationship', name: 'admin_generator_relationship_create', methods: ['POST'])]
    public function createRelationship(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $property = $this->propertyRepository->find($data['propertyId']);
        $targetEntity = $this->entityRepository->find($data['targetEntityId']);

        if (!$property || !$targetEntity) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid property or entity'], 400);
        }

        // Update property with relationship
        $property->setTargetEntity($targetEntity->getEntityName());
        $property->setRelationshipType($data['relationshipType']);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/relationship/{id}', name: 'admin_generator_relationship_delete', methods: ['DELETE'])]
    public function deleteRelationship(GeneratorProperty $property): JsonResponse
    {
        // Clear relationship from property
        $property->setTargetEntity(null);
        $property->setRelationshipType(null);
        $property->setInversedBy(null);
        $property->setMappedBy(null);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/auto-layout', name: 'admin_generator_auto_layout', methods: ['POST'])]
    public function autoLayout(): JsonResponse
    {
        $entities = $this->entityRepository->findAll();

        // Build relationship graph
        $graph = [];
        foreach ($entities as $entity) {
            foreach ($entity->getProperties() as $property) {
                if ($property->getRelationshipType() && $property->getTargetEntity()) {
                    if (!isset($graph[$entity->getId()->toRfc4122()])) {
                        $graph[$entity->getId()->toRfc4122()] = [];
                    }

                    $targetEntity = $this->entityRepository->findOneBy(['entityName' => $property->getTargetEntity()]);
                    if ($targetEntity) {
                        $graph[$entity->getId()->toRfc4122()][] = $targetEntity->getId()->toRfc4122();
                    }
                }
            }
        }

        // Level assignment (BFS-based)
        $levels = [];
        $connectedIds = array_keys($graph);

        foreach ($graph as $edges) {
            foreach ($edges as $targetId) {
                $connectedIds[] = $targetId;
            }
        }
        $connectedIds = array_unique($connectedIds);

        // Simple level assignment
        foreach ($entities as $entity) {
            $id = $entity->getId()->toRfc4122();

            if (in_array($id, $connectedIds)) {
                $level = $this->calculateLevel($id, $graph, []);
                $levels[$id] = $level;
            }
        }

        // Position entities
        $horizontalSpacing = 350;
        $verticalSpacing = 150;
        $startX = 100;
        $startY = 100;

        $nodesByLevel = [];
        foreach ($levels as $id => $level) {
            if (!isset($nodesByLevel[$level])) {
                $nodesByLevel[$level] = [];
            }
            $nodesByLevel[$level][] = $id;
        }

        ksort($nodesByLevel);

        foreach ($nodesByLevel as $level => $ids) {
            $x = $startX + ($level * $horizontalSpacing);
            $y = $startY;

            foreach ($ids as $id) {
                $entity = $this->entityRepository->find($id);
                if ($entity) {
                    $entity->setCanvasX($x);
                    $entity->setCanvasY($y);

                    $y += $verticalSpacing;
                }
            }
        }

        // Position orphan entities
        $orphanX = $startX + (count($nodesByLevel) * $horizontalSpacing);
        $orphanY = $startY;

        foreach ($entities as $entity) {
            $id = $entity->getId()->toRfc4122();

            if (!isset($levels[$id])) {
                $entity->setCanvasX($orphanX);
                $entity->setCanvasY($orphanY);

                $orphanY += $verticalSpacing;
            }
        }

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    private function calculateLevel(string $id, array $graph, array $visited): int
    {
        if (in_array($id, $visited)) {
            return 0; // Cycle detection
        }

        $visited[] = $id;

        if (!isset($graph[$id]) || empty($graph[$id])) {
            return 0;
        }

        $maxChildLevel = 0;
        foreach ($graph[$id] as $childId) {
            $childLevel = $this->calculateLevel($childId, $graph, $visited);
            $maxChildLevel = max($maxChildLevel, $childLevel);
        }

        return $maxChildLevel + 1;
    }

    #[Route('/entity/{id}/generate-preview', name: 'admin_generator_entity_preview', methods: ['GET'])]
    public function generatePreview(GeneratorEntity $entity): Response
    {
        $definition = $this->definitionService->buildEntityDefinition($entity);

        $generatedCode = [
            'entity' => $this->definitionService->generateEntityCode($definition),
            'repository' => $this->definitionService->generateRepositoryCode($definition),
            'form' => $this->definitionService->generateFormCode($definition),
            'controller' => $this->definitionService->generateControllerCode($definition),
            'templates' => $this->definitionService->generateTemplatesCode($definition),
        ];

        return $this->render('generator/preview_modal.html.twig', [
            'entity' => $entity,
            'generatedCode' => $generatedCode
        ]);
    }

    #[Route('/entity/{id}/generate', name: 'admin_generator_entity_generate', methods: ['POST'])]
    public function generateEntity(GeneratorEntity $entity): JsonResponse
    {
        try {
            $definition = $this->definitionService->buildEntityDefinition($entity);

            // Generate all files
            $this->definitionService->generateAllFiles($definition);

            // Mark as generated
            $entity->setIsGenerated(true);
            $entity->setLastGeneratedAt(new \DateTimeImmutable());
            $entity->setLastGenerationLog('Successfully generated all files');

            $this->em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Entity generated successfully'
            ]);
        } catch (\Exception $e) {
            $entity->setLastGenerationLog('Error: ' . $e->getMessage());
            $this->em->flush();

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

### **2. DatabaseDefinitionService**

Create: `app/src/Service/Generator/DatabaseDefinitionService.php`

```php
<?php

declare(strict_types=1);

namespace App\Service\Generator;

use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;

class DatabaseDefinitionService
{
    /**
     * Build entity definition array from database
     */
    public function buildEntityDefinition(GeneratorEntity $entity): array
    {
        $definition = [
            'entityName' => $entity->getEntityName(),
            'entityLabel' => $entity->getEntityLabel(),
            'pluralLabel' => $entity->getPluralLabel(),
            'icon' => $entity->getIcon(),
            'description' => $entity->getDescription(),
            'hasOrganization' => $entity->isHasOrganization(),

            // API
            'apiEnabled' => $entity->isApiEnabled(),
            'apiOperations' => $entity->getApiOperations() ?? [],
            'apiSecurity' => $entity->getApiSecurity(),
            'apiPaginationEnabled' => $entity->isApiPaginationEnabled(),
            'apiItemsPerPage' => $entity->getApiItemsPerPage(),
            'apiDefaultOrder' => $entity->getApiDefaultOrder(),
            'apiSearchableFields' => $entity->getApiSearchableFields(),
            'apiFilterableFields' => $entity->getApiFilterableFields(),

            // Security
            'voterEnabled' => $entity->isVoterEnabled(),
            'voterAttributes' => $entity->getVoterAttributes() ?? ['VIEW', 'EDIT', 'DELETE'],

            // Form
            'formTheme' => $entity->getFormTheme(),

            // Templates
            'customIndexTemplate' => $entity->getCustomIndexTemplate(),
            'customFormTemplate' => $entity->getCustomFormTemplate(),
            'customShowTemplate' => $entity->getCustomShowTemplate(),

            // Navigation
            'menuGroup' => $entity->getMenuGroup(),
            'menuOrder' => $entity->getMenuOrder(),

            // Testing
            'testEnabled' => $entity->isTestEnabled(),

            // Properties
            'properties' => []
        ];

        foreach ($entity->getProperties() as $property) {
            $definition['properties'][] = $this->buildPropertyDefinition($property);
        }

        return $definition;
    }

    private function buildPropertyDefinition(GeneratorProperty $property): array
    {
        return [
            'propertyName' => $property->getPropertyName(),
            'propertyLabel' => $property->getPropertyLabel(),
            'propertyType' => $property->getPropertyType(),
            'propertyOrder' => $property->getPropertyOrder(),

            // Database
            'nullable' => $property->isNullable(),
            'length' => $property->getLength(),
            'precision' => $property->getPrecision(),
            'scale' => $property->getScale(),
            'unique' => $property->isUnique(),
            'defaultValue' => $property->getDefaultValue(),

            // Relationships
            'relationshipType' => $property->getRelationshipType(),
            'targetEntity' => $property->getTargetEntity(),
            'inversedBy' => $property->getInversedBy(),
            'mappedBy' => $property->getMappedBy(),
            'cascade' => $property->getCascade(),
            'orphanRemoval' => $property->isOrphanRemoval(),
            'fetch' => $property->getFetch(),
            'orderBy' => $property->getOrderBy(),

            // Validation
            'validationRules' => $property->getValidationRules(),
            'validationMessage' => $property->getValidationMessage(),

            // Form
            'formType' => $property->getFormType(),
            'formOptions' => $property->getFormOptions(),
            'formRequired' => $property->isFormRequired(),
            'formReadOnly' => $property->isFormReadOnly(),
            'formHelp' => $property->getFormHelp(),

            // UI
            'showInList' => $property->isShowInList(),
            'showInDetail' => $property->isShowInDetail(),
            'showInForm' => $property->isShowInForm(),
            'sortable' => $property->isSortable(),
            'searchable' => $property->isSearchable(),
            'filterable' => $property->isFilterable(),

            // API
            'apiReadable' => $property->isApiReadable(),
            'apiWritable' => $property->isApiWritable(),
            'apiGroups' => $property->getApiGroups(),

            // Localization
            'translationKey' => $property->getTranslationKey(),
            'formatPattern' => $property->getFormatPattern(),

            // Fixtures
            'fixtureType' => $property->getFixtureType(),
            'fixtureOptions' => $property->getFixtureOptions(),
        ];
    }

    /**
     * Generate entity PHP code
     */
    public function generateEntityCode(array $definition): string
    {
        // Use existing EntityGenerator templates
        // This integrates with the existing Generator system
        return "Entity code generation placeholder";
    }

    /**
     * Generate repository PHP code
     */
    public function generateRepositoryCode(array $definition): string
    {
        return "Repository code generation placeholder";
    }

    /**
     * Generate form PHP code
     */
    public function generateFormCode(array $definition): string
    {
        return "Form code generation placeholder";
    }

    /**
     * Generate controller PHP code
     */
    public function generateControllerCode(array $definition): string
    {
        return "Controller code generation placeholder";
    }

    /**
     * Generate templates code
     */
    public function generateTemplatesCode(array $definition): array
    {
        return [
            'index' => "Index template placeholder",
            'show' => "Show template placeholder",
            'form' => "Form template placeholder"
        ];
    }

    /**
     * Generate all files and write to disk
     */
    public function generateAllFiles(array $definition): void
    {
        // Use existing Generator services
        // EntityGenerator, ControllerGenerator, FormGenerator, etc.
        // This is where we integrate with existing Generator infrastructure
    }
}
```

---

## 🎨 Template Implementation

### **Main Studio Template**

Create: `app/templates/generator/studio.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Generator Studio - Visual Database Designer{% endblock %}

{% block body %}
<div class="container-fluid" id="generator-studio-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>
            <i class="bi bi-diagram-3 me-2"></i>
            Generator Studio
        </h1>

        <div class="btn-group">
            <button type="button" class="btn btn-primary" data-action="click->generatorCanvas#openCreateEntityModal">
                <i class="bi bi-plus-lg"></i> New Entity
            </button>
            <button type="button" class="btn btn-outline-secondary" data-action="click->generatorCanvas#autoLayout">
                <i class="bi bi-grid-3x3"></i> Auto Layout
            </button>
            <button type="button" class="btn btn-outline-secondary" data-action="click->generatorCanvas#fitToScreen">
                <i class="bi bi-arrows-angle-expand"></i> Fit to Screen
            </button>
            <button type="button" class="btn btn-outline-secondary" data-action="click->generatorCanvas#toggleFullscreen">
                <i class="bi bi-fullscreen"></i> Fullscreen
            </button>
        </div>
    </div>

    <div class="luminai-card p-0 position-relative"
         data-controller="generator-canvas"
         data-generator-canvas-entities-value="{{ entities|json_encode|e('html_attr') }}"
         data-generator-canvas-canvas-state-value="{{ {scale: canvasState.scale, offsetX: canvasState.offsetX, offsetY: canvasState.offsetY}|json_encode|e('html_attr') }}">

        {# Canvas Controls #}
        <div class="canvas-controls">
            <button type="button" class="canvas-control-btn" data-action="click->generatorCanvas#zoomIn" title="Zoom In">
                <i class="bi bi-zoom-in"></i>
            </button>
            <button type="button" class="canvas-control-btn" data-action="click->generatorCanvas#zoomOut" title="Zoom Out">
                <i class="bi bi-zoom-out"></i>
            </button>
            <button type="button" class="canvas-control-btn" data-action="click->generatorCanvas#fitToScreen" title="Fit to Screen">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
            <div class="canvas-zoom-display">
                <span id="zoom-percentage">100%</span>
            </div>
        </div>

        {# Main Canvas #}
        <div class="generator-canvas-container" data-generator-canvas-target="canvasContainer">
            <div class="generator-canvas" data-generator-canvas-target="canvas">
                {# Entities and connections will be rendered here by Stimulus #}
            </div>
        </div>

        {# Legend #}
        <div class="canvas-legend">
            <div class="legend-title">Relationships</div>
            <div class="legend-item">
                <span class="legend-line" style="background: #3b82f6;"></span>
                <span>ManyToOne</span>
            </div>
            <div class="legend-item">
                <span class="legend-line" style="background: #10b981;"></span>
                <span>OneToMany</span>
            </div>
            <div class="legend-item">
                <span class="legend-line" style="background: #8b5cf6;"></span>
                <span>ManyToMany</span>
            </div>
            <div class="legend-item">
                <span class="legend-line" style="background: #f59e0b;"></span>
                <span>OneToOne</span>
            </div>
        </div>
    </div>
</div>

{# Modal Container #}
<div id="global-modal-container"></div>

{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    <style>
        {# Canvas styles imported from separate CSS file #}
        @import url('/build/generator-canvas.css');
    </style>
{% endblock %}
```

---

## 🎨 CSS Styles

Create: `app/assets/styles/generator-canvas.css`

```css
/* ========================================
   GENERATOR CANVAS STYLES
   Adapted from TreeFlow Canvas
   ======================================== */

/* Canvas Container */
.generator-canvas-container {
    position: relative;
    width: 100%;
    height: 600px;
    overflow: hidden;
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    border-radius: 8px;
}

.generator-canvas {
    position: absolute;
    width: 100%;
    height: 100%;
    cursor: default;
    overflow: visible;
}

/* Canvas Background Grid */
.generator-canvas::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image:
        linear-gradient(rgba(0,0,0,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.05) 1px, transparent 1px);
    background-size: 20px 20px;
    pointer-events: none;
    z-index: 0;
}

/* Entity Nodes */
.generator-entity-node {
    position: absolute;
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    cursor: move;
    min-width: 280px;
    max-width: 350px;
    transition: box-shadow 0.2s, border-color 0.2s;
    z-index: 10;
}

.generator-entity-node:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    border-color: #0d6efd;
}

.generator-entity-node.drop-target {
    border-color: #8b5cf6;
    border-style: dashed;
    border-width: 3px;
}

.generator-entity-node.highlight-drop {
    border-color: #10b981;
    background: #f0fdf4;
    border-width: 3px;
}

/* Entity Header */
.entity-node-header {
    padding: 12px 16px;
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    color: white;
    border-radius: 6px 6px 0 0;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: move;
    font-weight: 600;
}

.entity-node-header i {
    font-size: 1.2rem;
}

.node-title {
    flex: 1;
    font-size: 1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.entity-edit-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s;
}

.entity-edit-btn:hover {
    background: rgba(255,255,255,0.3);
}

/* Entity Badges */
.entity-node-badges {
    padding: 8px 16px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.entity-node-badges .badge {
    font-size: 0.75rem;
    padding: 4px 8px;
}

/* Entity Body - Properties List */
.entity-node-body {
    padding: 12px 16px;
    max-height: 300px;
    overflow-y: auto;
}

.properties-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.property-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 10px;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 0.875rem;
    transition: background 0.2s;
}

.property-item:hover {
    background: #e9ecef;
}

.property-item .type {
    color: #6c757d;
    font-size: 0.75rem;
    font-family: 'Courier New', monospace;
}

.property-item .badge-rel {
    font-size: 0.7rem;
    padding: 2px 6px;
}

.empty-list {
    color: #6c757d;
    font-style: italic;
    text-align: center;
    padding: 20px;
}

/* Connection Points */
.connection-point {
    position: absolute;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    cursor: crosshair;
    z-index: 20;
    transition: transform 0.2s, box-shadow 0.2s;
}

.connection-point:hover {
    transform: scale(1.3);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.connection-point.output-point {
    right: -8px;
    top: 50%;
    transform: translateY(-50%);
}

/* Relationship Lines (SVG) */
#connections-svg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
    overflow: visible;
}

.relationship-line {
    fill: none;
    stroke-width: 3;
    stroke-linecap: round;
    pointer-events: stroke;
    cursor: pointer;
    transition: stroke-width 0.2s;
}

.relationship-line.selected {
    stroke-width: 5;
    filter: drop-shadow(0 0 4px rgba(0,0,0,0.3));
}

.ghost-relationship-line {
    fill: none;
    stroke-width: 3;
    stroke-dasharray: 5,5;
    pointer-events: none;
    animation: dash 0.5s linear infinite;
}

@keyframes dash {
    to {
        stroke-dashoffset: -10;
    }
}

/* Canvas Controls */
.canvas-controls {
    position: absolute;
    top: 16px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 100;
}

.canvas-control-btn {
    width: 40px;
    height: 40px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s;
}

.canvas-control-btn:hover {
    background: #f8f9fa;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.canvas-zoom-display {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 8px 12px;
    text-align: center;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Canvas Legend */
.canvas-legend {
    position: absolute;
    bottom: 16px;
    left: 16px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 100;
}

.legend-title {
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.875rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    font-size: 0.813rem;
}

.legend-line {
    display: inline-block;
    width: 30px;
    height: 3px;
    border-radius: 2px;
}

/* Loading Overlay */
.canvas-loading {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.canvas-loading .spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e9ecef;
    border-top-color: #0d6efd;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Error Toast */
.connection-error-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #dc3545;
    color: white;
    padding: 16px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 10000;
    display: flex;
    align-items: center;
    animation: slideIn 0.3s;
}

.connection-error-toast.fade-out {
    animation: slideOut 0.3s;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

/* Relationship Tooltip */
.relationship-tooltip {
    background: rgba(0,0,0,0.9);
    color: white;
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 0.875rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 10000;
    pointer-events: none;
}

.relationship-tooltip .tooltip-header {
    font-weight: 600;
    margin-bottom: 8px;
}

.relationship-tooltip .tooltip-body {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

/* Relationship Context Menu */
.relationship-context-menu {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 10000;
    overflow: hidden;
}

.context-menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: white;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 0.875rem;
}

.context-menu-item:hover {
    background: #f8f9fa;
}

.context-menu-item.delete-btn {
    color: #dc3545;
}

.context-menu-item.delete-btn:hover {
    background: #fee;
}

/* Fullscreen Styles */
.luminai-card:fullscreen {
    width: 100vw;
    height: 100vh;
    border-radius: 0;
}

.luminai-card:fullscreen .generator-canvas-container {
    height: calc(100vh - 120px);
}
```

---

## 📝 Implementation Steps

### **Phase 1: Database Setup** (Day 1-2)

1. **Create Migration**
   ```bash
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate
   ```

2. **Create Repositories**
   - `GeneratorEntityRepository`
   - `GeneratorPropertyRepository`

3. **Verify Schema**
   ```bash
   php bin/console doctrine:schema:validate
   ```

### **Phase 2: Backend** (Day 3-5)

1. **Create Form Types**
   - `GeneratorEntityFormType`
   - `GeneratorPropertyFormType`

2. **Create Controller**
   - `GeneratorCanvasController`

3. **Create Service**
   - `DatabaseDefinitionService`

4. **Test API Endpoints**
   ```bash
   curl -X GET http://localhost/admin/generator/studio
   curl -X PATCH http://localhost/admin/generator/entity/{id}/position
   ```

### **Phase 3: Frontend** (Day 6-8)

1. **Extract Base Canvas**
   - Copy TreeFlow methods to `base_canvas_controller.js`
   - Test pan/zoom/drag independently

2. **Create Generator Canvas**
   - Extend BaseCanvasController
   - Add entity-specific rendering

3. **Create Templates**
   - `studio.html.twig`
   - Modal templates

### **Phase 4: Integration** (Day 9-11)

1. **Connect to Existing Generator**
   - Update `DatabaseDefinitionService` to use existing generators
   - Test code generation

2. **CSV Import**
   - Create import command
   - Test migration from CSV

### **Phase 5: Testing** (Day 12-14)

1. **Unit Tests**
2. **Functional Tests**
3. **Manual QA**

### **Phase 6: Documentation & Deployment** (Day 15-20)

1. **User Guide**
2. **Migration Guide**
3. **Deploy to VPS**

---

## ✅ Testing Strategy

### **Unit Tests**

```php
// tests/Service/Generator/DatabaseDefinitionServiceTest.php
class DatabaseDefinitionServiceTest extends TestCase
{
    public function testBuildEntityDefinition(): void
    {
        $entity = new GeneratorEntity();
        $entity->setEntityName('Contact');
        // ... test definition building
    }
}
```

### **Functional Tests**

```php
// tests/Controller/GeneratorCanvasControllerTest.php
class GeneratorCanvasControllerTest extends WebTestCase
{
    public function testStudioPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/generator/studio');
        $this->assertResponseIsSuccessful();
    }

    public function testSaveEntityPosition(): void
    {
        // Test AJAX position save
    }
}
```

---

## 🔄 Migration from CSV

### **Import Command**

Create: `app/src/Command/ImportCsvToDatabase.php`

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCsvToDatabase extends Command
{
    protected static $defaultName = 'generator:import-csv';

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Read EntityNew.csv
        // Create GeneratorEntity records
        // Read PropertyNew.csv
        // Create GeneratorProperty records
        // Link relationships

        $output->writeln('<info>CSV import completed!</info>');

        return Command::SUCCESS;
    }
}
```

---

## 🎯 Summary

This V3 plan provides:

✅ **Complete database schema** (3 entities, fully specified)
✅ **70% code reuse** from TreeFlow canvas
✅ **Full backend implementation** (controllers, services, forms)
✅ **Complete frontend** (Stimulus controllers, templates, CSS)
✅ **Step-by-step implementation guide** (6 phases, 20 days)
✅ **Testing strategy** (unit + functional tests)
✅ **CSV migration** (import command)

**Timeline: 3-4 weeks** (vs 6 weeks from scratch)

**Next Steps:**
1. Review and approve plan
2. Create database migration
3. Start Phase 1 implementation

---

**🚀 Ready to build Generator V3!**
