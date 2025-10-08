#!/usr/bin/env php
<?php

/**
 * CSV Migration Script
 * Converts old Entity.csv format to new Entity.csv + Property.csv structure
 *
 * Usage: php scripts/migrate-csv.php [--input=path/to/old.csv] [--dry-run]
 *
 * This script migrates from the old single-file semicolon-separated CSV format
 * to the new two-file comma-separated format used by the TURBO generator.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

class CsvMigration
{
    private const DEFAULT_OLD_CSV = __DIR__ . '/../config/Entity.csv.old';
    private const NEW_ENTITY_CSV = __DIR__ . '/../config/EntityNew.csv';
    private const NEW_PROPERTY_CSV = __DIR__ . '/../config/PropertyNew.csv';

    private array $entities = [];
    private array $properties = [];
    private int $entityIdCounter = 1;
    private int $propertyIdCounter = 1;
    private bool $dryRun = false;
    private string $inputFile;

    public function __construct(array $options = [])
    {
        $this->inputFile = $options['input'] ?? self::DEFAULT_OLD_CSV;
        $this->dryRun = isset($options['dry-run']);
    }

    public function run(): int
    {
        try {
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "  CSV Migration - Old Format → New Format\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            if ($this->dryRun) {
                echo "⚠️  DRY RUN MODE - No files will be written\n\n";
            }

            // 1. Check input file exists
            if (!file_exists($this->inputFile)) {
                throw new \RuntimeException(
                    "Input file not found: {$this->inputFile}\n" .
                    "Please specify the old CSV file with --input=path/to/file.csv"
                );
            }

            // 2. Parse old CSV
            echo "📖 Parsing old CSV format...\n";
            echo "   Input: {$this->inputFile}\n";
            $this->parseOldCsv();
            echo "   ✓ Parsed {$this->entityIdCounter} entities and " . count($this->properties) . " properties\n\n";

            // 3. Validate parsed data
            echo "🔍 Validating data...\n";
            $this->validate();
            echo "   ✓ Validation passed\n\n";

            // 4. Write new CSVs
            if ($this->dryRun) {
                echo "📝 Would write (dry-run):\n";
                echo "   - " . self::NEW_ENTITY_CSV . " ({$this->entityIdCounter} entities)\n";
                echo "   - " . self::NEW_PROPERTY_CSV . " (" . count($this->properties) . " properties)\n\n";
            } else {
                echo "✍️  Writing new CSV files...\n";
                $this->writeEntityCsv();
                echo "   ✓ Wrote " . self::NEW_ENTITY_CSV . "\n";
                $this->writePropertyCsv();
                echo "   ✓ Wrote " . self::NEW_PROPERTY_CSV . "\n\n";
            }

            echo "✅ Migration completed successfully!\n";
            echo "\n";
            echo "📊 Summary:\n";
            echo "   • Entities:    {$this->entityIdCounter}\n";
            echo "   • Properties:  " . count($this->properties) . "\n";
            echo "   • Output:      " . ($this->dryRun ? 'DRY RUN' : 'WRITTEN') . "\n";
            echo "\n";

            if (!$this->dryRun) {
                echo "Next steps:\n";
                echo "  1. Verify migration: php scripts/verify-csv-migration.php\n";
                echo "  2. Test generation:  php bin/console app:generate-from-csv --dry-run\n";
                echo "\n";
            }

            return 0;

        } catch (\Throwable $e) {
            echo "\n❌ Migration failed: {$e->getMessage()}\n";
            echo "\nStack trace:\n";
            echo $e->getTraceAsString() . "\n";
            return 1;
        }
    }

    private function parseOldCsv(): void
    {
        $handle = fopen($this->inputFile, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Failed to open file: {$this->inputFile}");
        }

        // Read headers (old format uses semicolons)
        $headers = fgetcsv($handle, 0, ';');

        if ($headers === false) {
            throw new \RuntimeException("Failed to read CSV headers");
        }

        $currentEntity = null;
        $lineNumber = 1;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $lineNumber++;

            // Skip empty lines
            if (empty($row[0]) && empty($row[1])) {
                continue;
            }

            // Combine headers with row data
            $data = array_combine($headers, array_pad($row, count($headers), ''));

            if ($data === false) {
                throw new \RuntimeException("Failed to parse line {$lineNumber}");
            }

            // Detect if this is entity or property line
            if ($this->isEntityLine($data)) {
                // Create new entity
                $entityId = $this->entityIdCounter++;
                $currentEntity = $entityId;

                $this->entities[$entityId] = $this->convertEntity($data, $entityId);
            } else {
                // This is a property line
                if ($currentEntity === null) {
                    throw new \RuntimeException(
                        "Property found before entity at line {$lineNumber}. " .
                        "Each CSV section must start with an entity row."
                    );
                }

                $this->properties[] = $this->convertProperty($data, $currentEntity);
            }
        }

        fclose($handle);
    }

    private function isEntityLine(array $data): bool
    {
        // Entity lines have entityName and either no propertyName or empty propertyName
        return !empty($data['entityName']) && empty($data['propertyName']);
    }

    private function convertEntity(array $data, int $entityId): array
    {
        $entityName = $data['entityName'];

        return [
            'entityName' => $entityName,
            'entityLabel' => $entityName,
            'pluralLabel' => $this->pluralize($entityName),
            'icon' => $data['icon'] ?? 'bi-circle',
            'description' => $data['description'] ?? '',
            'hasOrganization' => $this->normalizeBoolean($data['hasOrganization'] ?? 'true'),
            'apiEnabled' => 'true',
            'operations' => 'GetCollection,Get,Post,Put,Delete',
            'security' => "is_granted('ROLE_USER')",
            'normalizationContext' => strtolower($entityName) . ':read,audit:read',
            'denormalizationContext' => strtolower($entityName) . ':write',
            'paginationEnabled' => 'true',
            'itemsPerPage' => '30',
            'order' => '{"id": "DESC"}',
            'searchableFields' => $this->detectSearchableFields($data),
            'filterableFields' => '',
            'voterEnabled' => 'true',
            'voterAttributes' => 'VIEW,EDIT,DELETE',
            'formTheme' => 'bootstrap_5_layout.html.twig',
            'indexTemplate' => '',
            'formTemplate' => '',
            'showTemplate' => '',
            'menuGroup' => $this->detectNavGroup($entityName),
            'menuOrder' => '10',
            'testEnabled' => 'true',
        ];
    }

    private function convertProperty(array $data, int $entityId): array
    {
        $propertyIdCounter = $this->propertyIdCounter++;
        $entityName = $this->entities[$entityId]['entityName'];
        $propertyName = $data['propertyName'];
        $propertyType = $data['type'] ?? $data['propertyType'] ?? 'string';

        return [
            'entityName' => $entityName,
            'propertyName' => $propertyName,
            'propertyLabel' => ucfirst($propertyName),
            'propertyType' => $this->normalizeType($propertyType),
            'nullable' => $this->normalizeBoolean($data['nullable'] ?? 'true'),
            'length' => $data['length'] ?? ($propertyType === 'string' ? '255' : ''),
            'precision' => $data['precision'] ?? '',
            'scale' => $data['scale'] ?? '',
            'unique' => $this->normalizeBoolean($data['unique'] ?? 'false'),
            'defaultValue' => $data['default'] ?? $data['defaultValue'] ?? '',
            'relationshipType' => $this->detectRelationshipType($propertyType),
            'targetEntity' => $data['targetEntity'] ?? '',
            'inversedBy' => $data['inversedBy'] ?? '',
            'mappedBy' => $data['mappedBy'] ?? '',
            'cascade' => $this->detectCascade($propertyType),
            'orphanRemoval' => 'false',
            'fetch' => 'LAZY',
            'orderBy' => '',
            'validationRules' => $this->detectValidation($propertyName, $data),
            'validationMessage' => "Please enter a valid {$propertyName}",
            'formType' => $this->detectFormType($propertyType),
            'formOptions' => '{}',
            'formRequired' => $this->normalizeBoolean($data['nullable'] ?? 'true') === 'false' ? 'true' : 'false',
            'formReadOnly' => 'false',
            'formHelp' => "Enter the {$entityName}'s {$propertyName}",
            'showInList' => 'true',
            'showInDetail' => 'true',
            'showInForm' => 'true',
            'sortable' => 'true',
            'searchable' => $this->isSearchableField($propertyName) ? 'true' : 'false',
            'filterable' => 'false',
            'apiReadable' => 'true',
            'apiWritable' => 'true',
            'apiGroups' => strtolower($entityName) . ':read,' . strtolower($entityName) . ':write',
            'translationKey' => strtolower($entityName) . '.' . $propertyName,
            'formatPattern' => '',
            'fixtureType' => $this->detectFixtureType($propertyName, $propertyType),
            'fixtureOptions' => '{}',
        ];
    }

    private function pluralize(string $word): string
    {
        // Simple pluralization rules
        if (str_ends_with($word, 'y')) {
            return substr($word, 0, -1) . 'ies';
        }
        if (str_ends_with($word, 's') || str_ends_with($word, 'x')) {
            return $word . 'es';
        }
        return $word . 's';
    }

    private function normalizeBoolean(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['true', '1', 'yes']) ? 'true' : 'false';
    }

    private function normalizeType(string $type): string
    {
        $typeMap = [
            'int' => 'integer',
            'bool' => 'boolean',
            'text' => 'text',
            'datetime' => 'datetime',
            'date' => 'date',
            'decimal' => 'decimal',
            'float' => 'float',
        ];

        return $typeMap[strtolower($type)] ?? 'string';
    }

    private function detectRelationshipType(string $type): string
    {
        $relationTypes = ['ManyToOne', 'OneToMany', 'ManyToMany', 'OneToOne'];
        return in_array($type, $relationTypes) ? $type : '';
    }

    private function detectCascade(string $type): string
    {
        return in_array($type, ['OneToMany', 'ManyToMany']) ? 'persist,remove' : '';
    }

    private function detectValidation(string $propertyName, array $data): string
    {
        $rules = [];

        // If not nullable, add NotBlank
        if (($this->normalizeBoolean($data['nullable'] ?? 'true')) === 'false') {
            $rules[] = 'NotBlank';
        }

        // Email validation
        if (str_contains(strtolower($propertyName), 'email')) {
            $rules[] = 'Email';
        }

        // Length validation for strings
        if (($data['type'] ?? 'string') === 'string' && !empty($data['length'])) {
            $rules[] = 'Length';
        }

        return implode(',', $rules);
    }

    private function detectFormType(string $type): string
    {
        return match($type) {
            'text' => 'TextareaType',
            'boolean' => 'CheckboxType',
            'integer' => 'IntegerType',
            'date' => 'DateType',
            'datetime' => 'DateTimeType',
            'ManyToOne', 'OneToOne' => 'EntityType',
            'ManyToMany' => 'EntityType',
            default => 'TextType'
        };
    }

    private function detectSearchableFields(array $data): string
    {
        $commonSearchFields = ['name', 'title', 'email', 'description'];
        // This is basic - in real migration you'd parse properties
        return implode(',', $commonSearchFields);
    }

    private function isSearchableField(string $propertyName): bool
    {
        $searchableFields = ['name', 'title', 'description', 'email', 'phone'];
        return in_array(strtolower($propertyName), $searchableFields);
    }

    private function detectFixtureType(string $propertyName, string $type): string
    {
        $propertyLower = strtolower($propertyName);

        if (str_contains($propertyLower, 'email')) return 'email';
        if (str_contains($propertyLower, 'phone')) return 'phoneNumber';
        if (str_contains($propertyLower, 'name')) return 'name';
        if (str_contains($propertyLower, 'address')) return 'address';
        if (str_contains($propertyLower, 'city')) return 'city';
        if (str_contains($propertyLower, 'country')) return 'country';
        if (str_contains($propertyLower, 'date')) return 'dateTime';

        return match($type) {
            'boolean' => 'boolean',
            'integer' => 'numberBetween',
            'datetime' => 'dateTime',
            default => 'word'
        };
    }

    private function detectNavGroup(string $entityName): string
    {
        $crmEntities = ['Contact', 'Company', 'Deal', 'Campaign', 'Lead', 'Opportunity'];
        $adminEntities = ['User', 'Role', 'Organization', 'Setting'];
        $contentEntities = ['Course', 'Module', 'Lecture', 'Content'];

        if (in_array($entityName, $crmEntities)) {
            return 'CRM';
        } elseif (in_array($entityName, $adminEntities)) {
            return 'Admin';
        } elseif (in_array($entityName, $contentEntities)) {
            return 'Content';
        }

        return 'Other';
    }

    private function validate(): void
    {
        // Validate entities
        foreach ($this->entities as $entity) {
            if (empty($entity['entityName'])) {
                throw new \RuntimeException("Entity without name found");
            }
        }

        // Validate properties
        foreach ($this->properties as $property) {
            if (empty($property['entityName'])) {
                throw new \RuntimeException("Property without entity name found");
            }
            if (empty($property['propertyName'])) {
                throw new \RuntimeException("Property without name found for entity {$property['entityName']}");
            }

            // Check entity exists
            $entityExists = false;
            foreach ($this->entities as $entity) {
                if ($entity['entityName'] === $property['entityName']) {
                    $entityExists = true;
                    break;
                }
            }
            if (!$entityExists) {
                throw new \RuntimeException(
                    "Property '{$property['propertyName']}' references non-existent entity: {$property['entityName']}"
                );
            }
        }
    }

    private function writeEntityCsv(): void
    {
        $dir = dirname(self::NEW_ENTITY_CSV);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen(self::NEW_ENTITY_CSV, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Failed to create file: " . self::NEW_ENTITY_CSV);
        }

        // Write headers
        $headers = array_keys($this->entities[array_key_first($this->entities)]);
        fputcsv($handle, $headers);

        // Write data
        foreach ($this->entities as $entity) {
            fputcsv($handle, $entity);
        }

        fclose($handle);
    }

    private function writePropertyCsv(): void
    {
        $handle = fopen(self::NEW_PROPERTY_CSV, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Failed to create file: " . self::NEW_PROPERTY_CSV);
        }

        // Write headers
        $headers = array_keys($this->properties[0]);
        fputcsv($handle, $headers);

        // Write data
        foreach ($this->properties as $property) {
            fputcsv($handle, $property);
        }

        fclose($handle);
    }
}

// Parse command line arguments
$options = [];
for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if (str_starts_with($arg, '--input=')) {
        $options['input'] = substr($arg, 8);
    } elseif ($arg === '--dry-run') {
        $options['dry-run'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "CSV Migration Script\n";
        echo "====================\n\n";
        echo "Usage: php scripts/migrate-csv.php [options]\n\n";
        echo "Options:\n";
        echo "  --input=PATH   Path to old CSV file (default: config/Entity.csv.old)\n";
        echo "  --dry-run      Preview changes without writing files\n";
        echo "  --help, -h     Show this help message\n\n";
        echo "Examples:\n";
        echo "  php scripts/migrate-csv.php --dry-run\n";
        echo "  php scripts/migrate-csv.php --input=backup/old.csv\n";
        echo "\n";
        exit(0);
    }
}

// Execute migration
$migration = new CsvMigration($options);
exit($migration->run());
