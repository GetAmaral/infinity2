# Genmax Controller Generator - Complete Specification

**Version:** 1.0
**Last Updated:** 2025-01-25
**Status:** Ready for Implementation

---

## Table of Contents

1. [Overview](#overview)
2. [Key Findings & Decisions](#key-findings--decisions)
3. [Architecture](#architecture)
4. [GeneratorEntity Configuration](#generatorentity-configuration)
5. [ControllerGenerator Service](#controllergenerator-service)
6. [Template Structure](#template-structure)
7. [List Property Configuration](#list-property-configuration)
8. [Best Practices](#best-practices)
9. [Implementation Checklist](#implementation-checklist)

---

## Overview

The Controller Generator is Phase 3 of the Genmax code generation system. It generates Symfony controllers using the Base/Extension pattern, following all system conventions and best practices.

### What It Generates

1. **Base Controller** (`src/Controller/Generated/{Entity}ControllerGenerated.php`)
   - ALWAYS regenerated
   - Contains all CRUD methods
   - Security checks with Voters
   - Form handling
   - Lifecycle hooks

2. **Extension Controller** (`src/Controller/{Entity}Controller.php`)
   - Created once, safe to edit
   - Route definitions
   - Calls to parent methods
   - Custom business logic

### Design Principles

- **Separation of Concerns**: Generated code vs. custom code
- **Security First**: Voters for authorization, CSRF protection
- **Multi-Tenant Ready**: Automatic organization filtering
- **Turbo Compatible**: Modal forms with SPA-like behavior
- **Never Guess**: Use centralized naming conventions (GenmaxExtension)

---

## Key Findings & Decisions

### Finding 1: MultiTenant System Handles Organization & Owner

**Analysis:**
- `TenantEntityProcessor` is both a Doctrine listener (prePersist) and API Platform processor
- Automatically assigns `organization` from `TenantContext`
- Automatically assigns `owner` from authenticated user
- Works for ALL entity creation scenarios (API, forms, CLI, fixtures)
- Non-admins CANNOT override organization (security enforcement)

**Decision:**
```php
// initializeNewEntity() should be EMPTY
// Only for custom initialization logic
protected function initializeNewEntity(Entity $entity): void
{
    // Organization and Owner are set automatically by TenantEntityProcessor
    // Add your custom initialization here
}
```

**Source:** `/app/src/MultiTenant/TenantEntityProcessor.php`

### Finding 2: GeneratorProperty Has Rich List Configuration

**Available Properties:**
```php
// Display Control
bool $showInList = true;          // Show in list views
bool $sortable = false;           // Column is sortable
bool $searchable = false;         // Field is searchable
bool $filterable = false;         // Field has filters

// Advanced Filter Configuration
string $filterStrategy = null;     // 'partial', 'exact', 'start', 'end', 'word_start'
bool $filterSearchable = false;    // Full-text search
bool $filterOrderable = false;     // Enable ordering
bool $filterBoolean = false;       // Boolean filter
bool $filterDate = false;          // Date range filter
bool $filterNumericRange = false;  // Numeric range filter
bool $filterExists = false;        // Null/not-null filter
```

**Decision:**
- Use these properties to generate proper list view configuration
- Generate search/filter/sort configurations dynamically
- Pass metadata to templates for client-side rendering

**Source:** `/app/src/Entity/Generator/GeneratorProperty.php`

### Finding 3: getSlug() Must Use Centralized Utils

**Current Implementation (WRONG):**
```php
public function getSlug(): string
{
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->entityName));
}
```

**Required Implementation:**
```php
public function getSlug(): string
{
    return \App\Service\Utils::stringToSlug($this->entityName);
}
```

**Source:** `/app/src/Entity/Generator/GeneratorEntity.php:381`

### Finding 4: API Platform Route Conflict

**Issue:**
- API Platform provides `/api/courses` automatically
- Controller should NOT use `/api/search` (would conflict)

**Solution:**
```php
// Use different route
#[Route('/search', name: 'course_search', methods: ['GET'])]
public function apiSearch(Request $request): Response
{
    return $this->apiSearchAction($request);
}
```

**Result:**
- API Platform: `/api/courses` (external/API clients)
- Web Controller: `/course/search` (internal AJAX from list page)

### Finding 5: Routes Must Be in Extension Class Only

**Issue:**
- PHP attributes are inherited
- Routes in base class would cause duplicate route errors

**Solution:**
- Routes ONLY in extension controller
- Base controller has protected action methods
- Extension controller has public route methods

---

## Architecture

### File Structure

```
app/src/Controller/
├── Base/
│   └── BaseApiController.php          # Shared functionality (exists)
├── {Entity}Controller.php             # Extension (created once)
└── Generated/
    └── {Entity}ControllerGenerated.php # Base (ALWAYS regenerated)
```

### Inheritance Chain

```
CourseControllerGenerated (abstract)
    ↓ extends
BaseApiController (shared utilities)
    ↓ extends
AbstractController (Symfony)

CourseController (final)
    ↓ extends
CourseControllerGenerated
```

### Separation of Responsibilities

| Component | Responsibility | Editable |
|-----------|---------------|----------|
| BaseApiController | Shared API utilities | Yes (manual) |
| {Entity}ControllerGenerated | All CRUD logic, security, forms | No (regenerated) |
| {Entity}Controller | Routes, custom overrides | Yes (safe) |

---

## GeneratorEntity Configuration

### New Properties (All Protected)

Add these fields to `GeneratorEntity`:

```php
// ====================================
// CONTROLLER CONFIGURATION (6 fields)
// ====================================

#[ORM\Column(options: ['default' => true])]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
protected bool $generateController = true;

#[ORM\Column(options: ['default' => true])]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
protected bool $controllerOperationIndex = true;

#[ORM\Column(options: ['default' => true])]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
protected bool $controllerOperationNew = true;

#[ORM\Column(options: ['default' => true])]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
protected bool $controllerOperationEdit = true;

#[ORM\Column(options: ['default' => true])]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
protected bool $controllerOperationDelete = true;

#[ORM\Column(options: ['default' => true])]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
protected bool $controllerOperationShow = true;

// NOTE: API search is controlled by $apiOperations property
// If 'GetCollection' is in $apiOperations, API search is enabled
```

### Removed/Reused Properties

| Proposed Property | Decision | Reason |
|------------------|----------|--------|
| `pageIcon` | Use existing `$icon` | Already exists, used for navigation |
| `generateApiSearch` | Use `$apiOperations` | Check for 'GetCollection' in array |
| `generateShowPage` | Use `$controllerOperationShow` | Individual bool property |
| `routePrefix` | Always use slug | `Utils::stringToSlug($entityName)` |

### Helper Methods to Add

```php
/**
 * Check if entity has any searchable properties
 */
public function hasSearchableProperties(): bool
{
    foreach ($this->properties as $property) {
        if ($property->isSearchable()) {
            return true;
        }
    }
    return false;
}

/**
 * Check if entity has any filterable properties
 */
public function hasFilterableProperties(): bool
{
    foreach ($this->properties as $property) {
        if ($property->isFilterable()) {
            return true;
        }
    }
    return false;
}

/**
 * Check if entity has any sortable properties
 */
public function hasSortableProperties(): bool
{
    foreach ($this->properties as $property) {
        if ($property->isSortable() || $property->isFilterOrderable()) {
            return true;
        }
    }
    return false;
}

/**
 * Get entity slug (using centralized Utils)
 */
public function getSlug(): string
{
    return \App\Service\Utils::stringToSlug($this->entityName);
}
```

---

## ControllerGenerator Service

### Service Definition

```php
<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use App\Service\Utils;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

/**
 * Controller Generator for Genmax
 *
 * Generates Symfony controllers using Base/Extension pattern.
 * All naming uses centralized Utils methods via GenmaxExtension.
 */
class ControllerGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        protected readonly string $projectDir,
        #[Autowire(param: 'genmax.paths')]
        protected readonly array $paths,
        #[Autowire(param: 'genmax.templates')]
        protected readonly array $templates,
        protected readonly Environment $twig,
        protected readonly SmartFileWriter $fileWriter,
        protected readonly GenmaxExtension $genmaxExtension,
        protected readonly LoggerInterface $logger
    ) {}

    public function generate(GeneratorEntity $entity): array
    {
        if (!$entity->isGenerateController()) {
            return [];
        }

        $this->validateConfiguration($entity);

        return [
            $this->generateBaseController($entity),
            $this->generateExtensionController($entity),
        ];
    }
}
```

### Core Methods

#### buildTemplateContext()

```php
protected function buildTemplateContext(GeneratorEntity $entity): array
{
    $entityName = $entity->getEntityName();

    // Use GenmaxExtension for all naming
    $entityVariable = $this->genmaxExtension->toCamelCase($entityName, false);
    $entityPluralName = $this->genmaxExtension->toPlural($entityName);
    $entityPluralVariable = $this->genmaxExtension->toCamelCase($entityPluralName, false);
    $routePrefix = $entity->getSlug(); // Uses Utils::stringToSlug

    return [
        'entity' => $entity,
        'entityName' => $entityName,
        'entityVariable' => $entityVariable,
        'entityPluralName' => $entityPluralName,
        'entityPluralVariable' => $entityPluralVariable,
        'routePrefix' => $routePrefix,
        'voterClass' => $entityName . 'Voter',
        'formTypeClass' => $entityName . 'FormType',
        'repositoryClass' => $entityName . 'Repository',
        'translationDomain' => $routePrefix,
        'pageIcon' => $entity->getIcon(),

        // Serialization
        'serializableProperties' => $this->getSerializableProperties($entity),

        // Operations
        'operations' => [
            'index' => $entity->isControllerOperationIndex(),
            'new' => $entity->isControllerOperationNew(),
            'edit' => $entity->isControllerOperationEdit(),
            'delete' => $entity->isControllerOperationDelete(),
            'show' => $entity->isControllerOperationShow(),
            'apiSearch' => $this->hasApiGetCollection($entity),
        ],

        // List configuration from GeneratorProperty
        'listProperties' => $this->getListProperties($entity),
        'hasSearchableProperties' => $entity->hasSearchableProperties(),
        'hasFilterableProperties' => $entity->hasFilterableProperties(),
        'hasSortableProperties' => $entity->hasSortableProperties(),
        'searchableFields' => $this->getSearchableFields($entity),
        'filterableFields' => $this->getFilterableFields($entity),
        'sortableFields' => $this->getSortableFields($entity),

        // Entity configuration
        'hasOrganization' => $entity->isHasOrganization(),
        'hasOwner' => $this->hasOwnerProperty($entity),

        // Namespace
        'namespace' => 'App\\Controller',
        'generatedNamespace' => 'App\\Controller\\Generated',
        'baseControllerClass' => 'BaseApiController',
    ];
}
```

#### getSerializableProperties()

```php
protected function getSerializableProperties(GeneratorEntity $entity): array
{
    $properties = [];

    foreach ($entity->getProperties() as $property) {
        // Skip DTO-excluded properties
        if ($property->isDtoExcluded()) {
            continue;
        }

        $propertyName = $property->getPropertyName();
        $propertyType = $property->getPropertyType();

        $properties[] = [
            'name' => $propertyName,
            'type' => $propertyType,
            'getter' => $this->genmaxExtension->getGetterName($propertyName),
            'serialization' => $this->getSerializationLogic($property),
            'isRelationship' => $property->isRelationship(),
            'nullable' => $property->isNullable(),
        ];
    }

    return $properties;
}
```

#### getSerializationLogic()

```php
protected function getSerializationLogic(GeneratorProperty $property): string
{
    $type = $property->getPropertyType();

    // UUID
    if ($type === 'uuid') {
        return "?->toString()";
    }

    // DateTime
    if (in_array($type, ['datetime', 'datetime_immutable', 'date', 'datetimetz'])) {
        return "?->format('M d, Y')";
    }

    // Boolean - direct access
    if ($type === 'boolean') {
        return "";
    }

    // Relationship - NEVER GUESS the display property
    if ($property->isRelationship()) {
        // Use configured display property or fail safely
        $displayProperty = $property->getRelationshipDisplayProperty();
        if (!$displayProperty) {
            // If no display property configured, serialize the ID only
            return "?->getId()?->toString()";
        }

        // Use GenmaxExtension to get proper getter name
        $getterName = $this->genmaxExtension->getGetterName($displayProperty);
        return "?->{$getterName}()";
    }

    // Default - direct access
    return "";
}
```

#### getListProperties()

```php
protected function getListProperties(GeneratorEntity $entity): array
{
    $properties = [];

    foreach ($entity->getProperties() as $property) {
        if (!$property->isShowInList()) {
            continue;
        }

        $propertyName = $property->getPropertyName();

        $properties[] = [
            'name' => $propertyName,
            'label' => $property->getPropertyLabel(),
            'type' => $property->getPropertyType(),
            'sortable' => $property->isSortable(),
            'searchable' => $property->isSearchable(),
            'filterable' => $property->isFilterable(),

            // Advanced filter configuration
            'filterStrategy' => $property->getFilterStrategy(),
            'filterOrderable' => $property->isFilterOrderable(),
            'filterBoolean' => $property->isFilterBoolean(),
            'filterDate' => $property->isFilterDate(),
            'filterNumericRange' => $property->isFilterNumericRange(),
            'filterExists' => $property->isFilterExists(),

            // Display
            'getter' => $this->genmaxExtension->getGetterName($propertyName),
        ];
    }

    return $properties;
}
```

#### validateConfiguration()

```php
protected function validateConfiguration(GeneratorEntity $entity): void
{
    $warnings = [];

    // Check if showInList is set for at least one property
    $hasListProperties = false;
    foreach ($entity->getProperties() as $property) {
        if ($property->isShowInList()) {
            $hasListProperties = true;
            break;
        }
    }

    if (!$hasListProperties) {
        $warnings[] = "No properties configured to show in list. Set showInList=true on at least one property.";
    }

    // Check if index operation is enabled but no API GetCollection
    if ($entity->isControllerOperationIndex() && !$this->hasApiGetCollection($entity)) {
        $warnings[] = "Index page requires 'GetCollection' in apiOperations for data fetching.";
    }

    // Check if sortable properties have filterOrderable set
    foreach ($entity->getProperties() as $property) {
        if ($property->isSortable() && !$property->isFilterOrderable()) {
            $warnings[] = "Property '{$property->getPropertyName()}' is sortable but filterOrderable is false. Set both for consistency.";
        }
    }

    if (!empty($warnings)) {
        foreach ($warnings as $warning) {
            $this->logger->warning('[GENMAX] Configuration warning', [
                'entity' => $entity->getEntityName(),
                'warning' => $warning
            ]);
        }
    }
}
```

---

## Template Structure

### Base Controller Template

**File:** `app/templates/genmax/php/controller_generated.php.twig`

**Key Sections:**

1. **Header & Namespace**
   ```php
   namespace {{ generatedNamespace }};

   /**
    * Generated Base Controller for {{ entityName }}
    * ⚠️ WARNING: This file is ALWAYS regenerated by Genmax
    * DO NOT EDIT THIS FILE - Edit {{ entityName }}Controller instead
    */
   abstract class {{ entityName }}ControllerGenerated extends {{ baseControllerClass }}
   ```

2. **Constructor with Dependencies**
   ```php
   public function __construct(
       protected readonly EntityManagerInterface $entityManager,
       protected readonly {{ repositoryClass }} $repository,
       protected readonly ListPreferencesService $listPreferencesService,
       protected readonly TranslatorInterface $translator,
   ) {}
   ```

3. **Abstract Methods Implementation**
   ```php
   protected function getRepository(): {{ repositoryClass }}
   protected function getEntityPluralName(): string
   protected function entityToArray(object $entity): array
   ```

4. **CRUD Actions** (conditionally generated based on `operations`)
   - `indexAction()` - List page
   - `apiSearchAction()` - API endpoint for list data
   - `newFormAction()` + `createAction()` - Create form
   - `editFormAction()` + `updateAction()` - Edit form
   - `deleteAction()` - Delete entity
   - `showAction()` - Detail page

5. **Lifecycle Hooks** (empty, for customization)
   ```php
   protected function initializeNewEntity({{ entityName }} $entity): void
   protected function beforeCreate({{ entityName }} $entity): void
   protected function afterCreate({{ entityName }} $entity): void
   protected function beforeUpdate({{ entityName }} $entity): void
   protected function afterUpdate({{ entityName }} $entity): void
   protected function beforeDelete({{ entityName }} $entity): void
   protected function afterDelete(): void
   ```

### Extension Controller Template

**File:** `app/templates/genmax/php/controller_extension.php.twig`

**Key Sections:**

1. **Header & Route Prefix**
   ```php
   #[Route('/{{ routePrefix }}')]
   final class {{ entityName }}Controller extends {{ entityName }}ControllerGenerated
   ```

2. **Route Definitions** (conditionally generated)
   ```php
   #[Route('', name: '{{ routePrefix }}_index', methods: ['GET'])]
   public function index(): Response

   #[Route('/search', name: '{{ routePrefix }}_search', methods: ['GET'])]
   public function apiSearch(Request $request): Response

   #[Route('/new', name: '{{ routePrefix }}_new', methods: ['GET', 'POST'])]
   public function new(Request $request): Response

   // etc.
   ```

3. **Placeholder for Custom Methods**
   ```php
   // Example: Override lifecycle hooks
   // protected function afterCreate({{ entityName }} ${{ entityVariable }}): void
   // {
   //     // Send notification, trigger event, custom logic
   // }
   ```

---

## List Property Configuration

### Property Usage Matrix

| Property | Grid View | List View | Table View | Filters/Search |
|----------|-----------|-----------|------------|----------------|
| `showInList` | Show card field | Show item field | Show column | - |
| `sortable` | Sort icon | Sort icon | Sortable header | - |
| `searchable` | - | - | - | Global search |
| `filterable` | Filter dropdown | Filter dropdown | Filter dropdown | Filter panel |
| `filterStrategy` | - | - | - | Input type |
| `filterOrderable` | Sort dropdown | Sort dropdown | Header click | Sort dropdown |
| `filterBoolean` | - | - | - | Yes/No toggle |
| `filterDate` | - | - | - | Date range picker |
| `filterNumericRange` | - | - | - | Min/Max inputs |
| `filterExists` | - | - | - | "Has value" checkbox |

### Index Action Template Data

```php
protected function indexAction(): Response
{
    $this->denyAccessUnlessGranted({{ voterClass }}::LIST);

    $preferences = $this->listPreferencesService->getEntityPreferences('{{ entityPluralVariable }}');
    $savedView = $preferences['view'] ?? 'grid';

    return $this->render('{{ routePrefix }}/index.html.twig', [
        'entities' => [],  // Loaded via API
        'entity_name' => '{{ entityVariable }}',
        'entity_name_plural' => '{{ entityPluralVariable }}',
        'page_icon' => '{{ pageIcon }}',
        'default_view' => $savedView,

        // List configuration from GeneratorProperty
        'enable_search' => {{ hasSearchableProperties ? 'true' : 'false' }},
        'enable_filters' => {{ hasFilterableProperties ? 'true' : 'false' }},
        'enable_sorting' => {{ hasSortableProperties ? 'true' : 'false' }},
        'enable_create_button' => true,

        // Property metadata for client-side rendering
        'list_fields' => {{ listProperties|json_encode|raw }},
        'searchable_fields' => {{ searchableFields|json_encode|raw }},
        'filterable_fields' => {{ filterableFields|json_encode|raw }},
        'sortable_fields' => {{ sortableFields|json_encode|raw }},
    ]);
}
```

### Filter Configuration Example

```php
// Generated filter configuration
[
    [
        'field' => 'status',
        'label' => 'Status',
        'type' => 'boolean',
        'options' => ['Active', 'Inactive']
    ],
    [
        'field' => 'createdAt',
        'label' => 'Created Date',
        'type' => 'dateRange',
        'operators' => ['after', 'before', 'between']
    ],
    [
        'field' => 'price',
        'label' => 'Price',
        'type' => 'numberRange',
        'operators' => ['gte', 'lte', 'between']
    ],
    [
        'field' => 'name',
        'label' => 'Name',
        'type' => 'text',
        'strategy' => 'partial'  // From filterStrategy
    ]
]
```

---

## Best Practices

### Security

1. **Always Use Voters**
   ```php
   $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);
   ```

2. **CSRF Protection**
   ```php
   if (!$this->isCsrfTokenValid('delete' . $entity->getId(), $token)) {
       throw new InvalidCsrfTokenException();
   }
   ```

3. **Organization Filtering**
   - Automatic via Doctrine filter (TenantDataFilter)
   - No manual filtering needed in controllers

### Form Handling

1. **Separate GET and POST**
   ```php
   if ($request->isMethod('POST')) {
       return $this->createAction($request);
   }
   return $this->newFormAction($request);
   ```

2. **Validation**
   ```php
   $form->handleRequest($request);
   if ($form->isSubmitted() && $form->isValid()) {
       // Process
   }
   // Re-render with errors (Symfony auto-includes errors)
   return $this->render('...', ['form' => $form]);
   ```

### Response Handling

1. **Redirects**
   ```php
   // Use SEE_OTHER (303) after POST
   return $this->redirectToRoute('course_index', [], Response::HTTP_SEE_OTHER);

   // Smart referer detection
   return $this->redirectToRefererOrRoute($request, 'course_index');
   ```

2. **Flash Messages**
   ```php
   $this->addFlash('success', $this->translator->trans(
       'course.flash.created_successfully',
       ['%name%' => $course->getName()],
       'course'
   ));
   ```

### Naming Conventions

1. **Always Use GenmaxExtension**
   ```php
   // NEVER guess or hardcode
   $getter = $this->genmaxExtension->getGetterName($propertyName);
   $plural = $this->genmaxExtension->toPlural($entityName);
   ```

2. **Route Naming**
   ```php
   // Pattern: {slug}_{action}
   // Examples:
   'course_index'
   'course_new'
   'course_edit'
   'course_delete'
   'course_show'
   'course_search'
   ```

---

## Implementation Checklist

### Phase 1: Preparation

- [ ] **Update GeneratorEntity**
  - [ ] Add 6 new `protected` properties for controller operations
  - [ ] Add helper methods (`hasSearchableProperties()`, etc.)
  - [ ] Update `getSlug()` to use `Utils::stringToSlug()`
  - [ ] Create migration for new fields

- [ ] **Update services.yaml**
  - [ ] Add `genmax.paths.controller_dir`
  - [ ] Add `genmax.paths.controller_generated_dir`
  - [ ] Add `genmax.templates.controller_generated`
  - [ ] Add `genmax.templates.controller_extension`

### Phase 2: Core Service

- [ ] **Create ControllerGenerator Service**
  - [ ] Implement constructor with dependencies
  - [ ] Implement `generate()` method
  - [ ] Implement `generateBaseController()`
  - [ ] Implement `generateExtensionController()`
  - [ ] Implement `buildTemplateContext()`

- [ ] **Implement Helper Methods**
  - [ ] `getSerializableProperties()`
  - [ ] `getSerializationLogic()`
  - [ ] `getListProperties()`
  - [ ] `getSearchableFields()`
  - [ ] `getFilterableFields()`
  - [ ] `getSortableFields()`
  - [ ] `hasApiGetCollection()`
  - [ ] `hasOwnerProperty()`
  - [ ] `validateConfiguration()`

### Phase 3: Twig Templates

- [ ] **Create Base Controller Template**
  - [ ] `app/templates/genmax/php/controller_generated.php.twig`
  - [ ] Header & documentation
  - [ ] Constructor & dependencies
  - [ ] Abstract method implementations
  - [ ] `indexAction()` with list configuration
  - [ ] `apiSearchAction()`
  - [ ] `newFormAction()` + `createAction()`
  - [ ] `editFormAction()` + `updateAction()`
  - [ ] `deleteAction()`
  - [ ] `showAction()`
  - [ ] Empty lifecycle hooks

- [ ] **Create Extension Controller Template**
  - [ ] `app/templates/genmax/php/controller_extension.php.twig`
  - [ ] Header & route prefix
  - [ ] Route definitions (conditional)
  - [ ] Placeholder for custom methods

### Phase 4: Integration

- [ ] **Update GenmaxOrchestrator**
  - [ ] Set `CONTROLLER_ACTIVE = true`
  - [ ] Add controller generation step
  - [ ] Add error handling
  - [ ] Add progress tracking

- [ ] **Testing**
  - [ ] Test with simple entity (no relationships)
  - [ ] Test with relationships
  - [ ] Test with all operations enabled
  - [ ] Test with some operations disabled
  - [ ] Test with different property configurations
  - [ ] Verify generated code compiles
  - [ ] Verify routes work
  - [ ] Verify security checks work

### Phase 5: Documentation

- [ ] **Update Genmax Documentation**
  - [ ] Add controller generation to workflow
  - [ ] Document configuration options
  - [ ] Add examples
  - [ ] Update Quick Start guide

- [ ] **Code Comments**
  - [ ] Add PHPDoc to all methods
  - [ ] Add inline comments for complex logic
  - [ ] Add @generated tags

---

## Future Enhancements

### Phase 4: Template Generation

Generate Twig templates for:
- `{{ routePrefix }}/index.html.twig` - List page with grid/list/table views
- `{{ routePrefix }}/_form_modal.html.twig` - Modal form
- `{{ routePrefix }}/show.html.twig` - Detail page

### PropertyDisplayService

Create dedicated service for property metadata analysis:
```php
namespace App\Service\Genmax;

class PropertyDisplayService
{
    public function getDisplayConfig(GeneratorEntity $entity): array
    public function getListConfig(GeneratorEntity $entity): array
    public function getSearchConfig(GeneratorEntity $entity): array
    public function getFilterConfig(GeneratorEntity $entity): array
    public function getSortConfig(GeneratorEntity $entity): array
}
```

### Advanced Filter Generation

- Generate filter components based on property configuration
- Support for complex filter combinations (AND/OR)
- Saved filter presets
- Filter sharing between users

---

## References

### Source Files

- `/app/src/Service/Genmax/` - Genmax generators
- `/app/src/Entity/Generator/GeneratorEntity.php` - Entity configuration
- `/app/src/Entity/Generator/GeneratorProperty.php` - Property configuration
- `/app/src/Service/Utils.php` - Naming utilities
- `/app/src/Service/Genmax/GenmaxExtension.php` - Twig naming functions
- `/app/src/MultiTenant/TenantEntityProcessor.php` - Organization/owner assignment
- `/app/src/Controller/Base/BaseApiController.php` - Shared controller utilities

### Documentation

- `/app/docs/Genmax/GENMAX.md` - Genmax overview
- `/app/docs/Genmax/QUICK_START.md` - Quick start guide
- `/app/docs/DEVELOPMENT_WORKFLOW.md` - Development workflow

---

**Last Updated:** 2025-01-25
**Status:** ✅ Ready for Implementation
**Next Step:** Begin Phase 1 - Preparation
