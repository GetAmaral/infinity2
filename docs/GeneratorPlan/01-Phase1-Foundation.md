# Phase 1: Foundation (Week 1)

## Overview

Phase 1 establishes the core infrastructure for the code generator:
- CSV parsing and validation
- Data Transfer Objects (DTOs)
- Backup and restore system
- Error handling framework

**Duration:** Week 1 (5 working days)

**Deliverables:**
- ✅ CsvParserService - Reads and parses Entity.csv and Property.csv
- ✅ CsvValidatorService - Validates CSV data against rules
- ✅ EntityDefinitionDto - Entity metadata container
- ✅ PropertyDefinitionDto - Property metadata container
- ✅ BackupService - Creates and restores timestamped backups
- ✅ Unit tests for all services (80%+ coverage)

---

## Day 1: CSV Parser Service

### File: `src/Service/Generator/Csv/CsvParserService.php`

**Purpose:** Parse Entity.csv and Property.csv into structured data.

**Key Features:**
- Reads CSV files using PHP's native CSV functions
- Converts CSV rows to associative arrays
- Validates CSV structure (column count, headers)
- Handles quoted values, special characters, JSON fields
- Links properties to parent entities

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Service\Generator\Csv;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class CsvParserService
{
    private const ENTITY_CSV_PATH = __DIR__ . '/../../../../config/Entity.csv';
    private const PROPERTY_CSV_PATH = __DIR__ . '/../../../../config/Property.csv';

    private const ENTITY_COLUMNS = [
        'entityName', 'entityLabel', 'pluralLabel', 'icon', 'description',
        'hasOrganization', 'apiEnabled', 'operations', 'security',
        'normalizationContext', 'denormalizationContext', 'paginationEnabled',
        'itemsPerPage', 'order', 'searchableFields', 'filterableFields',
        'voterEnabled', 'voterAttributes', 'formTheme', 'indexTemplate',
        'formTemplate', 'showTemplate', 'menuGroup', 'menuOrder', 'testEnabled'
    ];

    private const PROPERTY_COLUMNS = [
        'entityName', 'propertyName', 'propertyLabel', 'propertyType',
        'nullable', 'length', 'precision', 'scale', 'unique', 'defaultValue',
        'relationshipType', 'targetEntity', 'inversedBy', 'mappedBy',
        'cascade', 'orphanRemoval', 'fetch', 'orderBy', 'validationRules',
        'validationMessage', 'formType', 'formOptions', 'formRequired',
        'formReadOnly', 'formHelp', 'showInList', 'showInDetail',
        'showInForm', 'sortable', 'searchable', 'filterable', 'apiReadable',
        'apiWritable', 'apiGroups', 'translationKey', 'formatPattern',
        'fixtureType', 'fixtureOptions'
    ];

    /**
     * Parse both CSV files and return structured data
     *
     * @return array{entities: array<EntityDefinitionDto>, properties: array<string, array<PropertyDefinitionDto>>}
     */
    public function parseAll(): array
    {
        $entities = $this->parseEntityCsv();
        $properties = $this->parsePropertyCsv();

        // Link properties to entities
        $entitiesWithProperties = [];
        foreach ($entities as $entity) {
            $entityName = $entity['entityName'];
            $entity['properties'] = $properties[$entityName] ?? [];
            $entitiesWithProperties[] = $entity;
        }

        return [
            'entities' => $entitiesWithProperties,
            'properties' => $properties
        ];
    }

    /**
     * Parse Entity.csv
     *
     * @return array<array<string, mixed>>
     */
    public function parseEntityCsv(): array
    {
        if (!file_exists(self::ENTITY_CSV_PATH)) {
            throw new FileNotFoundException(sprintf(
                'Entity CSV file not found at: %s',
                self::ENTITY_CSV_PATH
            ));
        }

        $handle = fopen(self::ENTITY_CSV_PATH, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Failed to open Entity.csv');
        }

        $entities = [];
        $headers = null;
        $lineNumber = 0;

        while (($row = fgetcsv($handle, 10000, ',')) !== false) {
            $lineNumber++;

            // First row is headers
            if ($headers === null) {
                $headers = $row;
                $this->validateHeaders($headers, self::ENTITY_COLUMNS, 'Entity.csv');
                continue;
            }

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Validate column count
            if (count($row) !== count($headers)) {
                throw new \RuntimeException(sprintf(
                    'Entity.csv line %d: Expected %d columns, got %d',
                    $lineNumber,
                    count($headers),
                    count($row)
                ));
            }

            // Combine headers with row data
            $entity = array_combine($headers, $row);

            // Type casting and normalization
            $entity = $this->normalizeEntityData($entity, $lineNumber);

            $entities[] = $entity;
        }

        fclose($handle);

        return $entities;
    }

    /**
     * Parse Property.csv
     *
     * @return array<string, array<array<string, mixed>>>
     */
    public function parsePropertyCsv(): array
    {
        if (!file_exists(self::PROPERTY_CSV_PATH)) {
            throw new FileNotFoundException(sprintf(
                'Property CSV file not found at: %s',
                self::PROPERTY_CSV_PATH
            ));
        }

        $handle = fopen(self::PROPERTY_CSV_PATH, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Failed to open Property.csv');
        }

        $properties = [];
        $headers = null;
        $lineNumber = 0;

        while (($row = fgetcsv($handle, 10000, ',')) !== false) {
            $lineNumber++;

            // First row is headers
            if ($headers === null) {
                $headers = $row;
                $this->validateHeaders($headers, self::PROPERTY_COLUMNS, 'Property.csv');
                continue;
            }

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Validate column count
            if (count($row) !== count($headers)) {
                throw new \RuntimeException(sprintf(
                    'Property.csv line %d: Expected %d columns, got %d',
                    $lineNumber,
                    count($headers),
                    count($row)
                ));
            }

            // Combine headers with row data
            $property = array_combine($headers, $row);

            // Type casting and normalization
            $property = $this->normalizePropertyData($property, $lineNumber);

            // Group by entity name
            $entityName = $property['entityName'];
            if (!isset($properties[$entityName])) {
                $properties[$entityName] = [];
            }
            $properties[$entityName][] = $property;
        }

        fclose($handle);

        return $properties;
    }

    /**
     * Validate CSV headers match expected columns
     */
    private function validateHeaders(array $headers, array $expected, string $filename): void
    {
        $missing = array_diff($expected, $headers);
        if (!empty($missing)) {
            throw new \RuntimeException(sprintf(
                '%s: Missing required columns: %s',
                $filename,
                implode(', ', $missing)
            ));
        }
    }

    /**
     * Normalize entity data (type casting, defaults)
     */
    private function normalizeEntityData(array $entity, int $lineNumber): array
    {
        // Boolean conversions
        $entity['hasOrganization'] = $this->parseBoolean($entity['hasOrganization'] ?? 'false');
        $entity['apiEnabled'] = $this->parseBoolean($entity['apiEnabled'] ?? 'true');
        $entity['paginationEnabled'] = $this->parseBoolean($entity['paginationEnabled'] ?? 'true');
        $entity['voterEnabled'] = $this->parseBoolean($entity['voterEnabled'] ?? 'true');
        $entity['testEnabled'] = $this->parseBoolean($entity['testEnabled'] ?? 'true');

        // Integer conversions
        $entity['itemsPerPage'] = (int)($entity['itemsPerPage'] ?? 30);
        $entity['menuOrder'] = (int)($entity['menuOrder'] ?? 0);

        // JSON field conversions
        $entity['order'] = $this->parseJson($entity['order'] ?? '{}', $lineNumber, 'order');

        // CSV list conversions
        $entity['operations'] = $this->parseCsvList($entity['operations'] ?? '');
        $entity['searchableFields'] = $this->parseCsvList($entity['searchableFields'] ?? '');
        $entity['filterableFields'] = $this->parseCsvList($entity['filterableFields'] ?? '');
        $entity['voterAttributes'] = $this->parseCsvList($entity['voterAttributes'] ?? 'VIEW,EDIT,DELETE');

        // String normalizations
        $entity['normalizationContext'] = trim($entity['normalizationContext'] ?? '');
        $entity['denormalizationContext'] = trim($entity['denormalizationContext'] ?? '');
        $entity['security'] = trim($entity['security'] ?? "is_granted('ROLE_USER')");

        return $entity;
    }

    /**
     * Normalize property data (type casting, defaults)
     */
    private function normalizePropertyData(array $property, int $lineNumber): array
    {
        // Boolean conversions
        $property['nullable'] = $this->parseBoolean($property['nullable'] ?? 'true');
        $property['unique'] = $this->parseBoolean($property['unique'] ?? 'false');
        $property['orphanRemoval'] = $this->parseBoolean($property['orphanRemoval'] ?? 'false');
        $property['formRequired'] = $this->parseBoolean($property['formRequired'] ?? 'true');
        $property['formReadOnly'] = $this->parseBoolean($property['formReadOnly'] ?? 'false');
        $property['showInList'] = $this->parseBoolean($property['showInList'] ?? 'true');
        $property['showInDetail'] = $this->parseBoolean($property['showInDetail'] ?? 'true');
        $property['showInForm'] = $this->parseBoolean($property['showInForm'] ?? 'true');
        $property['sortable'] = $this->parseBoolean($property['sortable'] ?? 'true');
        $property['searchable'] = $this->parseBoolean($property['searchable'] ?? 'false');
        $property['filterable'] = $this->parseBoolean($property['filterable'] ?? 'false');
        $property['apiReadable'] = $this->parseBoolean($property['apiReadable'] ?? 'true');
        $property['apiWritable'] = $this->parseBoolean($property['apiWritable'] ?? 'true');

        // Integer conversions
        $property['length'] = $property['length'] !== '' ? (int)$property['length'] : null;
        $property['precision'] = $property['precision'] !== '' ? (int)$property['precision'] : null;
        $property['scale'] = $property['scale'] !== '' ? (int)$property['scale'] : null;

        // JSON field conversions
        $property['orderBy'] = $this->parseJson($property['orderBy'] ?? '{}', $lineNumber, 'orderBy');
        $property['formOptions'] = $this->parseJson($property['formOptions'] ?? '{}', $lineNumber, 'formOptions');
        $property['fixtureOptions'] = $this->parseJson($property['fixtureOptions'] ?? '{}', $lineNumber, 'fixtureOptions');

        // CSV list conversions
        $property['validationRules'] = $this->parseCsvList($property['validationRules'] ?? '');
        $property['cascade'] = $this->parseCsvList($property['cascade'] ?? '');
        $property['apiGroups'] = $this->parseCsvList($property['apiGroups'] ?? '');

        return $property;
    }

    /**
     * Parse boolean value from CSV
     */
    private function parseBoolean(string $value): bool
    {
        $normalized = strtolower(trim($value));
        return in_array($normalized, ['true', '1', 'yes', 'y'], true);
    }

    /**
     * Parse JSON field from CSV
     */
    private function parseJson(string $value, int $lineNumber, string $fieldName): array|string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(sprintf(
                'Line %d: Invalid JSON in field "%s": %s',
                $lineNumber,
                $fieldName,
                json_last_error_msg()
            ));
        }

        return $decoded;
    }

    /**
     * Parse comma-separated list from CSV
     */
    private function parseCsvList(string $value): array
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        return array_map('trim', explode(',', $trimmed));
    }
}
```

**Tests:** `tests/Service/Generator/Csv/CsvParserServiceTest.php`

---

## Day 2: CSV Validator Service

### File: `src/Service/Generator/Csv/CsvValidatorService.php`

**Purpose:** Validate parsed CSV data against business rules.

**Validation Rules:**

**Entity Validation:**
- Entity name is required, alphanumeric, PascalCase
- Entity label and plural label are required
- Icon follows Bootstrap icon format (`bi-*`)
- Operations are valid API Platform operations
- Items per page is 1-1000
- Menu order is 0-999
- Security expression is valid Symfony security syntax

**Property Validation:**
- Property name is required, camelCase
- Property belongs to existing entity
- Type is valid Doctrine type
- Length required for string types
- Relationship fields (targetEntity, inversedBy, mappedBy) valid
- Target entity exists in Entity.csv
- Validation rules are valid Symfony constraints
- Form type is valid Symfony form type
- API groups match entity normalization/denormalization groups

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Service\Generator\Csv;

use Psr\Log\LoggerInterface;

class CsvValidatorService
{
    private const VALID_DOCTRINE_TYPES = [
        'string', 'text', 'integer', 'bigint', 'smallint', 'decimal', 'float',
        'boolean', 'date', 'time', 'datetime', 'datetime_immutable',
        'date_immutable', 'datetimetz', 'datetimetz_immutable', 'array',
        'simple_array', 'json', 'guid', 'blob'
    ];

    private const VALID_RELATIONSHIP_TYPES = [
        'ManyToOne', 'OneToMany', 'ManyToMany', 'OneToOne'
    ];

    private const VALID_API_OPERATIONS = [
        'GetCollection', 'Get', 'Post', 'Put', 'Patch', 'Delete'
    ];

    private const VALID_SYMFONY_FORM_TYPES = [
        'TextType', 'EmailType', 'PasswordType', 'TextareaType', 'IntegerType',
        'NumberType', 'MoneyType', 'DateType', 'DateTimeType', 'TimeType',
        'CheckboxType', 'ChoiceType', 'EntityType', 'FileType', 'HiddenType',
        'UrlType', 'TelType', 'ColorType', 'RangeType', 'SearchType'
    ];

    private const VALID_SYMFONY_CONSTRAINTS = [
        'NotBlank', 'NotNull', 'Blank', 'IsNull', 'IsTrue', 'IsFalse',
        'Type', 'Email', 'Length', 'Url', 'Regex', 'Ip', 'Uuid', 'Range',
        'EqualTo', 'NotEqualTo', 'IdenticalTo', 'NotIdenticalTo',
        'LessThan', 'LessThanOrEqual', 'GreaterThan', 'GreaterThanOrEqual',
        'Date', 'DateTime', 'Time', 'Timezone', 'Choice', 'Count', 'UniqueEntity',
        'Language', 'Locale', 'Country', 'CardScheme', 'Bic', 'Iban', 'Currency',
        'Luhn', 'Issn', 'Isbn', 'Json', 'Positive', 'PositiveOrZero',
        'Negative', 'NegativeOrZero'
    ];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Validate all entities and properties
     *
     * @param array<array<string, mixed>> $entities
     * @param array<string, array<array<string, mixed>>> $properties
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateAll(array $entities, array $properties): array
    {
        $errors = [];

        // Validate each entity
        foreach ($entities as $entity) {
            $entityErrors = $this->validateEntity($entity);
            $errors = array_merge($errors, $entityErrors);
        }

        // Build entity name lookup
        $entityNames = array_column($entities, 'entityName');

        // Validate each property
        foreach ($properties as $entityName => $entityProperties) {
            foreach ($entityProperties as $property) {
                $propertyErrors = $this->validateProperty($property, $entityNames);
                $errors = array_merge($errors, $propertyErrors);
            }
        }

        // Log validation results
        if (empty($errors)) {
            $this->logger->info('CSV validation passed', [
                'entities_count' => count($entities),
                'properties_count' => array_sum(array_map('count', $properties))
            ]);
        } else {
            $this->logger->error('CSV validation failed', [
                'error_count' => count($errors),
                'errors' => $errors
            ]);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate single entity
     *
     * @return array<string>
     */
    private function validateEntity(array $entity): array
    {
        $errors = [];
        $name = $entity['entityName'] ?? 'Unknown';

        // Required fields
        if (empty($entity['entityName'])) {
            $errors[] = "Entity: Missing entityName";
        } elseif (!$this->isPascalCase($entity['entityName'])) {
            $errors[] = "Entity '{$name}': entityName must be PascalCase";
        }

        if (empty($entity['entityLabel'])) {
            $errors[] = "Entity '{$name}': Missing entityLabel";
        }

        if (empty($entity['pluralLabel'])) {
            $errors[] = "Entity '{$name}': Missing pluralLabel";
        }

        if (empty($entity['icon'])) {
            $errors[] = "Entity '{$name}': Missing icon";
        } elseif (!str_starts_with($entity['icon'], 'bi-')) {
            $errors[] = "Entity '{$name}': icon must start with 'bi-'";
        }

        // Validate operations
        if ($entity['apiEnabled'] && !empty($entity['operations'])) {
            foreach ($entity['operations'] as $operation) {
                if (!in_array($operation, self::VALID_API_OPERATIONS, true)) {
                    $errors[] = "Entity '{$name}': Invalid API operation '{$operation}'";
                }
            }
        }

        // Validate integers
        if ($entity['itemsPerPage'] < 1 || $entity['itemsPerPage'] > 1000) {
            $errors[] = "Entity '{$name}': itemsPerPage must be 1-1000";
        }

        if ($entity['menuOrder'] < 0 || $entity['menuOrder'] > 999) {
            $errors[] = "Entity '{$name}': menuOrder must be 0-999";
        }

        return $errors;
    }

    /**
     * Validate single property
     *
     * @param array<string> $validEntityNames
     * @return array<string>
     */
    private function validateProperty(array $property, array $validEntityNames): array
    {
        $errors = [];
        $entityName = $property['entityName'] ?? 'Unknown';
        $propertyName = $property['propertyName'] ?? 'unknown';
        $context = "{$entityName}.{$propertyName}";

        // Entity exists
        if (!in_array($entityName, $validEntityNames, true)) {
            $errors[] = "Property '{$context}': Entity '{$entityName}' not found in Entity.csv";
        }

        // Required fields
        if (empty($property['propertyName'])) {
            $errors[] = "Property in '{$entityName}': Missing propertyName";
        } elseif (!$this->isCamelCase($property['propertyName'])) {
            $errors[] = "Property '{$context}': propertyName must be camelCase";
        }

        if (empty($property['propertyLabel'])) {
            $errors[] = "Property '{$context}': Missing propertyLabel";
        }

        // Validate Doctrine type
        if (empty($property['propertyType'])) {
            $errors[] = "Property '{$context}': Missing propertyType";
        } elseif (!in_array($property['propertyType'], self::VALID_DOCTRINE_TYPES, true)) {
            $errors[] = "Property '{$context}': Invalid Doctrine type '{$property['propertyType']}'";
        }

        // String types need length
        if (in_array($property['propertyType'], ['string'], true) && empty($property['length'])) {
            $errors[] = "Property '{$context}': String type requires length";
        }

        // Validate relationships
        if (!empty($property['relationshipType'])) {
            if (!in_array($property['relationshipType'], self::VALID_RELATIONSHIP_TYPES, true)) {
                $errors[] = "Property '{$context}': Invalid relationship type '{$property['relationshipType']}'";
            }

            if (empty($property['targetEntity'])) {
                $errors[] = "Property '{$context}': Relationship requires targetEntity";
            } elseif (!in_array($property['targetEntity'], $validEntityNames, true)) {
                $errors[] = "Property '{$context}': targetEntity '{$property['targetEntity']}' not found";
            }

            // Validate inversedBy/mappedBy
            if (in_array($property['relationshipType'], ['OneToMany', 'ManyToMany'], true)) {
                if (empty($property['mappedBy']) && empty($property['inversedBy'])) {
                    $errors[] = "Property '{$context}': {$property['relationshipType']} requires mappedBy or inversedBy";
                }
            }
        }

        // Validate validation rules
        if (!empty($property['validationRules'])) {
            foreach ($property['validationRules'] as $rule) {
                // Extract constraint name (before parentheses if exists)
                $constraintName = preg_replace('/\(.*\)/', '', $rule);
                if (!in_array($constraintName, self::VALID_SYMFONY_CONSTRAINTS, true)) {
                    $errors[] = "Property '{$context}': Invalid validation constraint '{$constraintName}'";
                }
            }
        }

        // Validate form type
        if (!empty($property['formType']) && !in_array($property['formType'], self::VALID_SYMFONY_FORM_TYPES, true)) {
            $errors[] = "Property '{$context}': Invalid form type '{$property['formType']}'";
        }

        return $errors;
    }

    private function isPascalCase(string $value): bool
    {
        return preg_match('/^[A-Z][a-zA-Z0-9]*$/', $value) === 1;
    }

    private function isCamelCase(string $value): bool
    {
        return preg_match('/^[a-z][a-zA-Z0-9]*$/', $value) === 1;
    }
}
```

