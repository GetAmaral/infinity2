<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

/**
 * Template Generator for Genmax
 *
 * Generates Twig templates using Base/Extension pattern.
 * All templates generated from GeneratorEntity database configuration.
 *
 * Generates 5 template types:
 * - index: List page with Grid/List/Table views (extends _base_entity_list.html.twig)
 * - show: Detail page with Bento Grid layout
 * - form: Shared form template (with form-navigation controller)
 * - new: Create page wrapper
 * - edit: Edit page wrapper
 *
 * @see /app/docs/Genmax/TEMPLATE_GENERATOR.md
 */
class TemplateGenerator
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

    /**
     * Generate template files for a GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string> Array of generated file paths
     */
    public function generate(GeneratorEntity $entity): array
    {
        $this->logger->info('[GENMAX] Generating templates', [
            'entity' => $entity->getEntityName()
        ]);

        $generatedFiles = [];

        // Build template context once for all templates
        $context = $this->buildTemplateContext($entity);

        // Generate base templates (always regenerated) + extension templates (once only)
        $generatedFiles[] = $this->generateBaseTemplate($entity, $context, 'index', $this->templates['template_index_generated']);
        $generatedFiles[] = $this->generateExtensionTemplate($entity, 'index');

        $generatedFiles[] = $this->generateBaseTemplate($entity, $context, 'show', $this->templates['template_show_generated']);
        $generatedFiles[] = $this->generateExtensionTemplate($entity, 'show');

        $generatedFiles[] = $this->generateBaseTemplate($entity, $context, 'form', $this->templates['template_form_generated']);
        $generatedFiles[] = $this->generateExtensionTemplate($entity, 'form');

        $generatedFiles[] = $this->generateBaseTemplate($entity, $context, 'new', $this->templates['template_new_generated']);
        $generatedFiles[] = $this->generateExtensionTemplate($entity, 'new');

        $generatedFiles[] = $this->generateBaseTemplate($entity, $context, 'edit', $this->templates['template_edit_generated']);
        $generatedFiles[] = $this->generateExtensionTemplate($entity, 'edit');

        return array_filter($generatedFiles);
    }

    /**
     * Generate base template file (Generated - always regenerated)
     */
    protected function generateBaseTemplate(GeneratorEntity $entity, array $context, string $type, string $templateName): string
    {
        $slug = $entity->getSlug();
        // Generated template goes to {entity}/generated/{type}_generated.html.twig
        $filePath = sprintf(
            '%s/%s/%s/generated/%s_generated.html.twig',
            $this->projectDir,
            $this->paths['template_dir'],
            $slug,
            $type
        );

        try {
            // Create directory if needed
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->logger->info('[GENMAX] Created template directory', ['dir' => $dir]);
            }

            // Read template file directly (don't render it - it contains runtime Twig code)
            $templatePath = $this->projectDir . '/' . $this->paths['template_dir'] . '/' . $templateName;
            if (!file_exists($templatePath)) {
                throw new \RuntimeException("Template not found: {$templatePath}");
            }

            $content = file_get_contents($templatePath);

            // Replace variable placeholders with actual values
            $content = str_replace([
                '{{ entityName }}',
                '{{ entityVariable }}',
                '{{ entitySlug }}',
                '{{ routePrefix }}',
                '{{ pageIcon }}',
                '{{ translationDomain }}',
                '{{ voterClass }}',
            ], [
                $context['entityName'],
                $context['entityVariable'],
                $context['entitySlug'],
                $context['routePrefix'],
                $context['pageIcon'],
                $context['translationDomain'],
                $context['voterClass'] ?? '',
            ], $content);

            // Replace conditional blocks
            if ($context['hasVoter']) {
                $content = str_replace('{% if hasVoter %}', '{% if true %}', $content);
            } else {
                $content = str_replace('{% if hasVoter %}', '{% if false %}', $content);
            }

            if ($context['hasOrganization']) {
                $content = str_replace('{% if hasOrganization %}', '{% if true %}', $content);
            } else {
                $content = str_replace('{% if hasOrganization %}', '{% if false %}', $content);
            }

            // Write file with smart comparison
            $status = $this->fileWriter->writeFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated base template', [
                'type' => $type,
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate base template', [
                'type' => $type,
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate {$type} base template for {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Generate extension template file (Extension - created once, safe to edit)
     */
    protected function generateExtensionTemplate(GeneratorEntity $entity, string $type): ?string
    {
        $slug = $entity->getSlug();
        $filePath = sprintf(
            '%s/%s/%s/%s.html.twig',
            $this->projectDir,
            $this->paths['template_dir'],
            $slug,
            $type
        );

        // Skip if exists (user may have customized)
        if (file_exists($filePath)) {
            $this->logger->info('[GENMAX] Skipping extension template (already exists)', [
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'type' => $type
            ]);
            return null;
        }

        try {
            // Create directory if needed
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Create simple include wrapper
            $entityName = $entity->getEntityName();
            $content = "{# Extension template for {$entityName} {$type} page #}\n";
            $content .= "{# You can customize this template or simply include the generated one #}\n";
            $content .= "{% include '{$slug}/generated/{$type}_generated.html.twig' %}\n";

            // Write file
            $status = $this->fileWriter->writeFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated extension template', [
                'type' => $type,
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate extension template', [
                'type' => $type,
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate {$type} extension template for {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Build template context with all variables needed for template generation
     */
    protected function buildTemplateContext(GeneratorEntity $entity): array
    {
        $entityName = $entity->getEntityName();
        $slug = $entity->getSlug();

        return [
            'entity' => $entity,
            'entity_name' => $slug,  // Required by _base_entity_list.html.twig
            'entityName' => $entityName,
            'entitySlug' => $slug,
            'entityVariable' => lcfirst($entityName),
            'entityPluralName' => $entity->getPluralLabel() ?: $entityName . 's',
            'entityPluralVariable' => lcfirst($entity->getPluralLabel() ?: $entityName . 's'),
            'routePrefix' => $slug,
            'page_icon' => 'bi-' . $this->getEntityIcon($entity),  // Required by _base_entity_list.html.twig
            'pageIcon' => $this->getEntityIcon($entity),
            'entityLabel' => $entity->getEntityLabel() ?: $entityName,
            'translationDomain' => $slug,
            'listProperties' => $this->getListProperties($entity),
            'showProperties' => $this->getShowProperties($entity),
            'hasVoter' => $entity->isVoterEnabled(),
            'voterClass' => $entityName . 'Voter',
            'hasOrganization' => $this->hasOrganizationProperty($entity),
        ];
    }

    /**
     * Get properties to display in list views (Grid/List/Table)
     */
    protected function getListProperties(GeneratorEntity $entity): array
    {
        $properties = [];

        foreach ($entity->getProperties() as $property) {
            // Skip if not shown in list
            if (!$property->isShowInList()) {
                continue;
            }

            // Skip auto-generated fields that are shown elsewhere
            if (in_array($property->getPropertyName(), ['id', 'createdAt', 'updatedAt', 'organization'], true)) {
                continue;
            }

            $properties[] = $this->formatProperty($property);
        }

        return $properties;
    }

    /**
     * Get properties to display in show/detail view
     */
    protected function getShowProperties(GeneratorEntity $entity): array
    {
        $properties = [];

        foreach ($entity->getProperties() as $property) {
            // Skip if not shown in detail
            if (!$property->isShowInDetail()) {
                continue;
            }

            // Skip meta fields (shown separately)
            if (in_array($property->getPropertyName(), ['id', 'createdAt', 'updatedAt', 'organization'], true)) {
                continue;
            }

            $properties[] = $this->formatProperty($property);
        }

        return $properties;
    }

    /**
     * Format a property for template use
     */
    protected function formatProperty(GeneratorProperty $property): array
    {
        $name = $property->getPropertyName();
        $type = $property->getPropertyType();
        $relationshipType = $property->getRelationshipType();
        $isRelationship = $relationshipType !== null;

        $formatted = [
            'name' => $name,
            'label' => $property->getPropertyLabel() ?: ucfirst($name),
            'type' => $type,
            'getter' => 'get' . ucfirst($name),
            'isRelationship' => $isRelationship,
            'sortable' => $property->isSortable(),
            'searchable' => $property->isSearchable(),
        ];

        // Add formatting info
        $formatting = $this->getPropertyFormatting($property);
        $formatted = array_merge($formatted, $formatting);

        // Add relationship-specific data
        if ($isRelationship) {
            $formatted['relationshipType'] = $relationshipType;
            $formatted['relationshipRoute'] = $this->getRelationshipRoute($property);
            $formatted['relationshipProperty'] = 'name'; // Default, can be customized
        }

        return $formatted;
    }

    /**
     * Get property formatting configuration
     */
    protected function getPropertyFormatting(GeneratorProperty $property): array
    {
        $type = $property->getPropertyType();
        $isRelationship = $property->getRelationshipType() !== null;

        if ($isRelationship) {
            return [
                'format' => 'relationship',
                'icon' => 'arrow-right-circle',
            ];
        }

        return match($type) {
            'boolean' => [
                'format' => 'boolean',
                'icon' => 'toggle-on',
            ],
            'datetime', 'datetime_immutable' => [
                'format' => 'datetime',
                'icon' => 'calendar-event',
                'dateFormat' => 'F j, Y, g:i A',
            ],
            'date' => [
                'format' => 'date',
                'icon' => 'calendar',
                'dateFormat' => 'M d, Y',
            ],
            'time' => [
                'format' => 'time',
                'icon' => 'clock',
                'dateFormat' => 'H:i',
            ],
            'uuid' => [
                'format' => 'uuid',
                'icon' => 'key',
            ],
            'text' => [
                'format' => 'text',
                'icon' => 'align-left',
            ],
            'integer', 'smallint', 'bigint' => [
                'format' => 'integer',
                'icon' => 'hash',
            ],
            'float', 'decimal' => [
                'format' => 'decimal',
                'icon' => 'currency-dollar',
            ],
            'json' => [
                'format' => 'json',
                'icon' => 'code-square',
            ],
            default => [
                'format' => 'string',
                'icon' => 'circle',
            ],
        };
    }

    /**
     * Get route name for relationship target
     */
    protected function getRelationshipRoute(GeneratorProperty $property): string
    {
        $targetEntity = $property->getTargetEntity();
        if (!$targetEntity) {
            return '#';
        }

        // Convert entity name to route prefix (e.g., Company -> company)
        $routePrefix = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $targetEntity));

        return $routePrefix . '_show';
    }

    /**
     * Get Bootstrap icon for entity
     */
    protected function getEntityIcon(GeneratorEntity $entity): string
    {
        // Check if entity has custom icon
        if ($icon = $entity->getIcon()) {
            // Remove 'bi-' prefix if present
            return str_replace('bi-', '', $icon);
        }

        // Default icon based on entity name
        $name = strtolower($entity->getEntityName());

        return match(true) {
            str_contains($name, 'user') => 'people',
            str_contains($name, 'organization') => 'building',
            str_contains($name, 'company') => 'building',
            str_contains($name, 'contact') => 'person-circle',
            str_contains($name, 'course') => 'book',
            str_contains($name, 'module') => 'collection',
            str_contains($name, 'step') => 'diagram-3',
            str_contains($name, 'task') => 'check2-square',
            str_contains($name, 'project') => 'folder',
            str_contains($name, 'document') => 'file-text',
            str_contains($name, 'message') => 'chat-dots',
            str_contains($name, 'notification') => 'bell',
            str_contains($name, 'setting') => 'gear',
            str_contains($name, 'tag') => 'tag',
            str_contains($name, 'category') => 'grid',
            str_contains($name, 'product') => 'box',
            str_contains($name, 'order') => 'cart',
            str_contains($name, 'payment') => 'credit-card',
            str_contains($name, 'invoice') => 'receipt',
            str_contains($name, 'report') => 'graph-up',
            str_contains($name, 'calendar') => 'calendar',
            str_contains($name, 'event') => 'calendar-event',
            str_contains($name, 'meeting') => 'people-fill',
            str_contains($name, 'pipeline') => 'diagram-2',
            str_contains($name, 'deal') => 'currency-dollar',
            default => 'circle',
        };
    }

    /**
     * Check if entity has organization property
     */
    protected function hasOrganizationProperty(GeneratorEntity $entity): bool
    {
        foreach ($entity->getProperties() as $property) {
            if ($property->getPropertyName() === 'organization') {
                return true;
            }
        }
        return false;
    }
}
