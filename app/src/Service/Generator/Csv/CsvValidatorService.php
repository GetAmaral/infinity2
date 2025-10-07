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
