<?php

declare(strict_types=1);

namespace App\Service\Generator\Csv;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class CsvParserService
{
    private const ENTITY_CSV_PATH = __DIR__ . '/../../../../config/EntityNew.csv';
    private const PROPERTY_CSV_PATH = __DIR__ . '/../../../../config/PropertyNew.csv';

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
        'cascade', 'orphanRemoval', 'fetch', 'orderBy', 'indexType',
        'compositeIndexWith', 'validationRules', 'validationMessage', 'formType',
        'formOptions', 'formRequired', 'formReadOnly', 'formHelp', 'showInList',
        'showInDetail', 'showInForm', 'sortable', 'searchable', 'filterable',
        'apiReadable', 'apiWritable', 'apiGroups', 'allowedRoles', 'translationKey',
        'formatPattern', 'fixtureType', 'fixtureOptions'
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

            // Validate column count - pad with empty strings if needed or skip row
            if (count($row) !== count($headers)) {
                if (count($row) < count($headers)) {
                    // Pad with empty strings
                    $row = array_pad($row, count($headers), '');
                } else {
                    // Too many columns - skip this row to avoid data corruption
                    error_log(sprintf(
                        'Property.csv line %d: Expected %d columns, got %d - SKIPPING ROW',
                        $lineNumber,
                        count($headers),
                        count($row)
                    ));
                    continue;
                }
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
        $property['unique'] = $this->parseBoolean($property['unique'] ?? '');
        $property['orphanRemoval'] = $this->parseBoolean($property['orphanRemoval'] ?? '');
        $property['formRequired'] = $this->parseBoolean($property['formRequired'] ?? 'true');
        $property['formReadOnly'] = $this->parseBoolean($property['formReadOnly'] ?? '');
        $property['showInList'] = $this->parseBoolean($property['showInList'] ?? 'true');
        $property['showInDetail'] = $this->parseBoolean($property['showInDetail'] ?? 'true');
        $property['showInForm'] = $this->parseBoolean($property['showInForm'] ?? 'true');
        $property['sortable'] = $this->parseBoolean($property['sortable'] ?? 'true');
        $property['searchable'] = $this->parseBoolean($property['searchable'] ?? '');
        $property['filterable'] = $this->parseBoolean($property['filterable'] ?? '');
        $property['apiReadable'] = $this->parseBoolean($property['apiReadable'] ?? 'true');
        $property['apiWritable'] = $this->parseBoolean($property['apiWritable'] ?? 'true');

        // Determine if indexed based on indexType (replaces old 'indexed' column)
        $indexType = trim($property['indexType'] ?? '');
        $property['indexed'] = !empty($indexType);

        // Integer conversions
        $property['length'] = $property['length'] !== '' ? (int)$property['length'] : null;
        $property['precision'] = $property['precision'] !== '' ? (int)$property['precision'] : null;
        $property['scale'] = $property['scale'] !== '' ? (int)$property['scale'] : null;

        // JSON field conversions
        $property['orderBy'] = $this->parseOrderBy($property['orderBy'] ?? '', $lineNumber);
        $property['formOptions'] = $this->parseJson($property['formOptions'] ?? '{}', $lineNumber, 'formOptions');
        $property['fixtureOptions'] = $this->parseJson($property['fixtureOptions'] ?? '{}', $lineNumber, 'fixtureOptions');

        // CSV list conversions
        $property['validationRules'] = $this->parseCsvList($property['validationRules'] ?? '');
        $property['cascade'] = $this->parseCsvList($property['cascade'] ?? '');
        $property['apiGroups'] = $this->parseCsvList($property['apiGroups'] ?? '');

        // Convert empty strings to null for optional fields
        $property['relationshipType'] = !empty($property['relationshipType']) ? $property['relationshipType'] : null;
        $property['targetEntity'] = !empty($property['targetEntity']) ? $property['targetEntity'] : null;
        $property['inversedBy'] = !empty($property['inversedBy']) ? $property['inversedBy'] : null;
        $property['mappedBy'] = !empty($property['mappedBy']) ? $property['mappedBy'] : null;
        $property['fetch'] = !empty($property['fetch']) ? $property['fetch'] : null;
        $property['indexType'] = !empty($property['indexType']) ? $property['indexType'] : null;

        // Parse compositeIndexWith - supports multiple columns separated by "|"
        if (!empty($property['compositeIndexWith'])) {
            $compositeColumns = array_map('trim', explode('|', $property['compositeIndexWith']));
            $property['compositeIndexWith'] = $compositeColumns;
        } else {
            $property['compositeIndexWith'] = null;
        }
        $property['allowedRoles'] = !empty($property['allowedRoles']) ? $property['allowedRoles'] : null;
        $property['defaultValue'] = !empty($property['defaultValue']) ? $property['defaultValue'] : null;
        $property['validationMessage'] = !empty($property['validationMessage']) ? $property['validationMessage'] : null;
        $property['formType'] = !empty($property['formType']) ? $property['formType'] : null;
        $property['formHelp'] = !empty($property['formHelp']) ? $property['formHelp'] : null;
        $property['translationKey'] = !empty($property['translationKey']) ? $property['translationKey'] : null;
        $property['formatPattern'] = !empty($property['formatPattern']) ? $property['formatPattern'] : null;
        $property['fixtureType'] = !empty($property['fixtureType']) ? $property['fixtureType'] : null;

        return $property;
    }

    /**
     * Parse boolean value from CSV
     * New pattern: empty string = false, "1" = true
     * Legacy support: "true", "yes", "y" = true
     */
    private function parseBoolean(string $value): bool
    {
        $trimmed = trim($value);

        // New pattern: empty = false, "1" = true
        if ($trimmed === '') {
            return false;
        }
        if ($trimmed === '1') {
            return true;
        }

        // Legacy support for backwards compatibility
        $normalized = strtolower($trimmed);
        return in_array($normalized, ['true', 'yes', 'y'], true);
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

        // Ensure we return array (json_decode with associative=true should return array, but check type)
        if (!is_array($decoded)) {
            return [$decoded];  // Wrap scalars in array
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

    /**
     * Parse orderBy field - supports both JSON and simple field name
     */
    private function parseOrderBy(string $value, int $lineNumber): array|string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        // If it starts with {, treat as JSON
        if (str_starts_with($trimmed, '{')) {
            return $this->parseJson($trimmed, $lineNumber, 'orderBy');
        }

        // Otherwise, convert simple field name to {"field": "ASC"}
        return [$trimmed => 'ASC'];
    }
}
