<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * Repository Generator for Genmax
 *
 * Generates Doctrine Repository files using the Generated/Extended pattern:
 * - {Entity}RepositoryGenerated (always regenerated, extends BaseRepository)
 * - {Entity}Repository (generated once, safe to edit)
 *
 * Repositories extend BaseRepository to inherit:
 * - apiSearch() with full-text search, filtering, sorting, pagination
 * - UNACCENT support for accent-insensitive search
 * - Date range and boolean filters
 * - Relationship field filtering
 */
class RepositoryGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        #[Autowire(param: 'genmax.paths')]
        private readonly array $paths,
        #[Autowire(param: 'genmax.templates')]
        private readonly array $templates,
        private readonly Environment $twig,
        private readonly SmartFileWriter $fileWriter,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate Repository files for a GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string> Array of generated file paths
     */
    public function generate(GeneratorEntity $entity): array
    {
        $generatedFiles = [];

        // Generate RepositoryGenerated (always regenerated)
        $generatedPath = sprintf(
            '%s/%s/%sRepositoryGenerated.php',
            $this->projectDir,
            $this->paths['repository_generated_dir'],
            $entity->getEntityName()
        );

        // Generate Repository Extension (generated once)
        $extensionPath = sprintf(
            '%s/%s/%sRepository.php',
            $this->projectDir,
            $this->paths['repository_dir'],
            $entity->getEntityName()
        );

        try {
            // Prepare template data
            $templateData = $this->prepareTemplateData($entity);

            // 1. Generate RepositoryGenerated (ALWAYS)
            $generatedContent = $this->twig->render(
                $this->templates['repository_generated'],
                $templateData
            );

            $status = $this->fileWriter->writeFile($generatedPath, $generatedContent);

            $this->logger->info('[GENMAX] Generated Repository (Generated)', [
                'file' => $generatedPath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            $generatedFiles[] = $generatedPath;

            // 2. Generate Repository Extension (ONLY IF NOT EXISTS)
            if (!file_exists($extensionPath)) {
                $extensionContent = $this->twig->render(
                    $this->templates['repository_extension'],
                    $templateData
                );

                file_put_contents($extensionPath, $extensionContent);

                $this->logger->info('[GENMAX] Generated Repository (Extension)', [
                    'file' => $extensionPath,
                    'entity' => $entity->getEntityName(),
                    'status' => 'created'
                ]);

                $generatedFiles[] = $extensionPath;
            } else {
                $this->logger->info('[GENMAX] Skipped Repository (Extension) - already exists', [
                    'file' => $extensionPath,
                    'entity' => $entity->getEntityName()
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate Repository', [
                'entity' => $entity->getEntityName(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate Repository for {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }

        return $generatedFiles;
    }

    /**
     * Prepare template data from GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string, mixed>
     */
    private function prepareTemplateData(GeneratorEntity $entity): array
    {
        $properties = $entity->getProperties();

        return [
            'entity' => $entity,
            'entity_name' => $entity->getEntityName(),
            'entity_description' => $entity->getDescription() ?? '',
            'entity_namespace' => $this->paths['entity_namespace'],
            'repository_namespace' => $this->paths['repository_namespace'],
            'repository_generated_namespace' => $this->paths['repository_generated_namespace'],
            'searchable_properties' => $this->getSearchableProperties($properties),
            'sortable_properties' => $this->getSortableProperties($properties),
            'filterable_properties' => $this->getFilterableProperties($properties),
            'relationship_filter_properties' => $this->getRelationshipFilterProperties($properties),
            'boolean_properties' => $this->getBooleanProperties($properties),
            'date_properties' => $this->getDateProperties($properties),
            'output_properties' => $this->getOutputProperties($properties),
        ];
    }

    /**
     * Get searchable properties (text fields)
     *
     * @param iterable<GeneratorProperty> $properties
     * @return array<array{name: string}>
     */
    private function getSearchableProperties(iterable $properties): array
    {
        $searchable = [];
        foreach ($properties as $prop) {
            if ($prop->isSearchable() && in_array($prop->getPropertyType(), ['string', 'text'])) {
                $searchable[] = ['name' => $prop->getPropertyName()];
            }
        }
        return $searchable;
    }

    /**
     * Get sortable properties
     *
     * @param iterable<GeneratorProperty> $properties
     * @return array<array{apiName: string, entityPath: string}>
     */
    private function getSortableProperties(iterable $properties): array
    {
        $sortable = [];
        foreach ($properties as $prop) {
            if ($prop->isSortable()) {
                $sortable[] = [
                    'apiName' => $prop->getPropertyName(),
                    'entityPath' => $prop->getPropertyName(),
                ];
            }
        }

        // Always add default sort fields
        $sortable[] = ['apiName' => 'createdAt', 'entityPath' => 'createdAt'];
        $sortable[] = ['apiName' => 'updatedAt', 'entityPath' => 'updatedAt'];

        return $sortable;
    }

    /**
     * Get filterable properties (excludes computed fields)
     *
     * @param iterable<GeneratorProperty> $properties
     * @return array<array{apiName: string, entityPath: string}>
     */
    private function getFilterableProperties(iterable $properties): array
    {
        $filterable = [];
        foreach ($properties as $prop) {
            // Only direct entity fields, not relationships or computed
            if ($prop->isFilterable() && !$prop->getRelationshipType()) {
                $filterable[] = [
                    'apiName' => $prop->getPropertyName(),
                    'entityPath' => $prop->getPropertyName(),
                ];
            }
        }

        // Add timestamp fields
        $filterable[] = ['apiName' => 'createdAt', 'entityPath' => 'createdAt'];
        $filterable[] = ['apiName' => 'updatedAt', 'entityPath' => 'updatedAt'];

        return $filterable;
    }

    /**
     * Get relationship filter properties
     *
     * @param iterable<GeneratorProperty> $properties
     * @return array<array{apiName: string, relation: string, field: string}>
     */
    private function getRelationshipFilterProperties(iterable $properties): array
    {
        $relationships = [];
        foreach ($properties as $prop) {
            if ($prop->isFilterable() && $prop->getRelationshipType()) {
                // Example: ownerName filters by owner.name
                $relationships[] = [
                    'apiName' => $prop->getPropertyName() . 'Name',
                    'relation' => $prop->getPropertyName(),
                    'field' => 'name',
                ];
            }
        }
        return $relationships;
    }

    /**
     * Get boolean properties
     *
     * @param iterable<GeneratorProperty> $properties
     * @return array<array{name: string}>
     */
    private function getBooleanProperties(iterable $properties): array
    {
        $booleans = [];
        foreach ($properties as $prop) {
            if ($prop->getPropertyType() === 'bool' || $prop->getPropertyType() === 'boolean') {
                $booleans[] = ['name' => $prop->getPropertyName()];
            }
        }
        return $booleans;
    }

    /**
     * Get date/datetime properties
     *
     * @param iterable<GeneratorProperty> $properties
     * @return array<array{name: string}>
     */
    private function getDateProperties(iterable $properties): array
    {
        $dates = [];
        foreach ($properties as $prop) {
            $type = $prop->getPropertyType();
            if (in_array($type, ['date', 'datetime', 'datetime_immutable', 'DateTimeImmutable'])) {
                $dates[] = ['name' => $prop->getPropertyName()];
            }
        }

        // Always add timestamp fields
        $dates[] = ['name' => 'createdAt'];
        $dates[] = ['name' => 'updatedAt'];

        return $dates;
    }

    /**
     * Get properties for entityToArray output
     *
     * @param iterable<GeneratorProperty> $properties
     * @return array<array{apiName: string, getter: string, isScalar: bool, isDate: bool, isUuid: bool, isRelation: bool, isCollection: bool}>
     */
    private function getOutputProperties(iterable $properties): array
    {
        $output = [];
        foreach ($properties as $prop) {
            $type = $prop->getPropertyType();
            $relationshipType = $prop->getRelationshipType();
            $getter = '$entity->get' . ucfirst($prop->getPropertyName()) . '()';

            // Check if it's a collection relationship (OneToMany or ManyToMany)
            $isCollection = $relationshipType && in_array($relationshipType, ['OneToMany', 'ManyToMany']);

            // Single relationship (ManyToOne or OneToOne)
            $isRelation = $relationshipType && !$isCollection;

            $output[] = [
                'apiName' => $prop->getPropertyName(),
                'getter' => $getter,
                'isScalar' => in_array($type, ['string', 'int', 'float', 'bool', 'text']),
                'isDate' => in_array($type, ['date', 'datetime', 'datetime_immutable', 'DateTimeImmutable']),
                'isUuid' => $type === 'Uuid' || $type === 'uuid',
                'isRelation' => $isRelation,
                'isCollection' => $isCollection,
            ];
        }

        return $output;
    }
}
