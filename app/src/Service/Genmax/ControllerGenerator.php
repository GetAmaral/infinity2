<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;
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

    /**
     * Generate controller files for a GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string> Array of generated file paths
     */
    public function generate(GeneratorEntity $entity): array
    {
        if (!$entity->isGenerateController()) {
            $this->logger->info('[GENMAX] Controller generation disabled', [
                'entity' => $entity->getEntityName()
            ]);
            return [];
        }

        $this->validateConfiguration($entity);

        $generatedFiles = [];

        $this->logger->info('[GENMAX] Generating controller', [
            'entity' => $entity->getEntityName(),
            'operations' => [
                'index' => $entity->isControllerOperationIndex(),
                'new' => $entity->isControllerOperationNew(),
                'edit' => $entity->isControllerOperationEdit(),
                'delete' => $entity->isControllerOperationDelete(),
                'show' => $entity->isControllerOperationShow(),
            ]
        ]);

        // Always generate base class (can be regenerated safely)
        $generatedFiles[] = $this->generateBaseController($entity);

        // Generate extension class ONCE only (user can customize)
        $extensionFile = $this->generateExtensionController($entity);
        if ($extensionFile) {
            $generatedFiles[] = $extensionFile;
        }

        return array_filter($generatedFiles);
    }

    /**
     * Generate base controller class: src/Controller/Generated/{Entity}ControllerGenerated.php
     */
    protected function generateBaseController(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sControllerGenerated.php',
            $this->projectDir,
            $this->paths['controller_generated_dir'],
            $entity->getEntityName()
        );

        try {
            $context = $this->buildTemplateContext($entity);

            // Render from template
            $content = $this->twig->render($this->templates['controller_generated'], $context);

            // Write file with smart comparison
            $status = $this->fileWriter->writeFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated controller base class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate controller base class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate controller base class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Generate extension controller class: src/Controller/{Entity}Controller.php
     */
    protected function generateExtensionController(GeneratorEntity $entity): ?string
    {
        $filePath = sprintf(
            '%s/%s/%sController.php',
            $this->projectDir,
            $this->paths['controller_dir'],
            $entity->getEntityName()
        );

        // Skip if exists (user may have customized)
        if (file_exists($filePath)) {
            $this->logger->info('[GENMAX] Skipping extension controller (already exists)', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);
            return null;
        }

        try {
            $context = $this->buildTemplateContext($entity);

            // Render from template
            $content = $this->twig->render($this->templates['controller_extension'], $context);

            // Write file with smart comparison
            $status = $this->fileWriter->writeFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated controller extension class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate controller extension class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate controller extension class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Build template context with all variables needed for controller generation
     */
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
            'namespace' => $this->paths['controller_namespace'],
            'generatedNamespace' => $this->paths['controller_generated_namespace'],
            'baseControllerClass' => 'BaseApiController',
        ];
    }

    /**
     * Get properties that should be serialized for API responses
     */
    protected function getSerializableProperties(GeneratorEntity $entity): array
    {
        $properties = [];
        $excludedProperties = $entity->getDtoExcludedProperties() ?? [];

        foreach ($entity->getProperties() as $property) {
            $propertyName = $property->getPropertyName();

            // Skip DTO-excluded properties
            if (in_array($propertyName, $excludedProperties, true)) {
                continue;
            }

            $propertyType = $property->getPropertyType();

            $properties[] = [
                'name' => $propertyName,
                'type' => $propertyType,
                'getter' => $this->genmaxExtension->getGetterName($propertyName),
                'serialization' => $this->getSerializationLogic($property),
                'isRelationship' => $property->getRelationshipType() !== null,
                'nullable' => $property->isNullable(),
            ];
        }

        return $properties;
    }

    /**
     * Get serialization logic for a property
     *
     * IMPORTANT: Enum-Backed Properties
     * ----------------------------------
     * Properties with PHP enum backing types (like InputType) are stored as STRINGS
     * in the database via Doctrine. Generated getters return strings directly, not enum objects.
     *
     * Example:
     *   - Database column: type_prop VARCHAR - stores "fully_completed"
     *   - Entity property: protected string $type = 'ANY'
     *   - Getter: getType(): string - returns "fully_completed" (NOT InputType enum object)
     *
     * This means:
     *   ✅ CORRECT: $entity->getType()          // Returns string: "fully_completed"
     *   ❌ WRONG:   $entity->getType()->value   // Error: "Attempt to read property 'value' on string"
     *
     * If business logic needs the enum object:
     *   $enumObj = InputType::from($entity->getType())
     *
     * Note: Relationships are handled directly in the template using entity __toString()
     */
    protected function getSerializationLogic(GeneratorProperty $property): string
    {
        $type = $property->getPropertyType();

        // Relationships handled in template using __toString()
        if ($property->getRelationshipType() !== null) {
            return ""; // Template uses isRelationship check
        }

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

        // String types (including enum-backed strings) - direct access
        // Note: Even if backed by an enum, getters return string values directly
        if ($type === 'string') {
            return "";
        }

        // Default - direct access
        return "";
    }

    /**
     * Get properties to show in list views
     */
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

    /**
     * Get fields that are searchable
     */
    protected function getSearchableFields(GeneratorEntity $entity): array
    {
        $fields = [];

        foreach ($entity->getProperties() as $property) {
            if ($property->isSearchable()) {
                $fields[] = [
                    'name' => $property->getPropertyName(),
                    'label' => $property->getPropertyLabel(),
                    'type' => $property->getPropertyType(),
                ];
            }
        }

        return $fields;
    }

    /**
     * Get fields that are filterable
     */
    protected function getFilterableFields(GeneratorEntity $entity): array
    {
        $fields = [];

        foreach ($entity->getProperties() as $property) {
            if ($property->isFilterable()) {
                $fields[] = [
                    'name' => $property->getPropertyName(),
                    'label' => $property->getPropertyLabel(),
                    'type' => $property->getPropertyType(),
                    'strategy' => $property->getFilterStrategy(),
                    'boolean' => $property->isFilterBoolean(),
                    'date' => $property->isFilterDate(),
                    'numericRange' => $property->isFilterNumericRange(),
                    'exists' => $property->isFilterExists(),
                ];
            }
        }

        return $fields;
    }

    /**
     * Get fields that are sortable
     */
    protected function getSortableFields(GeneratorEntity $entity): array
    {
        $fields = [];

        foreach ($entity->getProperties() as $property) {
            if ($property->isSortable()) {
                $fields[] = [
                    'name' => $property->getPropertyName(),
                    'label' => $property->getPropertyLabel(),
                ];
            }
        }

        return $fields;
    }

    /**
     * Check if entity has GetCollection API operation
     */
    protected function hasApiGetCollection(GeneratorEntity $entity): bool
    {
        if (!$entity->isApiEnabled()) {
            return false;
        }

        $operations = $entity->getApiOperations();
        return $operations && in_array('GetCollection', $operations, true);
    }

    /**
     * Check if entity has owner property
     */
    protected function hasOwnerProperty(GeneratorEntity $entity): bool
    {
        foreach ($entity->getProperties() as $property) {
            if ($property->getPropertyName() === 'owner') {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate configuration and log warnings
     */
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

        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                $this->logger->warning('[GENMAX] Configuration warning', [
                    'entity' => $entity->getEntityName(),
                    'warning' => $warning
                ]);
            }
        }
    }
}
