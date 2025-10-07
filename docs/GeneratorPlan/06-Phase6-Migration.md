# Phase 6: CSV Migration (Weeks 7-8)

## Overview

Phase 6 migrates the existing CSV format to the new two-file structure and migrates existing entities to the generated code pattern.

**Duration:** Weeks 7-8 (10 working days)

**Deliverables:**
- ✅ CSV Migration Script
- ✅ Entity.csv with 66 entities
- ✅ Property.csv with 700+ properties
- ✅ Existing code migration plan
- ✅ Verification tests

---

## Week 7: CSV Structure Migration

### Day 1-3: CSV Migration Script

**File:** `scripts/migrate-csv.php`

**Purpose:** Convert existing `config/Entity.csv` (mixed format) to separate `Entity.csv` and `Property.csv`.

**Current Format Issues:**
- Single semicolon-separated file
- Entity and property metadata mixed
- 23 columns with inconsistent data types
- Boolean values as strings ("true"/"false")
- No clear separation of concerns

**Target Format:**
- **Entity.csv:** 25 columns, entity-level metadata
- **Property.csv:** 38 columns, property-level metadata
- Comma-separated (standard CSV)
- Proper boolean handling
- Clear foreign key relationships (entityId)

**Migration Script:**

```php
<?php

/**
 * CSV Migration Script
 * Converts old Entity.csv format to new Entity.csv + Property.csv structure
 *
 * Usage: php scripts/migrate-csv.php
 */

require __DIR__ . '/../vendor/autoload.php';

class CsvMigration
{
    private const OLD_CSV = __DIR__ . '/../config/Entity.csv';
    private const NEW_ENTITY_CSV = __DIR__ . '/../config/entities/Entity.csv';
    private const NEW_PROPERTY_CSV = __DIR__ . '/../config/entities/Property.csv';

    private array $entities = [];
    private array $properties = [];
    private int $entityIdCounter = 1;
    private int $propertyIdCounter = 1;

    public function run(): void
    {
        echo "Starting CSV migration...\n";

        // 1. Parse old CSV
        echo "Parsing old CSV format...\n";
        $this->parseOldCsv();

        // 2. Validate parsed data
        echo "Validating data...\n";
        $this->validate();

        // 3. Write new CSVs
        echo "Writing new CSV files...\n";
        $this->writeEntityCsv();
        $this->writePropertyCsv();

        echo "Migration completed successfully!\n";
        echo "  - {$this->entityIdCounter} entities\n";
        echo "  - {$this->propertyIdCounter} properties\n";
    }

    private function parseOldCsv(): void
    {
        $handle = fopen(self::OLD_CSV, 'r');
        $headers = fgetcsv($handle, 0, ';'); // Old format uses semicolons

        $currentEntity = null;
        $lineNumber = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $lineNumber++;

            if (empty($row[0])) {
                continue;
            }

            $data = array_combine($headers, $row);

            // Detect if this is entity or property line
            if ($this->isEntityLine($data)) {
                // Create new entity
                $entityId = $this->entityIdCounter++;
                $currentEntity = $entityId;

                $this->entities[$entityId] = [
                    'id' => $entityId,
                    'entityName' => $data['entityName'],
                    'labelSingular' => $this->generateTranslationKey($data['entityName'], 'singular'),
                    'labelPlural' => $this->generateTranslationKey($data['entityName'], 'plural'),
                    'icon' => $data['icon'] ?? 'bi-circle',
                    'description' => $data['description'] ?? '',
                    'tableName' => strtolower($data['entityName']),
                    'repositoryClass' => $data['entityName'] . 'Repository',
                    'indexes' => '',
                    'apiEnabled' => 'true',
                    'apiOperations' => 'get|getCollection|post|put|delete',
                    'apiSecurity' => $this->generateApiSecurity($data['entityName']),
                    'apiNormalizationGroups' => strtolower($data['entityName']) . ':read',
                    'apiDenormalizationGroups' => strtolower($data['entityName']) . ':write',
                    'apiFilters' => 'search|order',
                    'apiShortName' => $data['entityName'],
                    'apiDescription' => $data['description'] ?? '',
                    'listRoles' => 'USER',
                    'createRoles' => 'MANAGER',
                    'baseReadRoles' => 'USER',
                    'showInNavigation' => 'true',
                    'navGroup' => $this->detectNavGroup($data['entityName']),
                    'navOrder' => 10,
                    'defaultView' => 'list',
                    'generateTests' => 'true',
                ];
            } else {
                // This is a property line
                if ($currentEntity === null) {
                    throw new \RuntimeException("Property found before entity at line {$lineNumber}");
                }

                $this->properties[] = $this->convertProperty($data, $currentEntity);
            }
        }

        fclose($handle);
    }

    private function isEntityLine(array $data): bool
    {
        // Entity lines have entityName and no propertyName
        return !empty($data['entityName']) && empty($data['propertyName']);
    }

    private function convertProperty(array $data, int $entityId): array
    {
        $propertyId = $this->propertyIdCounter++;

        return [
            'id' => $propertyId,
            'entityId' => $entityId,
            'propertyName' => $data['propertyName'],
            'propertyType' => $data['type'] ?? 'string',
            'phpType' => $this->detectPhpType($data),
            'length' => $data['length'] ?? '',
            'precision' => $data['precision'] ?? '',
            'scale' => $data['scale'] ?? '',
            'nullable' => $data['nullable'] ?? 'true',
            'default' => $data['default'] ?? '',
            'unique' => $data['unique'] ?? 'false',
            'uniqueGroup' => '',
            'targetEntity' => $data['targetEntity'] ?? '',
            'inversedBy' => $data['inversedBy'] ?? '',
            'mappedBy' => $data['mappedBy'] ?? '',
            'cascade' => $this->detectCascade($data),
            'orphanRemoval' => 'false',
            'fetch' => 'LAZY',
            'orderBy' => '',
            'columnName' => '',
            'indexType' => '',
            'indexName' => '',
            'assert' => $this->detectValidation($data),
            'assertMin' => '',
            'assertMax' => '',
            'assertOptions' => '',
            'serializationGroups' => $this->detectSerializationGroups($entityId, $data),
            'jsonIgnore' => 'false',
            'formType' => $this->detectFormType($data),
            'formLabel' => $this->generateFieldTranslationKey($entityId, $data['propertyName']),
            'formHelp' => '',
            'formOptions' => '',
            'formOrder' => 10,
            'showInForm' => 'true',
            'showInList' => 'true',
            'showInDetail' => 'true',
            'showInFilter' => 'false',
            'searchable' => $this->isSearchable($data),
            'sortable' => 'true',
        ];
    }

    private function generateTranslationKey(string $entityName, string $type): string
    {
        $key = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $entityName));
        return "{$key}.{$type}";
    }

    private function generateApiSecurity(string $entityName): string
    {
        $upper = strtoupper(preg_replace('/([a-z])([A-Z])/', '$1_$2', $entityName));
        return "{$upper}_VIEW|{$upper}_LIST|{$upper}_CREATE|{$upper}_EDIT|{$upper}_DELETE";
    }

    private function detectNavGroup(string $entityName): string
    {
        $crmEntities = ['Contact', 'Company', 'Deal', 'Campaign', 'Lead'];
        $adminEntities = ['User', 'Role', 'Organization'];

        if (in_array($entityName, $crmEntities)) {
            return 'crm.01';
        } elseif (in_array($entityName, $adminEntities)) {
            return 'admin.99';
        }

        return 'other.50';
    }

    private function detectPhpType(array $data): string
    {
        $type = $data['type'] ?? 'string';

        return match($type) {
            'string', 'text' => 'string',
            'integer', 'int' => 'int',
            'boolean', 'bool' => 'bool',
            'float', 'decimal' => 'float',
            'ManyToOne', 'OneToOne' => $data['targetEntity'] ?? 'mixed',
            'OneToMany', 'ManyToMany' => 'Collection',
            default => 'string'
        };
    }

    private function detectCascade(array $data): string
    {
        if (in_array($data['type'] ?? '', ['OneToMany', 'ManyToMany'])) {
            return 'persist|remove';
        }
        return '';
    }

    private function detectValidation(array $data): string
    {
        $rules = [];

        if (($data['nullable'] ?? 'true') === 'false') {
            $rules[] = 'NotBlank';
        }

        if (str_contains($data['propertyName'] ?? '', 'email')) {
            $rules[] = 'Email';
        }

        return implode('|', $rules);
    }

    private function detectSerializationGroups(int $entityId, array $data): string
    {
        $entityName = strtolower($this->entities[$entityId]['entityName']);
        return "{$entityName}:read|{$entityName}:write";
    }

    private function detectFormType(array $data): string
    {
        $type = $data['type'] ?? 'string';

        return match($type) {
            'text' => 'textarea',
            'boolean' => 'checkbox',
            'integer' => 'integer',
            'date' => 'date',
            'datetime' => 'datetime',
            'ManyToOne', 'OneToOne', 'ManyToMany' => 'entity',
            default => 'text'
        };
    }

    private function isSearchable(array $data): string
    {
        $searchableFields = ['name', 'title', 'description', 'email', 'phone'];
        $propertyName = $data['propertyName'] ?? '';

        return in_array($propertyName, $searchableFields) ? 'true' : 'false';
    }

    private function generateFieldTranslationKey(int $entityId, string $propertyName): string
    {
        $entityKey = $this->generateTranslationKey($this->entities[$entityId]['entityName'], 'field');
        return str_replace('.field', ".field.{$propertyName}", $entityKey);
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
            if (!isset($this->entities[$property['entityId']])) {
                throw new \RuntimeException("Property references non-existent entity: {$property['entityId']}");
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

// Execute migration
$migration = new CsvMigration();
$migration->run();
```

