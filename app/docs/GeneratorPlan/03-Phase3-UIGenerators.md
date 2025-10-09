# Phase 3: UI Generators (Week 4)

## Overview

Phase 3 generates all user-facing components: Twig templates, navigation menus, and translations.

**Duration:** Week 4 (5 working days)

**Deliverables:**
- ✅ Template Generator (index, form, show views)
- ✅ Navigation Generator (marker-based injection)
- ✅ Translation Generator (entity labels and field translations)
- ✅ Unit tests for all generators (80%+ coverage)

---

## Day 1-2: Template Generator

### File: `src/Service/Generator/Template/TemplateGenerator.php`

**Purpose:** Generate Twig templates for entity CRUD views.

**Key Features:**
- Turbo Drive compatible
- Bootstrap 5 styling
- Responsive design
- Modal forms
- Turbo Stream responses

**Templates Generated:**
1. **index.html.twig** - List view with search, sort, filter
2. **form.html.twig** - Create/Edit form
3. **show.html.twig** - Detail view
4. **_turbo_stream_create.html.twig** - Turbo Stream for create action
5. **_turbo_stream_update.html.twig** - Turbo Stream for update action
6. **_turbo_stream_delete.html.twig** - Turbo Stream for delete action

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Service\Generator\Template;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class TemplateGenerator
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem
    ) {}

    /**
     * Generate all templates for an entity
     */
    public function generate(EntityDefinitionDto $entity): array
    {
        $templateDir = sprintf(
            '%s/templates/%s',
            $this->projectDir,
            $entity->getLowercaseName()
        );

        // Create directory
        if (!is_dir($templateDir)) {
            $this->filesystem->mkdir($templateDir, 0755);
        }

        $generatedFiles = [];

        // Generate index.html.twig
        $generatedFiles[] = $this->generateIndexTemplate($entity, $templateDir);

        // Generate form.html.twig
        $generatedFiles[] = $this->generateFormTemplate($entity, $templateDir);

        // Generate show.html.twig
        $generatedFiles[] = $this->generateShowTemplate($entity, $templateDir);

        // Generate Turbo Stream templates
        $generatedFiles[] = $this->generateTurboStreamCreate($entity, $templateDir);
        $generatedFiles[] = $this->generateTurboStreamUpdate($entity, $templateDir);
        $generatedFiles[] = $this->generateTurboStreamDelete($entity, $templateDir);

        return $generatedFiles;
    }

    private function generateIndexTemplate(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/index.html.twig';

        $content = $this->twig->render('Generator/twig/index.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateFormTemplate(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/form.html.twig';

        $content = $this->twig->render('Generator/twig/form.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateShowTemplate(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/show.html.twig';

        $content = $this->twig->render('Generator/twig/show.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateTurboStreamCreate(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/_turbo_stream_create.html.twig';

        $content = $this->twig->render('Generator/twig/turbo_stream_create.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateTurboStreamUpdate(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/_turbo_stream_update.html.twig';

        $content = $this->twig->render('Generator/twig/turbo_stream_update.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateTurboStreamDelete(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/_turbo_stream_delete.html.twig';

        $content = $this->twig->render('Generator/twig/turbo_stream_delete.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }
}
```

**Example Template:** `templates/generator/twig/index.html.twig.twig`

```twig
{% raw %}{% extends 'base.html.twig' %}

{% block title %}{{ '{{ "' ~ entity.pluralLabel ~ '"|trans }}' }}{% endblock %}

{% block body %}
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="{{ entity.icon }} me-2"></i>
            {{ '{{ "' ~ entity.pluralLabel ~ '"|trans }}' }}
        </h1>
        <a href="{{ '{{ path("' ~ entity.getSnakeCaseName() ~ '_new") }}' }}"
           class="btn btn-primary"
           data-turbo-frame="modal">
            <i class="bi bi-plus-circle me-1"></i>
            {{ '{{ "action.create"|trans }}' }}
        </a>
    </div>

    {# Search and Filter #}
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" data-turbo="true">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="search"
                               name="q"
                               value="{{ '{{ app.request.query.get("q") }}' }}"
                               class="form-control"
                               placeholder="{{ '{{ "action.search"|trans }}' }}...">
                    </div>
{% for property in entity.properties if property.filterable %}
                    <div class="col-md-3">
                        <select name="filter_{{ property.propertyName }}" class="form-select">
                            <option value="">{{ '{{ "' ~ property.formLabel ~ '"|trans }}' }}</option>
                            {# Filter options based on property type #}
                        </select>
                    </div>
{% endfor %}
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-secondary w-100">
                            <i class="bi bi-search me-1"></i>
                            {{ '{{ "action.search"|trans }}' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {# Data Table #}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
{% for property in entity.properties if property.showInList %}
                            <th>
{% if property.sortable %}
                                <a href="{{ '{{ path("' ~ entity.getSnakeCaseName() ~ '_index", {sort: "' ~ property.propertyName ~ '", direction: app.request.query.get("direction") == "asc" ? "desc" : "asc"}) }}' }}"
                                   data-turbo="true">
                                    {{ '{{ "' ~ property.formLabel ~ '"|trans }}' }}
                                    <i class="bi bi-arrow-down-up"></i>
                                </a>
{% else %}
                                {{ '{{ "' ~ property.formLabel ~ '"|trans }}' }}
{% endif %}
                            </th>
{% endfor %}
                            <th>{{ '{{ "action.label"|trans }}' }}</th>
                        </tr>
                    </thead>
                    <tbody id="{{ entity.getLowercaseName() }}-list">
                        {{ '{% for item in items %}' }}
                        <tr id="{{ entity.getLowercaseName() }}-{{ '{{ item.id }}' }}">
{% for property in entity.properties if property.showInList %}
{% if property.isRelationship and not property.isCollection %}
                            <td>{{ '{{ item.' ~ property.propertyName ~ ' }}' }}</td>
{% elseif not property.isRelationship %}
                            <td>{{ '{{ item.' ~ property.propertyName ~ ' }}' }}</td>
{% endif %}
{% endfor %}
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ '{{ path("' ~ entity.getSnakeCaseName() ~ '_show", {id: item.id}) }}' }}"
                                       class="btn btn-sm btn-info"
                                       data-turbo-frame="modal">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ '{{ path("' ~ entity.getSnakeCaseName() ~ '_edit", {id: item.id}) }}' }}"
                                       class="btn btn-sm btn-warning"
                                       data-turbo-frame="modal">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal{{ '{{ item.id }}' }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        {{ '{% endfor %}' }}
                    </tbody>
                </table>
            </div>

            {# Pagination #}
            {{ '{{ knp_pagination_render(items) }}' }}
        </div>
    </div>
</div>

{# Modal Frame for Turbo #}
<turbo-frame id="modal" class="modal fade" tabindex="-1">
</turbo-frame>
{% endblock %}{% endraw %}
```

---

## Day 3: Navigation Generator

### File: `src/Service/Generator/Navigation/NavigationGenerator.php`

**Purpose:** Inject entity menu items into base template navigation.

**Key Features:**
- Marker-based injection (preserves custom menu items)
- Grouped navigation (CRM, Admin, etc.)
- Icon support
- Order control

**Markers in base.html.twig:**
```twig
{# GENERATOR_NAV_START:crm #}
{# Auto-generated CRM menu items #}
{# GENERATOR_NAV_END:crm #}
```

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Service\Generator\Navigation;

use App\Service\Generator\Csv\EntityDefinitionDto;

class NavigationGenerator
{
    private const BASE_TEMPLATE_PATH = '/templates/base.html.twig';

    public function __construct(
        private readonly string $projectDir
    ) {}

    /**
     * Update navigation menu in base template
     *
     * @param EntityDefinitionDto[] $entities
     */
    public function generate(array $entities): void
    {
        $templatePath = $this->projectDir . self::BASE_TEMPLATE_PATH;

        if (!file_exists($templatePath)) {
            throw new \RuntimeException('Base template not found');
        }

        $template = file_get_contents($templatePath);

        // Group entities by navGroup
        $groups = [];
        foreach ($entities as $entity) {
            if (!$entity->showInNavigation) {
                continue;
            }

            [$groupName, $groupOrder] = $this->parseNavGroup($entity->navGroup);
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'order' => (int)$groupOrder,
                    'entities' => []
                ];
            }
            $groups[$groupName]['entities'][] = $entity;
        }

        // Sort groups by order
        uasort($groups, fn($a, $b) => $a['order'] <=> $b['order']);

        // Generate menu HTML for each group
        foreach ($groups as $groupName => $group) {
            // Sort entities within group by navOrder
            usort($group['entities'], fn($a, $b) => $a->navOrder <=> $b->navOrder);

            $menuHtml = $this->generateMenuItems($group['entities']);

            // Replace between markers
            $startMarker = "<!-- GENERATOR_NAV_START:{$groupName} -->";
            $endMarker = "<!-- GENERATOR_NAV_END:{$groupName} -->";

            if (str_contains($template, $startMarker) && str_contains($template, $endMarker)) {
                // Markers exist, replace content
                $pattern = '/(' . preg_quote($startMarker, '/') . ').*?(' . preg_quote($endMarker, '/') . ')/s';
                $replacement = "$1\n{$menuHtml}\n$2";
                $template = preg_replace($pattern, $replacement, $template);
            } else {
                // Markers don't exist, append to nav
                // Find </ul> before closing nav
                $navEndPos = strrpos($template, '</ul>', strpos($template, '</nav>'));
                if ($navEndPos !== false) {
                    $insertion = "\n{$startMarker}\n{$menuHtml}\n{$endMarker}\n";
                    $template = substr_replace($template, $insertion, $navEndPos, 0);
                }
            }
        }

        file_put_contents($templatePath, $template);
    }

    /**
     * Parse navGroup into name and order (e.g., "crm.01" => ["crm", "01"])
     */
    private function parseNavGroup(string $navGroup): array
    {
        if (str_contains($navGroup, '.')) {
            return explode('.', $navGroup, 2);
        }
        return [$navGroup, '0'];
    }

    /**
     * Generate menu HTML for entities
     */
    private function generateMenuItems(array $entities): string
    {
        $html = '';
        foreach ($entities as $entity) {
            $html .= sprintf(
                '                <li class="nav-item">' . "\n" .
                '                    <a class="nav-link" href="{{ path(\'%s_index\') }}" data-turbo="true">' . "\n" .
                '                        <i class="%s me-2"></i>' . "\n" .
                '                        {{ \'%s\'|trans }}' . "\n" .
                '                    </a>' . "\n" .
                '                </li>' . "\n",
                $entity->getSnakeCaseName(),
                $entity->icon,
                $entity->pluralLabel
            );
        }
        return rtrim($html);
    }
}
```

---

## Day 4: Translation Generator

### File: `src/Service/Generator/Translation/TranslationGenerator.php`

**Purpose:** Generate translation entries for entity labels and fields.

**Key Features:**
- 4-level fallback system (specific → field → entity → generated)
- Singular and plural forms
- Field labels and help text
- Merge with existing translations (preserves custom translations)

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Service\Generator/Translation;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Yaml\Yaml;

class TranslationGenerator
{
    private const TRANSLATIONS_PATH = '/translations/messages.en.yaml';

    public function __construct(
        private readonly string $projectDir
    ) {}

    /**
     * Generate translations for entities
     *
     * @param EntityDefinitionDto[] $entities
     */
    public function generate(array $entities): void
    {
        $translationsPath = $this->projectDir . self::TRANSLATIONS_PATH;

        // Load existing translations
        $existingTranslations = [];
        if (file_exists($translationsPath)) {
            $existingTranslations = Yaml::parseFile($translationsPath) ?? [];
        }

        // Generate new translations
        $newTranslations = $this->generateTranslations($entities);

        // Merge (existing takes precedence)
        $merged = array_replace_recursive($newTranslations, $existingTranslations);

        // Write back
        $yaml = Yaml::dump($merged, 4, 2);
        file_put_contents($translationsPath, $yaml);
    }

    /**
     * Generate translation array for entities
     */
    private function generateTranslations(array $entities): array
    {
        $translations = [];

        foreach ($entities as $entity) {
            // Entity labels
            $translations[$entity->entityLabel] = $entity->entityLabel;
            $translations[$entity->pluralLabel] = $entity->pluralLabel;

            // Field labels
            foreach ($entity->properties as $property) {
                $translations[$property->formLabel] = $this->humanize($property->propertyName);

                // Help text
                if ($property->formHelp) {
                    $translations[$property->formHelp] = '';
                }
            }
        }

        return $translations;
    }

    /**
     * Convert camelCase to "Human Readable"
     */
    private function humanize(string $text): string
    {
        // Insert space before uppercase letters
        $humanized = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);

        // Capitalize first letter
        return ucfirst($humanized);
    }
}
```

---

## Day 5: Integration and Testing

### Integration Test: Complete UI Generation Flow

```php
<?php

namespace App\Tests\Service\Generator\Integration;

use App\Service\Generator\Template\TemplateGenerator;
use App\Service\Generator\Navigation\NavigationGenerator;
use App\Service\Generator\Translation\TranslationGenerator;
use App\Service\Generator\Csv\CsvParserService;
use PHPUnit\Framework\TestCase;

class UIGenerationIntegrationTest extends TestCase
{
    public function testCompleteUIGeneration(): void
    {
        // 1. Parse CSV
        $parser = new CsvParserService(/* ... */);
        $result = $parser->parseAll();
        $entities = $result['entities'];

        // 2. Generate templates
        $templateGenerator = new TemplateGenerator(/* ... */);
        foreach ($entities as $entity) {
            $files = $templateGenerator->generate($entity);
            $this->assertCount(6, $files); // 6 template files
        }

        // 3. Generate navigation
        $navGenerator = new NavigationGenerator(/* ... */);
        $navGenerator->generate($entities);

        // Verify markers exist
        $baseTemplate = file_get_contents($this->testDir . '/templates/base.html.twig');
        $this->assertStringContainsString('GENERATOR_NAV_START', $baseTemplate);
        $this->assertStringContainsString('GENERATOR_NAV_END', $baseTemplate);

        // 4. Generate translations
        $translationGenerator = new TranslationGenerator(/* ... */);
        $translationGenerator->generate($entities);

        // Verify translations file created
        $this->assertFileExists($this->testDir . '/translations/messages.en.yaml');
    }
}
```

---

## Phase 3 Deliverables Checklist

- [ ] Template Generator implemented and tested
- [ ] Index template generated with search/filter/sort
- [ ] Form template generated with proper field types
- [ ] Show template generated with all fields
- [ ] Turbo Stream templates for CRUD actions
- [ ] Navigation Generator implemented and tested
- [ ] Marker-based injection working correctly
- [ ] Translation Generator implemented and tested
- [ ] Translation fallback system working
- [ ] All unit tests pass (80%+ coverage)
- [ ] Integration test passes
- [ ] Code passes PHPStan level 8
- [ ] Templates render correctly

---

## Next Phase

**Phase 4: Test Generators** (Week 5)
- Entity Test Generator
- Repository Test Generator
- Controller Test Generator
- Voter Test Generator