**Tests:** `tests/Service/Generator/Csv/CsvValidatorServiceTest.php`

---

## Day 3: Data Transfer Objects (DTOs)

### Files:
- `src/Service/Generator/Csv/EntityDefinitionDto.php`
- `src/Service/Generator/Csv/PropertyDefinitionDto.php`

**Purpose:** Type-safe containers for entity and property metadata.

**EntityDefinitionDto:**

```php
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
```

**PropertyDefinitionDto:**

```php
<?php

declare(strict_types=1);

namespace App\Service\Generator\Csv;

class PropertyDefinitionDto
{
    /**
     * @param array<string> $validationRules
     * @param array<string, mixed> $formOptions
     * @param array<string> $cascade
     * @param array<string, string> $orderBy
     * @param array<string> $apiGroups
     * @param array<string, mixed> $fixtureOptions
     */
    public function __construct(
        public readonly string $entityName,
        public readonly string $propertyName,
        public readonly string $propertyLabel,
        public readonly string $propertyType,
        public readonly bool $nullable,
        public readonly ?int $length,
        public readonly ?int $precision,
        public readonly ?int $scale,
        public readonly bool $unique,
        public readonly ?string $defaultValue,
        public readonly ?string $relationshipType,
        public readonly ?string $targetEntity,
        public readonly ?string $inversedBy,
        public readonly ?string $mappedBy,
        public readonly array $cascade,
        public readonly bool $orphanRemoval,
        public readonly ?string $fetch,
        public readonly array $orderBy,
        public readonly array $validationRules,
        public readonly ?string $validationMessage,
        public readonly ?string $formType,
        public readonly array $formOptions,
        public readonly bool $formRequired,
        public readonly bool $formReadOnly,
        public readonly ?string $formHelp,
        public readonly bool $showInList,
        public readonly bool $showInDetail,
        public readonly bool $showInForm,
        public readonly bool $sortable,
        public readonly bool $searchable,
        public readonly bool $filterable,
        public readonly bool $apiReadable,
        public readonly bool $apiWritable,
        public readonly array $apiGroups,
        public readonly ?string $translationKey,
        public readonly ?string $formatPattern,
        public readonly ?string $fixtureType,
        public readonly array $fixtureOptions
    ) {}

    /**
     * Create DTO from parsed CSV array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entityName: $data['entityName'],
            propertyName: $data['propertyName'],
            propertyLabel: $data['propertyLabel'],
            propertyType: $data['propertyType'],
            nullable: $data['nullable'],
            length: $data['length'],
            precision: $data['precision'],
            scale: $data['scale'],
            unique: $data['unique'],
            defaultValue: $data['defaultValue'] ?? null,
            relationshipType: $data['relationshipType'] ?? null,
            targetEntity: $data['targetEntity'] ?? null,
            inversedBy: $data['inversedBy'] ?? null,
            mappedBy: $data['mappedBy'] ?? null,
            cascade: $data['cascade'],
            orphanRemoval: $data['orphanRemoval'],
            fetch: $data['fetch'] ?? null,
            orderBy: $data['orderBy'],
            validationRules: $data['validationRules'],
            validationMessage: $data['validationMessage'] ?? null,
            formType: $data['formType'] ?? null,
            formRequired: $data['formRequired'],
            formReadOnly: $data['formReadOnly'],
            formHelp: $data['formHelp'] ?? null,
            formOptions: $data['formOptions'],
            showInList: $data['showInList'],
            showInDetail: $data['showInDetail'],
            showInForm: $data['showInForm'],
            sortable: $data['sortable'],
            searchable: $data['searchable'],
            filterable: $data['filterable'],
            apiReadable: $data['apiReadable'],
            apiWritable: $data['apiWritable'],
            apiGroups: $data['apiGroups'],
            translationKey: $data['translationKey'] ?? null,
            formatPattern: $data['formatPattern'] ?? null,
            fixtureType: $data['fixtureType'] ?? null,
            fixtureOptions: $data['fixtureOptions']
        );
    }

    /**
     * Check if property is a relationship
     */
    public function isRelationship(): bool
    {
        return $this->relationshipType !== null;
    }

    /**
     * Check if property is collection (OneToMany, ManyToMany)
     */
    public function isCollection(): bool
    {
        return in_array($this->relationshipType, ['OneToMany', 'ManyToMany'], true);
    }

    /**
     * Check if property is single relationship (ManyToOne, OneToOne)
     */
    public function isSingleRelationship(): bool
    {
        return in_array($this->relationshipType, ['ManyToOne', 'OneToOne'], true);
    }

    /**
     * Get PHP type hint for property
     */
    public function getPhpType(): string
    {
        if ($this->isRelationship()) {
            if ($this->isCollection()) {
                return 'Collection';
            }
            return $this->targetEntity;
        }

        return match($this->propertyType) {
            'string', 'text' => 'string',
            'integer', 'smallint', 'bigint' => 'int',
            'decimal', 'float' => 'float',
            'boolean' => 'bool',
            'date', 'datetime', 'datetime_immutable', 'date_immutable',
            'time', 'datetimetz', 'datetimetz_immutable' => '\\DateTimeInterface',
            'array', 'simple_array', 'json' => 'array',
            default => 'mixed'
        };
    }

    /**
     * Get default Symfony form type if not specified
     */
    public function getFormType(): string
    {
        if ($this->formType !== null) {
            return $this->formType;
        }

        if ($this->isRelationship()) {
            return 'EntityType';
        }

        return match($this->propertyType) {
            'text' => 'TextareaType',
            'integer', 'smallint', 'bigint' => 'IntegerType',
            'decimal', 'float' => 'NumberType',
            'boolean' => 'CheckboxType',
            'date' => 'DateType',
            'datetime', 'datetime_immutable' => 'DateTimeType',
            'time' => 'TimeType',
            default => 'TextType'
        };
    }
}
```

