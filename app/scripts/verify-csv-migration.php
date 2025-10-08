#!/usr/bin/env php
<?php

/**
 * CSV Migration Verification Script
 * Validates migrated Entity.csv and Property.csv files
 *
 * Usage: php scripts/verify-csv-migration.php [--verbose]
 *
 * This script verifies that the migrated CSV files:
 * - Have correct structure
 * - Pass all validation rules
 * - Are ready for code generation
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\CsvValidatorService;
use Psr\Log\NullLogger;

class CsvVerification
{
    private bool $verbose = false;
    private int $errorCount = 0;
    private int $warningCount = 0;

    public function __construct(array $options = [])
    {
        $this->verbose = isset($options['verbose']);
    }

    public function run(): int
    {
        try {
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "  CSV Migration Verification\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            $projectDir = dirname(__DIR__);

            // Initialize services
            $parser = new CsvParserService($projectDir);
            $validator = new CsvValidatorService(new NullLogger());

            // 1. Check files exist
            echo "📁 Checking CSV files...\n";
            $this->checkFilesExist($projectDir);
            echo "   ✓ Both files exist\n\n";

            // 2. Parse CSV files
            echo "📖 Parsing CSV files...\n";
            $result = $parser->parseAll();
            $entityCount = count($result['entities']);

            // Flatten grouped properties
            $flatProperties = [];
            foreach ($result['properties'] as $entityProps) {
                $flatProperties = array_merge($flatProperties, $entityProps);
            }
            $propertyCount = count($flatProperties);

            echo "   ✓ Parsed {$entityCount} entities\n";
            echo "   ✓ Parsed {$propertyCount} properties\n\n";

            // 3. Validate structure and data
            echo "🔍 Validating CSV data...\n";
            // Validator expects properties grouped by entity name
            $validation = $validator->validateAll($result['entities'], $result['properties']);

            if ($validation['valid']) {
                echo "   ✓ Validation passed\n\n";
            } else {
                echo "   ✗ Validation failed\n\n";
                $this->errorCount += count($validation['errors']);
                $this->displayErrors($validation['errors']);
            }

            // 4. Additional checks
            echo "🔬 Running additional checks...\n";
            $this->runAdditionalChecks($result['entities'], $flatProperties);

            // 5. Summary
            echo "\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "  Verification Summary\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            echo "📊 Statistics:\n";
            echo "   • Entities:   {$entityCount}\n";
            echo "   • Properties: {$propertyCount}\n";
            echo "   • Avg Props:  " . round($propertyCount / max($entityCount, 1), 1) . " per entity\n";
            echo "\n";

            echo "🎯 Results:\n";
            if ($this->errorCount === 0 && $this->warningCount === 0) {
                echo "   ✅ All checks passed - CSV files are ready!\n";
            } else {
                echo "   • Errors:   {$this->errorCount}\n";
                echo "   • Warnings: {$this->warningCount}\n";

                if ($this->errorCount > 0) {
                    echo "\n   ❌ Fix errors before generating code\n";
                } else {
                    echo "\n   ⚠️  Review warnings (not blocking)\n";
                }
            }

            echo "\n";

            if ($this->errorCount === 0) {
                echo "Next steps:\n";
                echo "  • Test generation:  php bin/console app:generate-from-csv --dry-run\n";
                echo "  • Generate entity:  php bin/console app:generate-from-csv --entity=Contact\n";
                echo "\n";
            }

            return $this->errorCount > 0 ? 1 : 0;

        } catch (\Throwable $e) {
            echo "\n❌ Verification failed: {$e->getMessage()}\n";
            if ($this->verbose) {
                echo "\nStack trace:\n";
                echo $e->getTraceAsString() . "\n";
            }
            return 1;
        }
    }

    private function checkFilesExist(string $projectDir): void
    {
        $entityCsv = $projectDir . '/config/EntityNew.csv';
        $propertyCsv = $projectDir . '/config/PropertyNew.csv';

        if (!file_exists($entityCsv)) {
            throw new \RuntimeException("Entity CSV not found: {$entityCsv}");
        }

        if (!file_exists($propertyCsv)) {
            throw new \RuntimeException("Property CSV not found: {$propertyCsv}");
        }

        // Check files are readable
        if (!is_readable($entityCsv)) {
            throw new \RuntimeException("Entity CSV is not readable: {$entityCsv}");
        }

        if (!is_readable($propertyCsv)) {
            throw new \RuntimeException("Property CSV is not readable: {$propertyCsv}");
        }
    }

    private function runAdditionalChecks(array $entities, array $properties): void
    {

        // Check for entities without properties
        $entitiesWithoutProps = [];
        foreach ($entities as $entity) {
            $hasProperties = false;
            foreach ($properties as $property) {
                if ($property['entityName'] === $entity['entityName']) {
                    $hasProperties = true;
                    break;
                }
            }
            if (!$hasProperties) {
                $entitiesWithoutProps[] = $entity['entityName'];
            }
        }

        if (!empty($entitiesWithoutProps)) {
            $this->warning("Entities without properties: " . implode(', ', $entitiesWithoutProps));
        } else {
            echo "   ✓ All entities have properties\n";
        }

        // Check for duplicate entity names
        $entityNames = array_column($entities, 'entityName');
        $duplicateEntities = array_diff_assoc($entityNames, array_unique($entityNames));
        if (!empty($duplicateEntities)) {
            $this->error("Duplicate entity names found: " . implode(', ', array_unique($duplicateEntities)));
        } else {
            echo "   ✓ No duplicate entity names\n";
        }

        // Check for missing icons
        $entitiesWithoutIcons = [];
        foreach ($entities as $entity) {
            if (empty($entity['icon']) || $entity['icon'] === 'bi-circle') {
                $entitiesWithoutIcons[] = $entity['entityName'];
            }
        }
        if (!empty($entitiesWithoutIcons) && $this->verbose) {
            $this->warning("Entities with default icon: " . implode(', ', $entitiesWithoutIcons));
        }

        // Check for properties with validation rules
        $propsWithValidation = 0;
        foreach ($properties as $property) {
            if (!empty($property['validationRules'])) {
                $propsWithValidation++;
            }
        }
        echo "   ✓ {$propsWithValidation}/" . count($properties) . " properties have validation rules\n";

        // Check for API-enabled entities
        $apiEnabledCount = 0;
        foreach ($entities as $entity) {
            if (($entity['apiEnabled'] ?? 'false') === 'true') {
                $apiEnabledCount++;
            }
        }
        echo "   ✓ {$apiEnabledCount}/" . count($entities) . " entities are API-enabled\n";

        // Check for voter-enabled entities
        $voterEnabledCount = 0;
        foreach ($entities as $entity) {
            if (($entity['voterEnabled'] ?? 'false') === 'true') {
                $voterEnabledCount++;
            }
        }
        echo "   ✓ {$voterEnabledCount}/" . count($entities) . " entities have voters\n";

        // Check for test-enabled entities
        $testEnabledCount = 0;
        foreach ($entities as $entity) {
            if (($entity['testEnabled'] ?? 'false') === 'true') {
                $testEnabledCount++;
            }
        }
        echo "   ✓ {$testEnabledCount}/" . count($entities) . " entities have tests\n";

        // Check for multi-tenant entities
        $orgEnabledCount = 0;
        foreach ($entities as $entity) {
            if (($entity['hasOrganization'] ?? 'false') === 'true') {
                $orgEnabledCount++;
            }
        }
        echo "   ✓ {$orgEnabledCount}/" . count($entities) . " entities are multi-tenant\n";

        // Check for searchable properties
        $searchableCount = 0;
        foreach ($properties as $property) {
            if (($property['searchable'] ?? 'false') === 'true') {
                $searchableCount++;
            }
        }
        echo "   ✓ {$searchableCount}/" . count($properties) . " properties are searchable\n";

        // Check for relationship properties
        $relationshipCount = 0;
        foreach ($properties as $property) {
            if (!empty($property['relationshipType'])) {
                $relationshipCount++;
            }
        }
        echo "   ✓ {$relationshipCount}/" . count($properties) . " properties are relationships\n";
    }

    private function displayErrors(array $errors): void
    {
        echo "   Errors:\n";
        foreach ($errors as $error) {
            echo "   • {$error}\n";
        }
        echo "\n";
    }

    private function error(string $message): void
    {
        $this->errorCount++;
        echo "   ✗ ERROR: {$message}\n";
    }

    private function warning(string $message): void
    {
        $this->warningCount++;
        echo "   ⚠️  WARNING: {$message}\n";
    }
}

// Parse command line arguments
$options = [];
for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if ($arg === '--verbose' || $arg === '-v') {
        $options['verbose'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "CSV Migration Verification Script\n";
        echo "==================================\n\n";
        echo "Usage: php scripts/verify-csv-migration.php [options]\n\n";
        echo "Options:\n";
        echo "  --verbose, -v  Show detailed output\n";
        echo "  --help, -h     Show this help message\n\n";
        echo "Examples:\n";
        echo "  php scripts/verify-csv-migration.php\n";
        echo "  php scripts/verify-csv-migration.php --verbose\n";
        echo "\n";
        exit(0);
    }
}

// Execute verification
$verification = new CsvVerification($options);
exit($verification->run());
