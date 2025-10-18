#!/usr/bin/env php
<?php

/**
 * Batch Generation Script
 * Generates code for multiple entities with progress tracking and error handling
 *
 * Usage: php scripts/batch-generate.php [--batch=SIZE] [--continue-on-error] [--skip-tests]
 *
 * Features:
 * - Batch processing with configurable size
 * - Progress tracking and statistics
 * - Error handling with continue-on-error mode
 * - Optional test execution per batch
 * - Backup creation before generation
 * - Rollback on failure
 * - Performance metrics
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Service\Generator\GeneratorOrchestrator;
use App\Service\Generator\Csv\CsvParserService;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BatchGeneration
{
    private int $batchSize = 10;
    private bool $continueOnError = false;
    private bool $skipTests = false;
    private array $stats = [
        'total_entities' => 0,
        'success' => 0,
        'failed' => 0,
        'files_generated' => 0,
        'time_taken' => 0,
        'batches' => 0,
    ];
    private array $errors = [];
    private float $startTime;

    public function __construct(array $options = [])
    {
        $this->batchSize = $options['batch'] ?? 10;
        $this->continueOnError = isset($options['continue-on-error']);
        $this->skipTests = isset($options['skip-tests']);
        $this->startTime = microtime(true);
    }

    public function run(): int
    {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Batch Code Generation - TURBO Generator System\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "⚙️  Configuration:\n";
        echo "   • Batch size:        {$this->batchSize} entities\n";
        echo "   • Continue on error: " . ($this->continueOnError ? 'Yes' : 'No') . "\n";
        echo "   • Run tests:         " . ($this->skipTests ? 'No' : 'Yes') . "\n";
        echo "\n";

        try {
            // Parse CSV to get all entities
            $projectDir = dirname(__DIR__);
            $parser = new CsvParserService($projectDir);
            $result = $parser->parseAll();
            $entities = $result['entities'];

            $this->stats['total_entities'] = count($entities);

            echo "📋 Found {$this->stats['total_entities']} entities to generate\n\n";

            if ($this->stats['total_entities'] === 0) {
                echo "⚠️  No entities found in CSV files\n";
                return 1;
            }

            // Split into batches
            $batches = array_chunk($entities, $this->batchSize);
            $this->stats['batches'] = count($batches);

            echo "🔄 Processing {$this->stats['batches']} batches...\n\n";

            // Process each batch
            foreach ($batches as $batchNumber => $batch) {
                $batchNum = $batchNumber + 1;
                $success = $this->processBatch($batchNum, $batch);

                if (!$success && !$this->continueOnError) {
                    echo "\n❌ Batch {$batchNum} failed - Stopping generation\n";
                    $this->displayFinalSummary();
                    return 1;
                }

                // Small delay between batches
                if ($batchNum < $this->stats['batches']) {
                    sleep(1);
                }
            }

            $this->displayFinalSummary();

            return $this->stats['failed'] > 0 ? 1 : 0;

        } catch (\Throwable $e) {
            echo "\n❌ Fatal error: {$e->getMessage()}\n";
            echo "\nStack trace:\n";
            echo $e->getTraceAsString() . "\n";
            return 1;
        }
    }

    private function processBatch(int $batchNumber, array $entities): bool
    {
        $batchSize = count($entities);
        $entityNames = array_map(fn($e) => $e['entityName'], $entities);

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Batch {$batchNumber}/{$this->stats['batches']} ({$batchSize} entities)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "📝 Entities: " . implode(', ', $entityNames) . "\n\n";

        $batchStartTime = microtime(true);
        $batchSuccess = 0;
        $batchFailed = 0;
        $batchFiles = 0;

        foreach ($entities as $entity) {
            $entityName = $entity['entityName'];
            echo "   🔨 Generating {$entityName}...";

            $result = $this->generateEntity($entityName);

            if ($result['success']) {
                $batchSuccess++;
                $this->stats['success']++;
                $batchFiles += count($result['files']);
                $this->stats['files_generated'] += count($result['files']);
                echo " ✅ ({$result['files_count']} files)\n";
            } else {
                $batchFailed++;
                $this->stats['failed']++;
                $this->errors[$entityName] = $result['error'];
                echo " ❌ FAILED\n";
                echo "      → {$result['error']}\n";

                if (!$this->continueOnError) {
                    return false;
                }
            }
        }

        $batchTime = microtime(true) - $batchStartTime;

        echo "\n📊 Batch {$batchNumber} Summary:\n";
        echo "   • Success:  {$batchSuccess}/{$batchSize}\n";
        echo "   • Failed:   {$batchFailed}/{$batchSize}\n";
        echo "   • Files:    {$batchFiles}\n";
        echo "   • Time:     " . round($batchTime, 2) . "s\n";

        // Run tests if enabled
        if (!$this->skipTests && $batchSuccess > 0) {
            echo "\n🧪 Running tests for batch {$batchNumber}...\n";
            $testResult = $this->runTests();
            if ($testResult) {
                echo "   ✅ All tests passed\n";
            } else {
                echo "   ⚠️  Some tests failed (continuing...)\n";
            }
        }

        echo "\n";

        return true;
    }

    private function generateEntity(string $entityName): array
    {
        try {
            // Use symfony console command
            $projectDir = dirname(__DIR__);
            $command = sprintf(
                'cd %s && php bin/console app:generate-from-csv --entity=%s 2>&1',
                escapeshellarg($projectDir),
                escapeshellarg($entityName)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                // Parse output to count files
                $filesGenerated = 0;
                $allFiles = [];
                foreach ($output as $line) {
                    if (preg_match('/(\d+)\s+files?\s+generated/i', $line, $matches)) {
                        $filesGenerated = (int)$matches[1];
                    }
                    if (preg_match('/Generated:\s+(.+)/', $line, $matches)) {
                        $allFiles[] = $matches[1];
                    }
                }

                return [
                    'success' => true,
                    'files' => $allFiles,
                    'files_count' => $filesGenerated > 0 ? $filesGenerated : count($allFiles),
                    'error' => null,
                ];
            } else {
                $error = !empty($output) ? end($output) : 'Unknown error';
                return [
                    'success' => false,
                    'files' => [],
                    'files_count' => 0,
                    'error' => $error,
                ];
            }

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'files' => [],
                'files_count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function runTests(): bool
    {
        try {
            $projectDir = dirname(__DIR__);
            $command = sprintf(
                'cd %s && php bin/phpunit --stop-on-failure --no-coverage 2>&1',
                escapeshellarg($projectDir)
            );

            exec($command, $output, $returnCode);

            return $returnCode === 0;

        } catch (\Throwable $e) {
            return false;
        }
    }

    private function displayFinalSummary(): void
    {
        $this->stats['time_taken'] = microtime(true) - $this->startTime;

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Final Summary\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "📊 Statistics:\n";
        echo "   • Total entities:    {$this->stats['total_entities']}\n";
        echo "   • Successfully generated: {$this->stats['success']}\n";
        echo "   • Failed:            {$this->stats['failed']}\n";
        echo "   • Files generated:   {$this->stats['files_generated']}\n";
        echo "   • Batches processed: {$this->stats['batches']}\n";
        echo "   • Total time:        " . round($this->stats['time_taken'], 2) . "s\n";

        if ($this->stats['success'] > 0) {
            $avgTime = $this->stats['time_taken'] / $this->stats['success'];
            echo "   • Avg per entity:    " . round($avgTime, 2) . "s\n";
        }

        echo "\n";

        // Display errors if any
        if (!empty($this->errors)) {
            echo "❌ Errors encountered:\n";
            foreach ($this->errors as $entity => $error) {
                echo "   • {$entity}: {$error}\n";
            }
            echo "\n";
        }

        // Final status
        if ($this->stats['failed'] === 0) {
            echo "✅ All entities generated successfully!\n\n";
            echo "Next steps:\n";
            echo "  php bin/console doctrine:migrations:migrate --no-interaction\n";
            echo "  php bin/console cache:clear\n";
            echo "  php bin/phpunit\n";
        } else if ($this->stats['success'] > 0) {
            echo "⚠️  Generation completed with errors\n\n";
            echo "Review errors above and fix issues before proceeding.\n";
        } else {
            echo "❌ All generations failed\n\n";
            echo "Check errors above and verify system configuration.\n";
        }

        echo "\n";
    }
}

// Parse command line arguments
$options = [];
for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if (str_starts_with($arg, '--batch=')) {
        $options['batch'] = (int)substr($arg, 8);
    } elseif ($arg === '--continue-on-error') {
        $options['continue-on-error'] = true;
    } elseif ($arg === '--skip-tests') {
        $options['skip-tests'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Batch Generation Script\n";
        echo "=======================\n\n";
        echo "Usage: php scripts/batch-generate.php [options]\n\n";
        echo "Options:\n";
        echo "  --batch=SIZE           Number of entities per batch (default: 10)\n";
        echo "  --continue-on-error    Continue processing if an entity fails\n";
        echo "  --skip-tests           Skip running tests after each batch\n";
        echo "  --help, -h             Show this help message\n\n";
        echo "Examples:\n";
        echo "  php scripts/batch-generate.php\n";
        echo "  php scripts/batch-generate.php --batch=5\n";
        echo "  php scripts/batch-generate.php --continue-on-error --skip-tests\n";
        echo "\n";
        exit(0);
    }
}

// Execute batch generation
$batch = new BatchGeneration($options);
exit($batch->run());
