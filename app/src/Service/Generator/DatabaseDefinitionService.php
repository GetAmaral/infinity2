<?php

declare(strict_types=1);

namespace App\Service\Generator;

use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;
use Doctrine\ORM\EntityManagerInterface;

/**
 * DatabaseDefinitionService
 *
 * Converts GeneratorEntity and GeneratorProperty from database
 * into definition arrays compatible with existing Generator services
 */
class DatabaseDefinitionService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
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
            'description' => $entity->getDescription(),
            'hasOrganization' => $entity->isHasOrganization(),

            // API
            'apiEnabled' => $entity->isApiEnabled(),
            'apiOperations' => $entity->getApiOperations() ?? [],
            'apiSecurity' => $entity->getApiSecurity(),
            'apiNormalizationContext' => $entity->getApiNormalizationContext(),
            'apiDenormalizationContext' => $entity->getApiDenormalizationContext(),
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

    /**
     * Build property definition from database property
     */
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
     * Generate entity PHP code (placeholder - will integrate with existing generators)
     */
    public function generateEntityCode(array $definition): string
    {
        return sprintf("// Entity: %s\n// TODO: Integrate with EntityGenerator", $definition['entityName']);
    }

    /**
     * Generate repository PHP code (placeholder - will integrate with existing generators)
     */
    public function generateRepositoryCode(array $definition): string
    {
        return sprintf("// Repository: %sRepository\n// TODO: Integrate with RepositoryGenerator", $definition['entityName']);
    }

    /**
     * Generate form PHP code (placeholder - will integrate with existing generators)
     */
    public function generateFormCode(array $definition): string
    {
        return sprintf("// Form: %sFormType\n// TODO: Integrate with FormGenerator", $definition['entityName']);
    }

    /**
     * Generate controller PHP code (placeholder - will integrate with existing generators)
     */
    public function generateControllerCode(array $definition): string
    {
        return sprintf("// Controller: %sController\n// TODO: Integrate with ControllerGenerator", $definition['entityName']);
    }

    /**
     * Generate templates code (placeholder - will integrate with existing generators)
     */
    public function generateTemplatesCode(array $definition): array
    {
        return [
            'index' => sprintf("<!-- Index template for %s -->", $definition['entityName']),
            'show' => sprintf("<!-- Show template for %s -->", $definition['entityName']),
            'form' => sprintf("<!-- Form template for %s -->", $definition['entityName']),
        ];
    }

    /**
     * Generate API Platform configuration (placeholder)
     */
    public function generateApiPlatformCode(array $definition): ?string
    {
        if (!$definition['apiEnabled']) {
            return null;
        }

        return sprintf("// API Platform for %s\n// TODO: Integrate with ApiPlatformGenerator", $definition['entityName']);
    }

    /**
     * Generate Security Voter (placeholder)
     */
    public function generateVoterCode(array $definition): ?string
    {
        if (!$definition['voterEnabled']) {
            return null;
        }

        return sprintf("// Voter: %sVoter\n// TODO: Integrate with VoterGenerator", $definition['entityName']);
    }

    /**
     * Generate Tests (placeholder)
     */
    public function generateTestCode(array $definition): ?string
    {
        if (!$definition['testEnabled']) {
            return null;
        }

        return sprintf("// Tests for %s\n// TODO: Integrate with Test Generators", $definition['entityName']);
    }

    /**
     * Generate all files and write to disk (placeholder - will integrate with existing generators)
     */
    public function generateAllFiles(array $definition): array
    {
        // For now, return placeholders
        // TODO: Integrate with actual generator services
        $generatedFiles = [
            'entity' => sprintf('src/Entity/%s.php', $definition['entityName']),
            'repository' => sprintf('src/Repository/%sRepository.php', $definition['entityName']),
            'form' => sprintf('src/Form/%sFormType.php', $definition['entityName']),
            'controller' => sprintf('src/Controller/%sController.php', $definition['entityName']),
            'templates' => [
                'index' => sprintf('templates/%s/index.html.twig', strtolower($definition['entityName'])),
                'show' => sprintf('templates/%s/show.html.twig', strtolower($definition['entityName'])),
            ],
        ];

        if ($definition['apiEnabled']) {
            $generatedFiles['api'] = 'API Platform integrated in entity';
        }

        if ($definition['voterEnabled']) {
            $generatedFiles['voter'] = sprintf('src/Security/Voter/%sVoter.php', $definition['entityName']);
        }

        return $generatedFiles;
    }

    /**
     * Preview generated code without writing to disk
     */
    public function previewGeneration(array $definition): array
    {
        return [
            'entity' => $this->generateEntityCode($definition),
            'repository' => $this->generateRepositoryCode($definition),
            'form' => $this->generateFormCode($definition),
            'controller' => $this->generateControllerCode($definition),
            'templates' => $this->generateTemplatesCode($definition),
            'api' => $this->generateApiPlatformCode($definition),
            'voter' => $this->generateVoterCode($definition),
            'test' => $this->generateTestCode($definition),
        ];
    }
}
