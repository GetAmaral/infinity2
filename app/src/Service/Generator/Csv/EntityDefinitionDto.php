<?php

declare(strict_types=1);

namespace App\Service\Generator\Csv;

class EntityDefinitionDto
{
    /**
     * @param array<PropertyDefinitionDto> $properties
     * @param array<string> $operations
     * @param array<string> $searchableFields
     * @param array<string> $filterableFields
     * @param array<string> $voterAttributes
     * @param array<string, string> $order
     */
    public function __construct(
        public readonly string $entityName,
        public readonly string $entityLabel,
        public readonly string $pluralLabel,
        public readonly string $icon,
        public readonly string $description,
        public readonly bool $hasOrganization,
        public readonly bool $apiEnabled,
        public readonly array $operations,
        public readonly string $security,
        public readonly string $normalizationContext,
        public readonly string $denormalizationContext,
        public readonly bool $paginationEnabled,
        public readonly int $itemsPerPage,
        public readonly array $order,
        public readonly array $searchableFields,
        public readonly array $filterableFields,
        public readonly bool $voterEnabled,
        public readonly array $voterAttributes,
        public readonly string $formTheme,
        public readonly string $indexTemplate,
        public readonly string $formTemplate,
        public readonly string $showTemplate,
        public readonly string $menuGroup,
        public readonly int $menuOrder,
        public readonly bool $testEnabled,
        public readonly array $properties = []
    ) {}

    /**
     * Create DTO from parsed CSV array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entityName: $data['entityName'],
            entityLabel: $data['entityLabel'],
            pluralLabel: $data['pluralLabel'],
            icon: $data['icon'],
            description: $data['description'] ?? '',
            hasOrganization: $data['hasOrganization'],
            apiEnabled: $data['apiEnabled'],
            operations: $data['operations'],
            security: $data['security'],
            normalizationContext: $data['normalizationContext'],
            denormalizationContext: $data['denormalizationContext'],
            paginationEnabled: $data['paginationEnabled'],
            itemsPerPage: $data['itemsPerPage'],
            order: $data['order'],
            searchableFields: $data['searchableFields'],
            filterableFields: $data['filterableFields'],
            voterEnabled: $data['voterEnabled'],
            voterAttributes: $data['voterAttributes'],
            formTheme: $data['formTheme'] ?? 'bootstrap_5_layout.html.twig',
            indexTemplate: $data['indexTemplate'] ?? '',
            formTemplate: $data['formTemplate'] ?? '',
            showTemplate: $data['showTemplate'] ?? '',
            menuGroup: $data['menuGroup'] ?? '',
            menuOrder: $data['menuOrder'],
            testEnabled: $data['testEnabled'],
            properties: array_map(
                fn($prop) => PropertyDefinitionDto::fromArray($prop),
                $data['properties'] ?? []
            )
        );
    }

    /**
     * Get lowercase entity name for routes/paths
     */
    public function getLowercaseName(): string
    {
        return strtolower($this->entityName);
    }

    /**
     * Get snake_case entity name for routes/paths
     */
    public function getSnakeCaseName(): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $this->entityName));
    }

    /**
     * Check if entity has relationships
     */
    public function hasRelationships(): bool
    {
        foreach ($this->properties as $property) {
            if ($property->relationshipType !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all relationship properties
     *
     * @return array<PropertyDefinitionDto>
     */
    public function getRelationshipProperties(): array
    {
        return array_filter(
            $this->properties,
            fn($prop) => $prop->relationshipType !== null
        );
    }

    /**
     * Get all non-relationship properties
     *
     * @return array<PropertyDefinitionDto>
     */
    public function getScalarProperties(): array
    {
        return array_filter(
            $this->properties,
            fn($prop) => $prop->relationshipType === null
        );
    }
}