**Tests:**
- `tests/Service/Generator/Csv/EntityDefinitionDtoTest.php`
- `tests/Service/Generator/Csv/PropertyDefinitionDtoTest.php`

---

## Day 4-5: Backup Service

### File: `src/Service/BackupService.php`

**Purpose:** Create and restore timestamped backups of generated files with verification.

**Key Features:**
- Timestamped backup directories (`/var/generatorBackup/20250107_143022/`)
- MD5 checksum verification
- manifest.json with metadata
- Safety backups before restore
- Uses Symfony Process for secure file operations

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Psr\Log\LoggerInterface;

class BackupService
{
    private const BACKUP_DIR = __DIR__ . '/../../var/generatorBackup';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Create backup of files before generation
     *
     * @param array<string> $filePaths
     * @return string Backup directory path
     */
    public function createBackup(array $filePaths, string $reason = 'generation'): string
    {
        $timestamp = date('Ymd_His');
        $backupDir = self::BACKUP_DIR . '/' . $timestamp;

        $this->filesystem->mkdir($backupDir);
        $this->logger->info('Creating backup', ['dir' => $backupDir, 'reason' => $reason]);

        $manifest = [
            'timestamp' => $timestamp,
            'reason' => $reason,
            'files' => []
        ];

        foreach ($filePaths as $filePath) {
            if (!file_exists($filePath)) {
                continue;
            }

            // Backup file
            $relativePath = $this->getRelativePath($filePath);
            $backupPath = $backupDir . '/' . str_replace('/', '_', $relativePath) . '.bak';

            $this->filesystem->copy($filePath, $backupPath);

            // Generate checksum
            $checksum = md5_file($filePath);
            file_put_contents($backupPath . '.md5', $checksum);

            $manifest['files'][] = [
                'original' => $filePath,
                'backup' => $backupPath,
                'checksum' => $checksum,
                'size' => filesize($filePath)
            ];

            $this->logger->debug('Backed up file', [
                'original' => $filePath,
                'backup' => $backupPath
            ]);
        }

        // Write manifest
        file_put_contents(
            $backupDir . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT)
        );

