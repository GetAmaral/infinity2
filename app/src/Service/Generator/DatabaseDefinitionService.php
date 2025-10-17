<?php

declare(strict_types=1);

namespace App\Service\Generator;

use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;
use App\Service\Generator\Csv\EntityDefinitionDto;
use App\Service\Generator\Csv\PropertyDefinitionDto;
use App\Service\Generator\Entity\EntityGenerator;
use App\Service\Generator\Controller\ControllerGenerator;
use App\Service\Generator\Form\FormGenerator;
use App\Service\Generator\Repository\RepositoryGenerator;
use App\Service\Generator\Template\TemplateGenerator;
use App\Service\Generator\ApiPlatform\ApiPlatformGenerator;
use App\Service\Generator\Voter\VoterGenerator;
use Doctrine\ORM\EntityManagerInterface;

/**
 * DatabaseDefinitionService
 *
 * Converts GeneratorEntity and GeneratorProperty from database
 * into DTOs compatible with existing Generator services
 */
class DatabaseDefinitionService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntityGenerator $entityGenerator,
        private readonly ControllerGenerator $controllerGenerator,
        private readonly FormGenerator $formGenerator,
        private readonly RepositoryGenerator $repositoryGenerator,
        private readonly TemplateGenerator $templateGenerator,
        private readonly ApiPlatformGenerator $apiPlatformGenerator,
        private readonly VoterGenerator $voterGenerator,
    ) {
    }

    /**
     * Build entity definition array from database entity
     */
    public function buildEntityDefinition(GeneratorEntity $entity): array
    {
        $definition = [
            'entityName' => $entity->getEntityName(),
            'entityLabel' => $entity->getEntityLabel(),
            'pluralLabel' => $entity->getPluralLabel(),
            'icon' => $entity->getIcon(),
            'description' => $entity->getDescription() ?? '',
            'hasOrganization' => $entity->isHasOrganization(),

            // API (map database field names to DTO field names)
            'apiEnabled' => $entity->isApiEnabled(),
            'operations' => $entity->getApiOperations() ?? ['GetCollection', 'Get', 'Post', 'Put', 'Delete'],
            'security' => $entity->getApiSecurity() ?? "is_granted('ROLE_USER')",
            'normalizationContext' => $entity->getApiNormalizationContext() ?? '',
            'denormalizationContext' => $entity->getApiDenormalizationContext() ?? '',
            'paginationEnabled' => $entity->isApiPaginationEnabled(),
            'itemsPerPage' => $entity->getApiItemsPerPage() ?? 30,
            'order' => $entity->getApiDefaultOrder() ?? [],
            'searchableFields' => $entity->getApiSearchableFields() ?? [],
            'filterableFields' => $entity->getApiFilterableFields() ?? [],

            // Security
            'voterEnabled' => $entity->isVoterEnabled(),
            'voterAttributes' => $entity->getVoterAttributes() ?? ['VIEW', 'EDIT', 'DELETE'],

            // Form
            'formTheme' => $entity->getFormTheme() ?? 'bootstrap_5_layout.html.twig',

            // Templates
            'indexTemplate' => $entity->getCustomIndexTemplate() ?? '',
            'formTemplate' => $entity->getCustomFormTemplate() ?? '',
            'showTemplate' => $entity->getCustomShowTemplate() ?? '',

            // Navigation
            'menuGroup' => $entity->getMenuGroup() ?? '',
            'menuOrder' => $entity->getMenuOrder() ?? 0,

            // Testing
            'testEnabled' => $entity->isTestEnabled(),

            // Properties
            'properties' => []
        ];

        foreach ($entity->getProperties() as $property) {
            $definition['properties'][] = $this->buildPropertyDefinition($property, $entity->getEntityName());
        }

        return $definition;
    }

    /**
     * Build property definition from database property
     */
    private function buildPropertyDefinition(GeneratorProperty $property, string $entityName): array
    {
        return [
            'entityName' => $entityName,
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
            'cascade' => $property->getCascade() ?? [],
            'orphanRemoval' => $property->isOrphanRemoval(),
            'fetch' => $property->getFetch(),
            'orderBy' => $property->getOrderBy() ?? [],

            // Validation
            'validationRules' => $property->getValidationRules() ?? [],
            'validationMessage' => $property->getValidationMessage(),

            // Form
            'formType' => $property->getFormType(),
            'formOptions' => $property->getFormOptions() ?? [],
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
            'apiGroups' => $property->getApiGroups() ?? [],

            // Indexing (not yet in database schema)
            'indexed' => false,
            'indexType' => null,
            'compositeIndexWith' => null,

            // Roles (not yet in database schema)
            'allowedRoles' => null,

            // Localization
            'translationKey' => $property->getTranslationKey(),
            'formatPattern' => $property->getFormatPattern(),

            // Fixtures
            'fixtureType' => $property->getFixtureType(),
            'fixtureOptions' => $property->getFixtureOptions() ?? [],
        ];
    }

    /**
     * Convert database GeneratorEntity to EntityDefinitionDto for generators
     */
    public function buildEntityDto(GeneratorEntity $entity): EntityDefinitionDto
    {
        $propertyDtos = [];
        foreach ($entity->getProperties() as $property) {
            $propertyDtos[] = $this->buildPropertyDto($property, $entity->getEntityName());
        }

        return new EntityDefinitionDto(
            entityName: $entity->getEntityName(),
            entityLabel: $entity->getEntityLabel(),
            pluralLabel: $entity->getPluralLabel(),
            icon: $entity->getIcon(),
            description: $entity->getDescription() ?? '',
            hasOrganization: $entity->isHasOrganization(),
            apiEnabled: $entity->isApiEnabled(),
            operations: $entity->getApiOperations() ?? ['GetCollection', 'Get', 'Post', 'Put', 'Delete'],
            security: $entity->getApiSecurity() ?? "is_granted('ROLE_USER')",
            normalizationContext: $entity->getApiNormalizationContext() ?? '',
            denormalizationContext: $entity->getApiDenormalizationContext() ?? '',
            paginationEnabled: $entity->isApiPaginationEnabled(),
            itemsPerPage: $entity->getApiItemsPerPage(),
            order: $entity->getApiDefaultOrder() ?? [],
            searchableFields: $entity->getApiSearchableFields() ?? [],
            filterableFields: $entity->getApiFilterableFields() ?? [],
            voterEnabled: $entity->isVoterEnabled(),
            voterAttributes: $entity->getVoterAttributes() ?? ['VIEW', 'EDIT', 'DELETE'],
            formTheme: $entity->getFormTheme(),
            indexTemplate: $entity->getCustomIndexTemplate() ?? '',
            formTemplate: $entity->getCustomFormTemplate() ?? '',
            showTemplate: $entity->getCustomShowTemplate() ?? '',
            menuGroup: $entity->getMenuGroup() ?? '',
            menuOrder: $entity->getMenuOrder(),
            testEnabled: $entity->isTestEnabled(),
            properties: $propertyDtos
        );
    }

    /**
     * Convert database GeneratorProperty to PropertyDefinitionDto
     */
    private function buildPropertyDto(GeneratorProperty $property, string $entityName): PropertyDefinitionDto
    {
        return new PropertyDefinitionDto(
            entityName: $entityName,
            propertyName: $property->getPropertyName(),
            propertyLabel: $property->getPropertyLabel(),
            propertyType: $property->getPropertyType(),
            nullable: $property->isNullable(),
            length: $property->getLength(),
            precision: $property->getPrecision(),
            scale: $property->getScale(),
            unique: $property->isUnique(),
            defaultValue: $property->getDefaultValue(),
            relationshipType: $property->getRelationshipType(),
            targetEntity: $property->getTargetEntity(),
            inversedBy: $property->getInversedBy(),
            mappedBy: $property->getMappedBy(),
            cascade: $property->getCascade() ?? [],
            orphanRemoval: $property->isOrphanRemoval(),
            fetch: $property->getFetch(),
            orderBy: $property->getOrderBy() ?? [],
            indexed: false, // Not in database yet
            indexType: null,
            compositeIndexWith: null,
            validationRules: $property->getValidationRules() ?? [],
            validationMessage: $property->getValidationMessage(),
            formType: $property->getFormType(),
            formOptions: $property->getFormOptions() ?? [],
            formRequired: $property->isFormRequired(),
            formReadOnly: $property->isFormReadOnly(),
            formHelp: $property->getFormHelp(),
            showInList: $property->isShowInList(),
            showInDetail: $property->isShowInDetail(),
            showInForm: $property->isShowInForm(),
            sortable: $property->isSortable(),
            searchable: $property->isSearchable(),
            filterable: $property->isFilterable(),
            apiReadable: $property->isApiReadable(),
            apiWritable: $property->isApiWritable(),
            apiGroups: $property->getApiGroups() ?? [],
            allowedRoles: null,
            translationKey: $property->getTranslationKey(),
            formatPattern: $property->getFormatPattern(),
            fixtureType: $property->getFixtureType(),
            fixtureOptions: $property->getFixtureOptions() ?? []
        );
    }

    /**
     * Generate all files and write to disk using existing generators
     */
    public function generateAllFiles(array $definition): array
    {
        // Convert to DTO first
        $entityDto = EntityDefinitionDto::fromArray($definition);

        $generatedFiles = [];

        try {
            // Generate Entity (both Generated base and extension)
            $entityFiles = $this->entityGenerator->generate($entityDto);
            $generatedFiles['entity'] = $entityFiles;

            // Generate Repository
            $repositoryFiles = $this->repositoryGenerator->generate($entityDto);
            $generatedFiles['repository'] = $repositoryFiles;

            // Generate Form
            $formFiles = $this->formGenerator->generate($entityDto);
            $generatedFiles['form'] = $formFiles;

            // Generate Controller
            $controllerFiles = $this->controllerGenerator->generate($entityDto);
            $generatedFiles['controller'] = $controllerFiles;

            // Generate Templates
            $templateFiles = $this->templateGenerator->generate($entityDto);
            $generatedFiles['templates'] = $templateFiles;

            // Generate API Platform (if enabled)
            if ($entityDto->apiEnabled) {
                $apiFiles = $this->apiPlatformGenerator->generate($entityDto);
                $generatedFiles['api'] = $apiFiles;
            }

            // Generate Voter (if enabled)
            if ($entityDto->voterEnabled) {
                $voterFiles = $this->voterGenerator->generate($entityDto);
                $generatedFiles['voter'] = $voterFiles;
            }

            return $generatedFiles;

        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Failed to generate files for %s: %s', $definition['entityName'], $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Preview generated code without writing to disk
     */
    public function previewGeneration(array $definition): array
    {
        // For preview, we just show the file paths that would be generated
        $entityDto = EntityDefinitionDto::fromArray($definition);

        $preview = [
            'entity' => [
                'Generated/' . $entityDto->entityName . 'Generated.php',
                $entityDto->entityName . '.php',
            ],
            'repository' => [
                $entityDto->entityName . 'Repository.php',
            ],
            'form' => [
                $entityDto->entityName . 'FormType.php',
            ],
            'controller' => [
                $entityDto->entityName . 'Controller.php',
            ],
            'templates' => [
                'index.html.twig',
                'show.html.twig',
                '_form.html.twig',
            ],
        ];

        if ($entityDto->apiEnabled) {
            $preview['api'] = 'Integrated in entity class';
        }

        if ($entityDto->voterEnabled) {
            $preview['voter'] = [$entityDto->entityName . 'Voter.php'];
        }

        return $preview;
    }
}