**Usage:**
```bash
cd /home/user/inf
php scripts/migrate-csv.php
```

---

## Day 4-5: Verification

### Verification Script: `scripts/verify-csv-migration.php`

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\CsvValidatorService;

$parser = new CsvParserService(__DIR__ . '/..');
$validator = new CsvValidatorService();

echo "Verifying migrated CSV files...\n\n";

// Parse
$result = $parser->parseAll();
echo "✓ Parsed {count($result['entities'])} entities\n";

// Validate
$validation = $validator->validateAll($result['entities'], $result['properties']);

if ($validation['valid']) {
    echo "✓ Validation passed\n";
    echo "\nMigration successful!\n";
    exit(0);
} else {
    echo "✗ Validation failed\n\n";
    echo "Errors:\n";
    foreach ($validation['errors'] as $error) {
        echo "  - {$error}\n";
    }
    exit(1);
}
```

---

## Week 8: Existing Code Migration

### Day 1-5: Manual Migration of Existing Entities

**Process:**
1. **Backup Current Code**
   ```bash
   cp -r src/Entity src/Entity.backup
   cp -r src/Repository src/Repository.backup
   ```

2. **Generate New Code**
   ```bash
   php bin/console app:generate-from-csv --entity=Contact
   ```

3. **Migrate Custom Logic**
   - Copy custom methods from `Contact.php.backup` to `Contact.php` (extension class)
   - Verify relationships still work
   - Update any hard-coded references

4. **Test**
   ```bash
   php bin/phpunit tests/Entity/ContactTest.php
   ```

5. **Repeat for All 66 Entities**

**Migration Checklist per Entity:**
- [ ] Generate code
- [ ] Migrate custom methods
- [ ] Verify relationships
- [ ] Run entity tests
- [ ] Run controller tests
- [ ] Manual smoke test

---

## Phase 6 Deliverables Checklist

- [ ] CSV migration script implemented
- [ ] Entity.csv created with 66 entities
- [ ] Property.csv created with 700+ properties
- [ ] CSV validation passes
- [ ] Verification script passes
- [ ] Existing entities migrated
- [ ] All tests pass after migration
- [ ] Documentation updated

---

## Next Phase

**Phase 7: Bulk Generation** (Weeks 9-10)
- Generate all 50+ new entities
- System testing
- Performance validation