        $this->logger->info('Backup created', [
            'dir' => $backupDir,
            'file_count' => count($manifest['files'])
        ]);

        return $backupDir;
    }

    /**
     * Restore files from backup
     */
    public function restoreBackup(string $backupDir): void
    {
        $manifestPath = $backupDir . '/manifest.json';

        if (!file_exists($manifestPath)) {
            throw new \RuntimeException("Backup manifest not found: {$manifestPath}");
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        $this->logger->warning('Restoring backup', ['dir' => $backupDir]);

        // Create safety backup before restore
        $originalFiles = array_column($manifest['files'], 'original');
        $safetyBackup = $this->createBackup($originalFiles, 'safety_before_restore');

        $this->logger->info('Safety backup created', ['dir' => $safetyBackup]);

        foreach ($manifest['files'] as $fileInfo) {
            $backupPath = $fileInfo['backup'];
            $originalPath = $fileInfo['original'];

            if (!file_exists($backupPath)) {
                $this->logger->error('Backup file not found', ['path' => $backupPath]);
                continue;
            }

            // Verify checksum
            $storedChecksum = file_get_contents($backupPath . '.md5');
            $actualChecksum = md5_file($backupPath);

            if ($storedChecksum !== $actualChecksum) {
                throw new \RuntimeException(
                    "Checksum mismatch for {$backupPath}: expected {$storedChecksum}, got {$actualChecksum}"
                );
            }

            // Restore file
            $this->filesystem->copy($backupPath, $originalPath);

            $this->logger->info('Restored file', [
                'from' => $backupPath,
                'to' => $originalPath
            ]);
        }

        $this->logger->warning('Backup restored', [
            'dir' => $backupDir,
            'file_count' => count($manifest['files'])
        ]);
    }

    /**
     * List all backups
     *
     * @return array<array<string, mixed>>
     */
    public function listBackups(): array
    {
        if (!is_dir(self::BACKUP_DIR)) {
            return [];
        }

        $backups = [];
        $dirs = scandir(self::BACKUP_DIR, SCANDIR_SORT_DESCENDING);

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $backupDir = self::BACKUP_DIR . '/' . $dir;
            $manifestPath = $backupDir . '/manifest.json';

            if (!file_exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode(file_get_contents($manifestPath), true);
            $backups[] = [
                'timestamp' => $manifest['timestamp'],
                'reason' => $manifest['reason'],
                'file_count' => count($manifest['files']),
                'path' => $backupDir
            ];
        }

        return $backups;
    }

    /**
     * Delete old backups (keep last N)
     */
    public function pruneBackups(int $keepCount = 10): void
    {
        $backups = $this->listBackups();

        if (count($backups) <= $keepCount) {
            return;
        }

        $toDelete = array_slice($backups, $keepCount);

        foreach ($toDelete as $backup) {
            $this->filesystem->remove($backup['path']);
            $this->logger->info('Pruned old backup', ['path' => $backup['path']]);
        }
    }

    /**
     * Get relative path from project root
     */
    private function getRelativePath(string $absolutePath): string
    {
        $projectRoot = realpath(__DIR__ . '/../..');
        return str_replace($projectRoot . '/', '', $absolutePath);
    }
}
```

**Tests:** `tests/Service/BackupServiceTest.php`

---

## Phase 1 Deliverables Checklist

- [ ] CsvParserService implemented and tested
- [ ] CsvValidatorService implemented and tested
- [ ] EntityDefinitionDto implemented and tested
- [ ] PropertyDefinitionDto implemented and tested
- [ ] BackupService implemented and tested
- [ ] All unit tests pass (80%+ coverage)
- [ ] Code passes PHPStan level 8
- [ ] Documentation updated

---

## Next Phase

**Phase 2: Code Generators** (Weeks 2-3)
- Entity Generator
- API Platform Generator
- Repository Generator (with abstract layer)
- Controller Generator
- Voter Generator
- Form Generator
