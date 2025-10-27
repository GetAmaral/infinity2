# Genmax Navigation Generator - Implementation Plan

**Version:** 1.0
**Status:** 📋 READY FOR IMPLEMENTATION
**Created:** October 2025
**Priority:** HIGH

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Objectives](#objectives)
3. [Architecture](#architecture)
4. [Implementation Steps](#implementation-steps)
5. [Database Schema](#database-schema)
6. [File Structure](#file-structure)
7. [Configuration](#configuration)
8. [Testing](#testing)
9. [Usage Examples](#usage-examples)
10. [Troubleshooting](#troubleshooting)

---

## Overview

Implement automatic navigation menu generation for Genmax-generated entities following the **Generated/Extended pattern**. This system will:

- ✅ Automatically create navigation menu items from `generator_entity` table
- ✅ Use existing `menuGroup` and `menuOrder` fields (no new fields needed for visibility)
- ✅ Follow Genmax Generated/Extended architecture pattern
- ✅ Support automatic permission checking via Security Voters
- ✅ Provide full translation support (no hardcoded labels)
- ✅ Allow manual customization through extension files
- ✅ Support smart defaults and automatic sorting

---

## Objectives

### Primary Goals

1. **Zero Configuration**: If entity has `menuGroup` or `menuOrder`, it appears in navigation automatically
2. **Generated/Extended Pattern**: NavigationConfig follows Genmax architecture (base + extension)
3. **Translation Only**: All labels use translation keys, no hardcoded text
4. **Smart Defaults**: Automatic grouping and ordering when partially configured
5. **Permission Integration**: Automatic voter-based permission checking
6. **Easy Customization**: Extension file allows manual modifications without breaking regeneration

### Success Criteria

- [ ] Navigation automatically generated from database
- [ ] Permission checking works via voters
- [ ] Translation keys resolve correctly
- [ ] Manual items (home, student courses, admin) still work
- [ ] Extension file allows customization
- [ ] Regeneration doesn't break manual customizations

---

## Architecture

### Navigation Visibility Logic

**Rule**: Entity appears in navigation if `menuGroup IS NOT NULL OR menuOrder IS NOT NULL`

```php
// Entity with menuGroup → Shows in that group
menuGroup = 'CRM', menuOrder = 10 → Shows in CRM group, order 10

// Entity with only menuOrder → Shows in "System" group
menuGroup = NULL, menuOrder = 50 → Shows in System group, order 50

// Entity with only menuGroup → Shows last in group
menuGroup = 'Sales', menuOrder = NULL → Shows in Sales group, order 9999

// Entity with neither → Hidden from navigation
menuGroup = NULL, menuOrder = NULL → Not shown
```

### Sorting Rules

**Within each group**, items are sorted by:

1. **Primary**: `menuOrder` ASC (null treated as 9999)
2. **Secondary**: `entityLabel` ASC (alphabetical)

**Groups** are sorted alphabetically (CRM, Reports, Sales, System).

### Generated/Extended Pattern

```
NavigationConfigGenerated.php (Generated folder)
├── ALWAYS REGENERATED
├── Contains ALL menu items from database
├── Organized by groups with dividers
└── Never edit directly

NavigationConfig.php (Service folder)
├── CREATED ONCE, safe to edit
├── Extends NavigationConfigGenerated
├── Merges manual + generated items
├── Allows full customization
└── Survives regeneration
```

---

## Implementation Steps

### Step 1: Update GeneratorEntity

**File**: `app/src/Entity/Generator/GeneratorEntity.php`

**Changes Required**:

1. **Make `menuOrder` nullable** (currently might be int with default)
2. **Add `navigationLabel` field** (optional metadata for future use)
3. **Add helper methods** for navigation logic

#### 1.1: Update `menuOrder` Field

```php
// BEFORE (around line 219-222):
#[ORM\Column(type: 'integer', options: ['default' => 100])]
#[Assert\Range(min: 0, max: 9999)]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
private int $menuOrder = 100;

// AFTER:
#[ORM\Column(type: 'integer', nullable: true)]
#[Assert\Range(min: 0, max: 9999)]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
private ?int $menuOrder = null;  // Changed to nullable
```

#### 1.2: Add `navigationLabel` Field

**Location**: After `menuOrder` field (around line 223)

```php
// ====================================
// NAVIGATION (3 fields - was 2)
// ====================================

#[ORM\Column(length: 100, nullable: true)]
#[Assert\Length(max: 100)]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
private ?string $navigationLabel = null;  // NEW FIELD - Future: Custom translation key metadata
```

#### 1.3: Add Navigation Helper Methods

**Location**: End of class, before closing brace

```php
/**
 * Determine if entity should appear in navigation
 *
 * Rules:
 * - If menuGroup OR menuOrder is set → show in navigation
 * - If NEITHER is set → do NOT show in navigation
 */
public function isShownInNavigation(): bool
{
    return $this->menuGroup !== null || $this->menuOrder !== null;
}

/**
 * Get effective menu group
 *
 * Rules:
 * - If menuGroup is set → use it
 * - If menuGroup is null but menuOrder is set → use "System"
 * - Otherwise → return null (not shown)
 */
public function getEffectiveMenuGroup(): ?string
{
    if ($this->menuGroup !== null) {
        return $this->menuGroup;
    }

    if ($this->menuOrder !== null) {
        return 'System';  // Default group when only menuOrder is set
    }

    return null;
}

/**
 * Get effective menu order
 *
 * Rules:
 * - If menuOrder is set → use it
 * - If menuOrder is null → use 9999 (appears last in group)
 */
public function getEffectiveMenuOrder(): int
{
    return $this->menuOrder ?? 9999;
}

// Add getter/setter for navigationLabel
public function getNavigationLabel(): ?string
{
    return $this->navigationLabel;
}

public function setNavigationLabel(?string $navigationLabel): self
{
    $this->navigationLabel = $navigationLabel;
    return $this;
}
```

#### 1.4: Create Migration

```bash
docker-compose exec app php bin/console make:migration
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

---

### Step 2: Create NavigationGenerator Service

**File**: `app/src/Service/Genmax/NavigationGenerator.php`

**Purpose**: Generate NavigationConfig files following Genmax Generated/Extended pattern

```php
<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use App\Repository\Generator\GeneratorEntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

final class NavigationGenerator
{
    public function __construct(
        private readonly string $projectDir,
        #[Autowire(param: 'genmax.paths')]
        private readonly array $paths,
        private readonly GeneratorEntityRepository $generatorEntityRepository,
        private readonly Environment $twig,
        private readonly SmartFileWriter $smartFileWriter,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Generate NavigationConfig files (Generated + Extension)
     *
     * @return array<string> Array of generated file paths
     */
    public function generate(): array
    {
        $this->logger->info('[NavigationGenerator] Starting navigation config generation');

        $generatedFiles = [];

        // 1. Generate NavigationConfigGenerated.php (ALWAYS OVERWRITTEN)
        $generatedFiles[] = $this->generateNavigationConfigGenerated();

        // 2. Generate NavigationConfig.php (CREATED ONCE, safe to edit)
        $extensionFile = $this->generateNavigationConfigExtension();
        if ($extensionFile) {
            $generatedFiles[] = $extensionFile;
        }

        $this->logger->info('[NavigationGenerator] Navigation config generation completed', [
            'files_count' => count($generatedFiles),
        ]);

        return $generatedFiles;
    }

    /**
     * Generate NavigationConfigGenerated.php
     *
     * Contains ALL menu items from database, organized by groups
     */
    private function generateNavigationConfigGenerated(): string
    {
        // Fetch all entities that should appear in navigation
        $allEntities = $this->generatorEntityRepository->findAll();
        $navEntities = array_filter($allEntities, fn($e) => $e->isShownInNavigation());

        // Group and sort entities
        $groupedItems = $this->buildGroupedMenuItems($navEntities);

        // Render template
        $content = $this->twig->render('genmax/php/navigation_config_generated.php.twig', [
            'grouped_items' => $groupedItems,
            'generation_timestamp' => new \DateTimeImmutable(),
        ]);

        // Write file
        $filePath = sprintf(
            '%s/%s/NavigationConfigGenerated.php',
            $this->projectDir,
            $this->paths['navigation_config_generated_dir']
        );

        $this->smartFileWriter->writeFile($filePath, $content, overwrite: true);

        $this->logger->info('[NavigationGenerator] Generated NavigationConfigGenerated.php', [
            'file' => $filePath,
            'groups_count' => count($groupedItems),
        ]);

        return $filePath;
    }

    /**
     * Generate NavigationConfig.php (extension)
     *
     * Created ONCE, safe to customize
     */
    private function generateNavigationConfigExtension(): ?string
    {
        $filePath = sprintf(
            '%s/%s/NavigationConfig.php',
            $this->projectDir,
            $this->paths['navigation_config_dir']
        );

        // Only create if doesn't exist
        if (file_exists($filePath)) {
            $this->logger->info('[NavigationGenerator] Extension file already exists, skipping', [
                'file' => $filePath,
            ]);
            return null;
        }

        // Render template
        $content = $this->twig->render('genmax/php/navigation_config_extension.php.twig', [
            'generation_timestamp' => new \DateTimeImmutable(),
        ]);

        $this->smartFileWriter->writeFile($filePath, $content, overwrite: false);

        $this->logger->info('[NavigationGenerator] Created NavigationConfig.php extension', [
            'file' => $filePath,
        ]);

        return $filePath;
    }

    /**
     * Build grouped and sorted menu items
     *
     * @param GeneratorEntity[] $entities
     * @return array<string, array> Grouped menu items
     */
    private function buildGroupedMenuItems(array $entities): array
    {
        $grouped = [];

        foreach ($entities as $entity) {
            $group = $entity->getEffectiveMenuGroup();
            if ($group === null) {
                continue;  // Should not happen due to isShownInNavigation() filter
            }

            $slug = $this->getEntitySlug($entity->getEntityName());
            $voterClass = $entity->getEntityName() . 'Voter';

            $grouped[$group][] = [
                'key' => $slug,
                'entity_name' => $entity->getEntityName(),
                'label_translation_key' => "{$slug}.plural",  // Translation key
                'route' => "{$slug}_index",
                'icon' => $entity->getIcon(),
                'voter_class' => $voterClass,
                'permission_constant' => 'LIST',
                'translation_domain' => $slug,
                'menu_order' => $entity->getEffectiveMenuOrder(),
                'entity_label' => $entity->getEntityLabel(),  // For sorting
            ];
        }

        // Sort groups by name (alphabetically)
        ksort($grouped);

        // Sort items within each group by menuOrder ASC, then entityLabel ASC
        foreach ($grouped as $group => &$items) {
            usort($items, function ($a, $b) {
                // Primary sort: menuOrder
                if ($a['menu_order'] !== $b['menu_order']) {
                    return $a['menu_order'] <=> $b['menu_order'];
                }
                // Secondary sort: entityLabel (alphabetical, case-insensitive)
                return strcasecmp($a['entity_label'], $b['entity_label']);
            });
        }

        return $grouped;
    }

    /**
     * Convert PascalCase to snake_case
     */
    private function getEntitySlug(string $entityName): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $entityName));
    }
}
```

---

### Step 3: Create Twig Templates

#### Template 1: navigation_config_generated.php.twig

**File**: `app/templates/genmax/php/navigation_config_generated.php.twig`

**Purpose**: Master template for NavigationConfigGenerated.php

```twig
<?php

/**
 * THIS FILE IS AUTO-GENERATED - DO NOT EDIT
 *
 * Generated by: Genmax NavigationGenerator
 * Generated at: {{ generation_timestamp|date('Y-m-d H:i:s') }}
 *
 * This file contains ALL menu items from generator_entity table.
 * To customize navigation, edit NavigationConfig.php (extends this class).
 */

declare(strict_types=1);

namespace App\Service\Generated;

abstract class NavigationConfigGenerated
{
    /**
     * Get generated menu items from database entities
     *
     * @return array<string, array{
     *   label: string,
     *   route: string,
     *   icon: string,
     *   permission: string,
     *   translation_domain: string
     * }>
     */
    protected function getGeneratedMenuItems(): array
    {
        return [
{% for group_name, items in grouped_items %}
            // ============================================
            // {{ group_name }} Section
            // ============================================
            '{{ group_name|lower }}_section_divider' => [
                'divider_before' => true,
                'section_title' => 'nav.section.{{ group_name|lower }}',
                // Permission: Visible if user has access to at least one item in section
            ],
{% for item in items %}
            '{{ item.key }}' => [
                'label' => '{{ item.label_translation_key }}',  // Translation: {{ item.translation_domain }}.plural
                'route' => '{{ item.route }}',
                'icon' => '{{ item.icon }}',
                'permission' => \App\Security\Voter\{{ item.voter_class }}::{{ item.permission_constant }},
                'translation_domain' => '{{ item.translation_domain }}',
            ],
{% endfor %}

{% endfor %}
        ];
    }

    /**
     * Get generated user menu items (empty by default)
     *
     * Override in extension to add user menu items from database
     *
     * @return array<string, array>
     */
    protected function getGeneratedUserMenuItems(): array
    {
        return [];
    }
}
```

#### Template 2: navigation_config_extension.php.twig

**File**: `app/templates/genmax/php/navigation_config_extension.php.twig`

**Purpose**: Extension template for NavigationConfig.php (created once, safe to edit)

```twig
<?php

/**
 * NavigationConfig - Customizable Navigation Configuration
 *
 * This file extends NavigationConfigGenerated and allows manual customization.
 *
 * Generated at: {{ generation_timestamp|date('Y-m-d H:i:s') }}
 *
 * SAFE TO EDIT:
 * - Add custom menu items
 * - Override group order
 * - Add custom sections
 * - Modify existing items
 * - Add conditional logic
 * - Reorder sections
 *
 * IMPORTANT:
 * - Do NOT edit NavigationConfigGenerated.php (will be overwritten)
 * - This file is created ONCE and safe to customize
 * - getGeneratedMenuItems() provides database-driven items
 */

declare(strict_types=1);

namespace App\Service;

use App\Service\Generated\NavigationConfigGenerated;

final class NavigationConfig extends NavigationConfigGenerated
{
    /**
     * Get main navigation menu structure
     *
     * Combines:
     * 1. Manual/hardcoded items (home, student courses)
     * 2. Generated items from database (from getGeneratedMenuItems())
     * 3. Admin section
     *
     * @return array<string, array{
     *   label: string,
     *   route: string,
     *   icon: string,
     *   permission?: string,
     *   role?: string,
     *   divider_before?: bool,
     *   divider_after?: bool,
     *   section_title?: string
     * }>
     */
    public function getMainMenu(): array
    {
        // ============================================
        // MANUAL ITEMS (always at top)
        // ============================================
        $manualItems = [
            'home' => [
                'label' => 'nav.home',
                'route' => 'app_home',
                'icon' => 'bi-house',
                // No permission required - all authenticated users
            ],
            'student_courses' => [
                'label' => 'nav.my.courses',
                'route' => 'student_courses',
                'icon' => 'bi-mortarboard',
                'role' => 'ROLE_STUDENT',
            ],
        ];

        // ============================================
        // GENERATED ITEMS (from database)
        // ============================================
        $generatedItems = $this->getGeneratedMenuItems();

        // ============================================
        // ADMIN SECTION (always at bottom)
        // ============================================
        $adminItems = [
            'admin_section_divider' => [
                'divider_before' => true,
                'section_title' => 'nav.admin.section',
                'role' => 'ROLE_ADMIN',
            ],
            'admin_audit' => [
                'label' => 'nav.admin.audit.log',
                'route' => 'admin_audit_index',
                'icon' => 'bi-clipboard-data',
                'role' => 'ROLE_ADMIN',
            ],
            'admin_analytics' => [
                'label' => 'nav.admin.audit.analytics',
                'route' => 'admin_audit_analytics',
                'icon' => 'bi-graph-up',
                'role' => 'ROLE_ADMIN',
            ],
        ];

        // ============================================
        // MERGE & RETURN
        // ============================================
        return array_merge($manualItems, $generatedItems, $adminItems);
    }

    /**
     * Get user profile dropdown menu structure
     *
     * @return array<string, array>
     */
    public function getUserMenu(): array
    {
        // Manual user menu items
        $manualItems = [
            'settings' => [
                'label' => 'nav.settings',
                'route' => 'app_settings',
                'icon' => 'bi-gear',
            ],
            'terms' => [
                'label' => 'nav.terms',
                'route' => 'app_terms',
                'icon' => 'bi-file-text',
            ],
            'logout_divider' => [
                'divider_before' => true,
            ],
            'logout' => [
                'label' => 'auth.logout',
                'route' => 'app_logout',
                'icon' => 'bi-box-arrow-right',
                'css_class' => 'text-danger',
            ],
        ];

        // Generated user menu items (if any)
        $generatedItems = $this->getGeneratedUserMenuItems();

        return array_merge($manualItems, $generatedItems);
    }

    /**
     * Check if a menu item should be visible based on permissions
     *
     * @param array{
     *   permission?: string,
     *   role?: string,
     *   divider_before?: bool,
     *   divider_after?: bool,
     *   section_title?: string
     * } $item
     */
    public function isMenuItemVisible(array $item, callable $isGranted): bool
    {
        // Dividers and section titles are visible if their role requirement is met
        if (isset($item['divider_before']) || isset($item['divider_after']) || isset($item['section_title'])) {
            if (isset($item['role'])) {
                return $isGranted($item['role']);
            }
            return true; // Dividers without role are always visible
        }

        // Check role-based access (simple RBAC)
        if (isset($item['role'])) {
            if (!$isGranted($item['role'])) {
                return false;
            }
        }

        // Check voter permission (attribute-based access control)
        if (isset($item['permission'])) {
            if (!$isGranted($item['permission'])) {
                return false;
            }
        }

        // If no permission or role specified, item is visible to all authenticated users
        return true;
    }

    /**
     * Get translation domain for menu item
     *
     * @param array{translation_domain?: string} $item
     */
    public function getTranslationDomain(array $item): string
    {
        return $item['translation_domain'] ?? 'messages';
    }
}
```

---

### Step 4: Update GenmaxOrchestrator

**File**: `app/src/Service/Genmax/GenmaxOrchestrator.php`

#### 4.1: Add Feature Flag

**Location**: Around line 28-43 (with other feature flags)

```php
// Feature flags
private const ENTITY_ACTIVE = true;
private const API_ACTIVE = true;
private const DTO_ACTIVE = true;
private const STATE_PROCESSOR_ACTIVE = true;
private const REPOSITORY_ACTIVE = true;
private const STATE_PROVIDER_ACTIVE = true;
private const CONTROLLER_ACTIVE = true;
private const VOTER_ACTIVE = true;
private const FORM_ACTIVE = true;
private const TEMPLATE_ACTIVE = true;
private const NAVIGATION_ACTIVE = true;  // ADD THIS LINE
private const TESTS_ACTIVE = false;
```

#### 4.2: Inject NavigationGenerator

**Location**: Constructor (around line 50-70)

```php
public function __construct(
    private readonly string $projectDir,
    #[Autowire(param: 'genmax.paths')]
    private readonly array $paths,
    private readonly GeneratorEntityRepository $generatorEntityRepository,
    private readonly EntityManagerInterface $entityManager,
    private readonly BackupService $backupService,
    private readonly EntityGenerator $entityGenerator,
    private readonly ApiGenerator $apiGenerator,
    private readonly DtoGenerator $dtoGenerator,
    private readonly StateProcessorGenerator $stateProcessorGenerator,
    private readonly RepositoryGenerator $repositoryGenerator,
    private readonly StateProviderGenerator $stateProviderGenerator,
    private readonly ControllerGenerator $controllerGenerator,
    private readonly VoterGenerator $voterGenerator,
    private readonly FormGenerator $formGenerator,
    private readonly TemplateGenerator $templateGenerator,
    private readonly NavigationGenerator $navigationGenerator,  // ADD THIS LINE
    private readonly LoggerInterface $logger
) {}
```

#### 4.3: Add Navigation Generation

**Location**: End of `generate()` method, after all entity generation (around line 270)

```php
public function generate(?string $entityName = null): int
{
    // ... existing entity generation code ...

    // ============================================
    // NAVIGATION GENERATION (runs after all entities)
    // ============================================

    // Navigation (ACTIVE) - Always regenerate after entity changes
    if (self::NAVIGATION_ACTIVE) {
        try {
            $this->logger->info('[GENMAX] Generating navigation configuration');
            $files = $this->navigationGenerator->generate();
            $generatedFiles = array_merge($generatedFiles, $files);
        } catch (\Throwable $e) {
            $this->logger->error('[GENMAX] Navigation generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    return count($generatedFiles);
}
```

#### 4.4: Update countActiveGenerators()

**Location**: Around line 440

```php
private function countActiveGenerators(): int
{
    $count = 0;
    $count += self::ENTITY_ACTIVE ? 1 : 0;
    $count += self::API_ACTIVE ? 1 : 0;
    $count += self::DTO_ACTIVE ? 1 : 0;
    $count += self::STATE_PROCESSOR_ACTIVE ? 1 : 0;
    $count += self::REPOSITORY_ACTIVE ? 1 : 0;
    $count += self::STATE_PROVIDER_ACTIVE ? 1 : 0;
    $count += self::CONTROLLER_ACTIVE ? 1 : 0;
    $count += self::VOTER_ACTIVE ? 1 : 0;
    $count += self::FORM_ACTIVE ? 1 : 0;
    $count += self::TEMPLATE_ACTIVE ? 1 : 0;
    $count += self::NAVIGATION_ACTIVE ? 1 : 0;  // ADD THIS LINE
    $count += self::TESTS_ACTIVE ? 1 : 0;
    return $count;
}
```

#### 4.5: Update getActiveGenerators()

**Location**: Around line 460

```php
private function getActiveGenerators(): array
{
    $active = [];
    if (self::ENTITY_ACTIVE) $active[] = 'entity';
    if (self::API_ACTIVE) $active[] = 'api';
    if (self::DTO_ACTIVE) $active[] = 'dto';
    if (self::STATE_PROCESSOR_ACTIVE) $active[] = 'state_processor';
    if (self::REPOSITORY_ACTIVE) $active[] = 'repository';
    if (self::STATE_PROVIDER_ACTIVE) $active[] = 'state_provider';
    if (self::CONTROLLER_ACTIVE) $active[] = 'controller';
    if (self::VOTER_ACTIVE) $active[] = 'voter';
    if (self::FORM_ACTIVE) $active[] = 'form';
    if (self::TEMPLATE_ACTIVE) $active[] = 'template';
    if (self::NAVIGATION_ACTIVE) $active[] = 'navigation';  // ADD THIS LINE
    if (self::TESTS_ACTIVE) $active[] = 'tests';
    return $active;
}
```

---

### Step 5: Update Configuration

**File**: `app/config/packages/genmax.yaml`

Add template and directory paths:

```yaml
parameters:
    genmax.paths:
        entity_dir: 'src/Entity'
        entity_generated_dir: 'src/Entity/Generated'
        dto_dir: 'src/Dto'
        dto_generated_dir: 'src/Dto/Generated'
        processor_dir: 'src/State'
        provider_dir: 'src/State'
        repository_dir: 'src/Repository'
        repository_generated_dir: 'src/Repository/Generated'
        controller_dir: 'src/Controller'
        controller_generated_dir: 'src/Controller/Generated'
        voter_dir: 'src/Security/Voter'
        voter_generated_dir: 'src/Security/Voter/Generated'
        form_dir: 'src/Form'
        form_generated_dir: 'src/Form/Generated'
        template_dir: 'templates'
        template_genmax_dir: 'templates/genmax/twig'
        api_platform_config_dir: 'config/api_platform'
        navigation_config_dir: 'src/Service'              # ADD THIS LINE
        navigation_config_generated_dir: 'src/Service/Generated'  # ADD THIS LINE

    genmax.templates:
        entity_generated: 'genmax/php/entity_generated.php.twig'
        entity_extension: 'genmax/php/entity_extension.php.twig'
        dto_input_generated: 'genmax/php/dto_input_generated.php.twig'
        dto_input_extension: 'genmax/php/dto_input_extension.php.twig'
        dto_output_generated: 'genmax/php/dto_output_generated.php.twig'
        dto_output_extension: 'genmax/php/dto_output_extension.php.twig'
        state_processor: 'genmax/php/state_processor.php.twig'
        state_provider: 'genmax/php/state_provider.php.twig'
        repository_generated: 'genmax/php/repository_generated.php.twig'
        repository_extension: 'genmax/php/repository_extension.php.twig'
        controller_generated: 'genmax/php/controller_generated.php.twig'
        controller_extension: 'genmax/php/controller_extension.php.twig'
        voter_generated: 'genmax/php/voter_generated.php.twig'
        voter_extension: 'genmax/php/voter_extension.php.twig'
        form_generated: 'genmax/php/form_generated.php.twig'
        form_extension: 'genmax/php/form_extension.php.twig'
        template_index_generated: 'genmax/twig/index_generated.html.twig'
        template_show_generated: 'genmax/twig/show_generated.html.twig'
        template_form_generated: 'genmax/twig/form_generated.html.twig'
        template_new_generated: 'genmax/twig/new_generated.html.twig'
        template_edit_generated: 'genmax/twig/edit_generated.html.twig'
        api_platform: 'genmax/yaml/api_platform.yaml.twig'
        navigation_config_generated: 'genmax/php/navigation_config_generated.php.twig'  # ADD THIS LINE
        navigation_config_extension: 'genmax/php/navigation_config_extension.php.twig'  # ADD THIS LINE
```

---

### Step 6: Migrate Existing NavigationConfig

**Important**: The current `NavigationConfig.php` will become the extension file.

#### 6.1: Backup Current File

```bash
cp app/src/Service/NavigationConfig.php app/src/Service/NavigationConfig.php.backup
```

#### 6.2: Create Generated Directory

```bash
mkdir -p app/src/Service/Generated
```

#### 6.3: Update Current NavigationConfig

The current `NavigationConfig.php` will be updated to extend `NavigationConfigGenerated` after first generation.

**Manual Step**: After running `genmax:generate` for the first time, update the existing `NavigationConfig.php`:

```php
// Change this line:
final class NavigationConfig

// To:
final class NavigationConfig extends NavigationConfigGenerated

// Add this import:
use App\Service\Generated\NavigationConfigGenerated;

// Merge generated items in getMainMenu():
public function getMainMenu(): array
{
    $manualItems = [
        // ... existing manual items ...
    ];

    // ADD THIS LINE:
    $generatedItems = $this->getGeneratedMenuItems();

    $adminItems = [
        // ... existing admin items ...
    ];

    // CHANGE RETURN:
    return array_merge($manualItems, $generatedItems, $adminItems);
}
```

---

## Database Schema

### GeneratorEntity Table Changes

```sql
-- Migration SQL (auto-generated)

-- Make menuOrder nullable (if not already)
ALTER TABLE generator_entity
ALTER COLUMN menu_order DROP DEFAULT,
ALTER COLUMN menu_order DROP NOT NULL;

-- Add navigationLabel field
ALTER TABLE generator_entity
ADD COLUMN navigation_label VARCHAR(100) DEFAULT NULL;
```

---

## File Structure

After implementation, the file structure will be:

```
app/
├── src/
│   ├── Entity/Generator/
│   │   └── GeneratorEntity.php          # Updated with navigation methods
│   │
│   ├── Service/
│   │   ├── NavigationConfig.php         # Extension (safe to edit)
│   │   ├── Generated/
│   │   │   └── NavigationConfigGenerated.php  # Generated (always overwritten)
│   │   └── Genmax/
│   │       └── NavigationGenerator.php  # New service
│   │
│   └── Twig/
│       └── MenuExtension.php            # Existing (no changes needed)
│
├── templates/genmax/php/
│   ├── navigation_config_generated.php.twig  # New template
│   └── navigation_config_extension.php.twig  # New template
│
└── config/packages/
    └── genmax.yaml                      # Updated with navigation paths
```

---

## Configuration

### Menu Group Examples

```sql
-- Example 1: CRM Group
UPDATE generator_entity SET menu_group = 'CRM', menu_order = 10 WHERE entity_name = 'Contact';
UPDATE generator_entity SET menu_group = 'CRM', menu_order = 20 WHERE entity_name = 'Company';
UPDATE generator_entity SET menu_group = 'CRM', menu_order = 30 WHERE entity_name = 'Lead';

-- Example 2: Sales Group
UPDATE generator_entity SET menu_group = 'Sales', menu_order = 10 WHERE entity_name = 'Deal';
UPDATE generator_entity SET menu_group = 'Sales', menu_order = 20 WHERE entity_name = 'Quote';
UPDATE generator_entity SET menu_group = 'Sales', menu_order = 30 WHERE entity_name = 'Invoice';

-- Example 3: Only menuOrder (defaults to "System" group)
UPDATE generator_entity SET menu_group = NULL, menu_order = 50 WHERE entity_name = 'Settings';

-- Example 4: Only menuGroup (defaults to order 9999, appears last)
UPDATE generator_entity SET menu_group = 'Reports', menu_order = NULL WHERE entity_name = 'Analytics';

-- Example 5: Hide from navigation
UPDATE generator_entity SET menu_group = NULL, menu_order = NULL WHERE entity_name = 'InternalEntity';
```

### Translation Keys

Navigation uses the following translation key patterns:

**Entity Labels**:
```yaml
# translations/contact.en.yaml
plural: "Contacts"
singular: "Contact"

# translations/company.en.yaml
plural: "Companies"
singular: "Company"
```

**Section Titles**:
```yaml
# translations/messages.en.yaml
nav:
  section:
    crm: "CRM"
    sales: "Sales"
    system: "System"
    reports: "Reports"
```

---

## Testing

### Testing Checklist

#### Phase 1: Entity Updates
- [ ] Migration created successfully
- [ ] Migration runs without errors
- [ ] `menuOrder` is nullable in database
- [ ] `navigationLabel` field exists
- [ ] `isShownInNavigation()` returns correct values
- [ ] `getEffectiveMenuGroup()` handles all cases (group only, order only, both, neither)
- [ ] `getEffectiveMenuOrder()` returns 9999 for null

#### Phase 2: Service Creation
- [ ] `NavigationGenerator` service is autowired correctly
- [ ] `generate()` method runs without errors
- [ ] `NavigationConfigGenerated.php` is created in `src/Service/Generated/`
- [ ] `NavigationConfig.php` extends `NavigationConfigGenerated`
- [ ] Generated file contains correct namespace

#### Phase 3: Template Generation
- [ ] Generated navigation file has correct PHP syntax
- [ ] All groups appear in alphabetical order
- [ ] Items within groups sorted by order then name
- [ ] Translation keys are correct (`entity.plural`)
- [ ] Voter constants are correctly referenced
- [ ] Icons are correctly included

#### Phase 4: Navigation Display
- [ ] Navigation menu appears in UI
- [ ] Manual items (home, student courses) still appear
- [ ] Generated items appear in correct groups
- [ ] Section dividers appear between groups
- [ ] Items are sorted correctly within groups
- [ ] Admin section still appears at bottom

#### Phase 5: Permission Checking
- [ ] Users without LIST permission don't see menu item
- [ ] Users with LIST permission do see menu item
- [ ] Section dividers only show if user has access to at least one item
- [ ] Role-based items (ROLE_STUDENT) work correctly

#### Phase 6: Translation
- [ ] Entity labels translate correctly
- [ ] Section titles translate correctly
- [ ] Translation domains work correctly
- [ ] Missing translations fall back gracefully

#### Phase 7: Customization
- [ ] Manual items can be added to extension file
- [ ] Manual items survive regeneration
- [ ] Group order can be customized
- [ ] Items can be added/removed manually
- [ ] Extension file is not overwritten on regeneration

### Test Cases

#### Test 1: Visibility Logic

```php
// Entity with menuGroup only → Shows in group with order 9999
$entity1 = new GeneratorEntity();
$entity1->setMenuGroup('CRM');
$entity1->setMenuOrder(null);
assertTrue($entity1->isShownInNavigation());
assertEquals('CRM', $entity1->getEffectiveMenuGroup());
assertEquals(9999, $entity1->getEffectiveMenuOrder());

// Entity with menuOrder only → Shows in "System" group
$entity2 = new GeneratorEntity();
$entity2->setMenuGroup(null);
$entity2->setMenuOrder(50);
assertTrue($entity2->isShownInNavigation());
assertEquals('System', $entity2->getEffectiveMenuGroup());
assertEquals(50, $entity2->getEffectiveMenuOrder());

// Entity with both → Shows in specified group with order
$entity3 = new GeneratorEntity();
$entity3->setMenuGroup('Sales');
$entity3->setMenuOrder(10);
assertTrue($entity3->isShownInNavigation());
assertEquals('Sales', $entity3->getEffectiveMenuGroup());
assertEquals(10, $entity3->getEffectiveMenuOrder());

// Entity with neither → Hidden
$entity4 = new GeneratorEntity();
$entity4->setMenuGroup(null);
$entity4->setMenuOrder(null);
assertFalse($entity4->isShownInNavigation());
assertNull($entity4->getEffectiveMenuGroup());
```

#### Test 2: Sorting

```sql
-- Setup test data
INSERT INTO generator_entity (entity_name, entity_label, menu_group, menu_order, icon) VALUES
('Contact', 'Contact', 'CRM', 10, 'bi-person'),
('Company', 'Company', 'CRM', 20, 'bi-building'),
('Lead', 'Lead', 'CRM', 20, 'bi-star'),  -- Same order as Company
('Deal', 'Deal', 'Sales', 10, 'bi-currency-dollar'),
('Quote', 'Quote', 'Sales', NULL, 'bi-file-text');  -- Should appear last

-- Expected order in CRM group:
-- 1. Contact (order 10)
-- 2. Company (order 20, alphabetically before Lead)
-- 3. Lead (order 20, alphabetically after Company)

-- Expected order in Sales group:
-- 1. Deal (order 10)
-- 2. Quote (order 9999)
```

#### Test 3: Permission Filtering

```php
// User without ContactVoter::LIST permission
$user = new User();
$user->setRoles(['ROLE_USER']);

// Navigation should NOT include Contact
$menu = $navigationConfig->getMainMenu();
$visibleMenu = array_filter($menu, fn($item) =>
    $navigationConfig->isMenuItemVisible($item, fn($perm) =>
        $authChecker->isGranted($perm)
    )
);

assertArrayNotHasKey('contact', $visibleMenu);
```

#### Test 4: Regeneration

```bash
# 1. Generate navigation
php bin/console genmax:generate

# 2. Manually edit NavigationConfig.php (add custom item)
# 3. Regenerate
php bin/console genmax:generate

# 4. Verify custom item still exists
# 5. Verify NavigationConfigGenerated.php was updated
```

---

## Usage Examples

### Example 1: Add Entity to Navigation

```sql
-- Simple: Add Contact to CRM group
UPDATE generator_entity
SET
    menu_group = 'CRM',
    menu_order = 10,
    icon = 'bi-person-circle'
WHERE entity_name = 'Contact';

-- Regenerate
php bin/console genmax:generate
```

**Result**: Contact appears in CRM section of navigation menu.

### Example 2: Reorder Items in Group

```sql
-- Reorder CRM items
UPDATE generator_entity SET menu_order = 5 WHERE entity_name = 'Lead';
UPDATE generator_entity SET menu_order = 10 WHERE entity_name = 'Contact';
UPDATE generator_entity SET menu_order = 15 WHERE entity_name = 'Company';

-- Regenerate
php bin/console genmax:generate
```

**Result**: CRM section shows Lead → Contact → Company.

### Example 3: Create New Group

```sql
-- Add entities to new "Marketing" group
UPDATE generator_entity SET menu_group = 'Marketing', menu_order = 10 WHERE entity_name = 'Campaign';
UPDATE generator_entity SET menu_group = 'Marketing', menu_order = 20 WHERE entity_name = 'EmailTemplate';

-- Regenerate
php bin/console genmax:generate
```

**Result**: New "Marketing" section appears with Campaign and EmailTemplate.

### Example 4: Hide Entity from Navigation

```sql
-- Remove from navigation
UPDATE generator_entity SET menu_group = NULL, menu_order = NULL WHERE entity_name = 'InternalLog';

-- Regenerate
php bin/console genmax:generate
```

**Result**: InternalLog no longer appears in navigation.

### Example 5: Add Custom Menu Item (Manual)

**Edit**: `app/src/Service/NavigationConfig.php`

```php
public function getMainMenu(): array
{
    $manualItems = [
        'home' => [...],
        'student_courses' => [...],

        // ADD CUSTOM ITEM:
        'custom_reports' => [
            'label' => 'nav.custom.reports',
            'route' => 'app_custom_reports',
            'icon' => 'bi-graph-up',
            'role' => 'ROLE_ADMIN',
        ],
    ];

    $generatedItems = $this->getGeneratedMenuItems();
    $adminItems = [...];

    return array_merge($manualItems, $generatedItems, $adminItems);
}
```

**Result**: Custom Reports appears in navigation, survives regeneration.

### Example 6: Customize Group Order

**Edit**: `app/src/Service/NavigationConfig.php`

```php
public function getMainMenu(): array
{
    $manualItems = [...];
    $generatedItems = $this->getGeneratedMenuItems();

    // REORDER GROUPS:
    $orderedGenerated = [];

    // 1. Sales first
    foreach ($generatedItems as $key => $item) {
        if (str_starts_with($key, 'sales_')) {
            $orderedGenerated[$key] = $item;
        }
    }

    // 2. CRM second
    foreach ($generatedItems as $key => $item) {
        if (str_starts_with($key, 'crm_')) {
            $orderedGenerated[$key] = $item;
        }
    }

    // 3. Everything else
    foreach ($generatedItems as $key => $item) {
        if (!isset($orderedGenerated[$key])) {
            $orderedGenerated[$key] = $item;
        }
    }

    $adminItems = [...];

    return array_merge($manualItems, $orderedGenerated, $adminItems);
}
```

**Result**: Sales group appears before CRM group.

---

## Troubleshooting

### Problem 1: Navigation Items Not Appearing

**Symptoms**: Entity has menuGroup or menuOrder but doesn't appear in navigation.

**Solutions**:

1. **Check entity generation**:
   ```bash
   php bin/console genmax:generate EntityName
   ```

2. **Verify voter exists**:
   ```bash
   ls -la app/src/Security/Voter/ | grep EntityNameVoter
   ```

3. **Check controller route exists**:
   ```bash
   php bin/console debug:router | grep entity_name_index
   ```

4. **Verify user has permission**:
   ```bash
   # Check in UI: User should have ROLE that allows EntityVoter::LIST
   ```

5. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

### Problem 2: NavigationConfigGenerated.php Not Created

**Symptoms**: Generated file doesn't exist after running genmax:generate.

**Solutions**:

1. **Check directory exists**:
   ```bash
   mkdir -p app/src/Service/Generated
   ```

2. **Check permissions**:
   ```bash
   chmod 755 app/src/Service/Generated
   ```

3. **Check logs**:
   ```bash
   tail -f app/var/log/dev.log | grep NavigationGenerator
   ```

4. **Verify NAVIGATION_ACTIVE flag**:
   ```php
   // In GenmaxOrchestrator.php
   private const NAVIGATION_ACTIVE = true;  // Should be true
   ```

### Problem 3: Translation Keys Not Resolving

**Symptoms**: Navigation shows "contact.plural" instead of "Contacts".

**Solutions**:

1. **Create translation file**:
   ```bash
   # app/translations/contact.en.yaml
   plural: "Contacts"
   singular: "Contact"
   ```

2. **Clear translation cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Check translation domain**:
   ```php
   // In NavigationConfigGenerated.php
   'translation_domain' => 'contact',  // Should match file name
   ```

### Problem 4: Custom Items Disappear After Regeneration

**Symptoms**: Manually added menu items are lost after running genmax:generate.

**Solution**: Custom items should be in `NavigationConfig.php`, NOT `NavigationConfigGenerated.php`.

**Correct location**:
```php
// app/src/Service/NavigationConfig.php (SAFE TO EDIT)
public function getMainMenu(): array
{
    $manualItems = [
        'home' => [...],
        'custom_item' => [...],  // ✅ Add here
    ];

    $generatedItems = $this->getGeneratedMenuItems();  // ✅ Not here

    return array_merge($manualItems, $generatedItems, $adminItems);
}
```

### Problem 5: Section Dividers Show Empty

**Symptoms**: Section divider appears but no items below it.

**Cause**: User doesn't have permission to any items in that section.

**Solution**: This is expected behavior. The MenuExtension should hide empty sections.

**Optional Fix**: Add logic to hide sections with no visible items:

```php
// In NavigationConfig.php
public function getMainMenu(): array
{
    $menu = array_merge($manualItems, $generatedItems, $adminItems);

    // Remove empty sections
    return $this->removeEmptySections($menu);
}

private function removeEmptySections(array $menu): array
{
    // Implementation: Check if section has any visible items
    // If not, remove section divider
}
```

### Problem 6: Items Not Sorted Correctly

**Symptoms**: Items appear in wrong order within group.

**Solutions**:

1. **Verify menuOrder values**:
   ```sql
   SELECT entity_name, menu_group, menu_order
   FROM generator_entity
   WHERE menu_group IS NOT NULL OR menu_order IS NOT NULL
   ORDER BY menu_group, menu_order, entity_label;
   ```

2. **Check entityLabel for alphabetical sorting**:
   ```sql
   -- Items with same menuOrder should be alphabetical by entityLabel
   SELECT entity_name, entity_label, menu_order
   FROM generator_entity
   WHERE menu_group = 'CRM'
   ORDER BY menu_order, entity_label;
   ```

3. **Regenerate navigation**:
   ```bash
   php bin/console genmax:generate
   ```

---

## Maintenance

### Updating Navigation After Changes

```bash
# After any change to menuGroup, menuOrder, or icon:
php bin/console genmax:generate

# This will regenerate NavigationConfigGenerated.php
# NavigationConfig.php (extension) is NOT overwritten
```

### Adding New Translation

```bash
# 1. Create translation file
# app/translations/new_entity.en.yaml
plural: "New Entities"
singular: "New Entity"

# 2. Add section translation if needed
# app/translations/messages.en.yaml
nav:
  section:
    new_group: "New Group"

# 3. Clear cache
php bin/console cache:clear
```

### Backup Before Major Changes

```bash
# Backup current NavigationConfig (extension)
cp app/src/Service/NavigationConfig.php app/src/Service/NavigationConfig.php.backup

# Backup database
docker-compose exec database pg_dump -U luminai luminai > backup.sql
```

---

## Best Practices

### DO:

✅ Use `menuGroup` for logical grouping
✅ Use `menuOrder` for explicit ordering
✅ Use translation keys for all labels
✅ Set icon on all entities
✅ Test permission checking after adding navigation items
✅ Customize in `NavigationConfig.php`, not `NavigationConfigGenerated.php`
✅ Run `genmax:generate` after database changes
✅ Create translation files for new entities

### DON'T:

❌ Edit `NavigationConfigGenerated.php` (always overwritten)
❌ Hardcode labels (use translation keys)
❌ Forget to set icons
❌ Skip permission checking
❌ Add items to wrong group
❌ Use reserved words for menuGroup names

---

## Future Enhancements

**Potential improvements for future versions**:

1. **Dynamic Section Titles**: Store section labels in database instead of translation files
2. **Icon Override**: Allow per-entity navigation icon override (different from entity icon)
3. **Badge Support**: Add badge/counter support for menu items (e.g., "5 new notifications")
4. **Conditional Visibility**: Add custom visibility rules beyond permission checking
5. **Nested Groups**: Support for sub-groups within main groups
6. **User Preferences**: Allow users to customize menu order/visibility
7. **Breadcrumb Integration**: Automatic breadcrumb generation from navigation structure
8. **Search**: Add search functionality to navigation menu

---

## Summary

This implementation provides:

- ✅ **Automatic navigation generation** from database
- ✅ **Zero new fields** (uses existing menuGroup/menuOrder)
- ✅ **Generated/Extended pattern** (Genmax architecture)
- ✅ **Translation support** (no hardcoded text)
- ✅ **Permission checking** (via Security Voters)
- ✅ **Smart defaults** (System group, order 9999)
- ✅ **Flexible customization** (safe extension file)
- ✅ **Automatic sorting** (order then alphabetical)

**Ready for implementation!** Follow the steps in order and test at each phase.

---

**Version:** 1.0
**Status:** 📋 READY FOR IMPLEMENTATION
**Last Updated:** October 2025
**Maintainer:** Luminai Development Team
