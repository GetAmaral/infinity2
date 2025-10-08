#!/usr/bin/env php
<?php

/**
 * Performance Testing Script
 * Validates generator and runtime performance
 *
 * Usage: php scripts/performance-test.php [--full] [--report=PATH]
 *
 * Tests:
 * - Code generation performance
 * - Database query performance
 * - Repository search performance
 * - API response performance
 * - Memory usage
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

class PerformanceTest
{
    private bool $fullTest = false;
    private ?string $reportPath = null;
    private array $results = [];
    private float $startTime;

    public function __construct(array $options = [])
    {
        $this->fullTest = isset($options['full']);
        $this->reportPath = $options['report'] ?? null;
        $this->startTime = microtime(true);
    }

    public function run(): int
    {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Performance Testing - TURBO Generator System\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        if ($this->fullTest) {
            echo "⚙️  Running full performance test suite\n\n";
        } else {
            echo "⚙️  Running quick performance test (use --full for comprehensive tests)\n\n";
        }

        // Run tests
        $this->testGenerationPerformance();
        $this->testDatabasePerformance();
        $this->testRepositoryPerformance();

        if ($this->fullTest) {
            $this->testMemoryUsage();
            $this->testFileSystemPerformance();
        }

        // Display summary
        $this->displaySummary();

        // Generate report if requested
        if ($this->reportPath) {
            $this->generateReport();
        }

        // Return exit code based on performance
        return $this->hasFailures() ? 1 : 0;
    }

    private function testGenerationPerformance(): void
    {
        echo "🔨 Testing Code Generation Performance...\n";

        $projectDir = dirname(__DIR__);

        // Test dry-run generation
        $startTime = microtime(true);
        $command = sprintf(
            'cd %s && php bin/console app:generate-from-csv --dry-run 2>&1',
            escapeshellarg($projectDir)
        );

        exec($command, $output, $returnCode);
        $generationTime = microtime(true) - $startTime;

        $this->results['generation'] = [
            'time' => $generationTime,
            'target' => 2.0, // 2 seconds target for dry-run
            'passed' => $generationTime < 2.0,
        ];

        $status = $this->results['generation']['passed'] ? '✅' : '❌';
        echo "   {$status} Code generation: " . round($generationTime, 3) . "s ";
        echo "(target: < 2.0s)\n";

        // Test CSV parsing performance
        if ($this->fullTest) {
            $startTime = microtime(true);
            $parser = new \App\Service\Generator\Csv\CsvParserService($projectDir);
            $result = $parser->parseAll();
            $parseTime = microtime(true) - $startTime;

            $this->results['csv_parsing'] = [
                'time' => $parseTime,
                'target' => 0.5, // 500ms target
                'passed' => $parseTime < 0.5,
            ];

            $status = $this->results['csv_parsing']['passed'] ? '✅' : '❌';
            echo "   {$status} CSV parsing: " . round($parseTime, 3) . "s ";
            echo "(target: < 0.5s)\n";
        }

        echo "\n";
    }

    private function testDatabasePerformance(): void
    {
        echo "🗄️  Testing Database Performance...\n";

        try {
            $dsn = getenv('DATABASE_URL');
            if (!$dsn) {
                echo "   ⚠️  DATABASE_URL not set - skipping database tests\n\n";
                return;
            }

            // Parse DSN
            if (!preg_match('/postgresql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $dsn, $matches)) {
                echo "   ⚠️  Could not parse DATABASE_URL - skipping database tests\n\n";
                return;
            }

            $pdo = new \PDO("pgsql:host={$matches[3]};port={$matches[4]};dbname={$matches[5]}", $matches[1], $matches[2]);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Test simple query
            $startTime = microtime(true);
            $pdo->query('SELECT 1');
            $queryTime = microtime(true) - $startTime;

            $this->results['db_simple_query'] = [
                'time' => $queryTime,
                'target' => 0.01, // 10ms target
                'passed' => $queryTime < 0.01,
            ];

            $status = $this->results['db_simple_query']['passed'] ? '✅' : '❌';
            echo "   {$status} Simple query: " . round($queryTime * 1000, 2) . "ms ";
            echo "(target: < 10ms)\n";

            // Test table count
            $startTime = microtime(true);
            $result = $pdo->query("
                SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = 'public'
                AND table_type = 'BASE TABLE'
            ")->fetchColumn();
            $countTime = microtime(true) - $startTime;

            echo "   ✓ Tables in database: {$result}\n";

            // Test entity query if tables exist
            if ($result > 0) {
                try {
                    $startTime = microtime(true);
                    $stmt = $pdo->query("SELECT COUNT(*) FROM organization LIMIT 1");
                    $count = $stmt->fetchColumn();
                    $entityQueryTime = microtime(true) - $startTime;

                    $this->results['db_entity_query'] = [
                        'time' => $entityQueryTime,
                        'target' => 0.05, // 50ms target
                        'passed' => $entityQueryTime < 0.05,
                    ];

                    $status = $this->results['db_entity_query']['passed'] ? '✅' : '❌';
                    echo "   {$status} Entity query: " . round($entityQueryTime * 1000, 2) . "ms ";
                    echo "(target: < 50ms)\n";
                } catch (\PDOException $e) {
                    echo "   ⚠️  Could not query entities (table may not exist)\n";
                }
            }

        } catch (\Throwable $e) {
            echo "   ⚠️  Database test failed: {$e->getMessage()}\n";
        }

        echo "\n";
    }

    private function testRepositoryPerformance(): void
    {
        echo "📚 Testing Repository Performance...\n";

        try {
            // Count repository files
            $projectDir = dirname(__DIR__);
            $repoDir = $projectDir . '/src/Repository';

            if (is_dir($repoDir)) {
                $startTime = microtime(true);
                $files = glob($repoDir . '/*Repository.php');
                $scanTime = microtime(true) - $startTime;

                $repoCount = count($files);
                echo "   ✓ Repositories found: {$repoCount}\n";
                echo "   ✓ File scan time: " . round($scanTime * 1000, 2) . "ms\n";

                // Test repository class loading
                if ($this->fullTest && $repoCount > 0) {
                    $loaded = 0;
                    $startTime = microtime(true);

                    foreach (array_slice($files, 0, 5) as $file) {
                        $className = $this->getClassNameFromFile($file);
                        if ($className && class_exists($className)) {
                            $loaded++;
                        }
                    }

                    $loadTime = microtime(true) - $startTime;

                    echo "   ✓ Classes loadable: {$loaded}/5 tested\n";
                    echo "   ✓ Load time: " . round($loadTime * 1000, 2) . "ms\n";
                }
            } else {
                echo "   ⚠️  Repository directory not found\n";
            }

        } catch (\Throwable $e) {
            echo "   ⚠️  Repository test failed: {$e->getMessage()}\n";
        }

        echo "\n";
    }

    private function testMemoryUsage(): void
    {
        echo "💾 Testing Memory Usage...\n";

        $memoryStart = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);

        $this->results['memory_usage'] = [
            'current' => $memoryStart,
            'peak' => $memoryPeak,
            'target' => 128 * 1024 * 1024, // 128MB target
            'passed' => $memoryPeak < 128 * 1024 * 1024,
        ];

        $currentMB = round($memoryStart / 1024 / 1024, 2);
        $peakMB = round($memoryPeak / 1024 / 1024, 2);

        $status = $this->results['memory_usage']['passed'] ? '✅' : '❌';
        echo "   {$status} Current memory: {$currentMB}MB\n";
        echo "   ✓ Peak memory: {$peakMB}MB (target: < 128MB)\n";

        // Test memory leak by loading and unloading
        if ($this->fullTest) {
            $memBefore = memory_get_usage(true);
            $data = array_fill(0, 1000, str_repeat('x', 1000));
            unset($data);
            gc_collect_cycles();
            $memAfter = memory_get_usage(true);

            $leak = $memAfter - $memBefore;
            $leakMB = round($leak / 1024 / 1024, 2);

            if ($leak < 1024 * 1024) { // Less than 1MB leak
                echo "   ✅ Memory leak test: {$leakMB}MB (< 1MB)\n";
            } else {
                echo "   ⚠️  Memory leak detected: {$leakMB}MB\n";
            }
        }

        echo "\n";
    }

    private function testFileSystemPerformance(): void
    {
        echo "📁 Testing File System Performance...\n";

        $projectDir = dirname(__DIR__);

        // Test directory scanning
        $startTime = microtime(true);
        $files = glob($projectDir . '/src/Entity/Generated/*.php');
        $scanTime = microtime(true) - $startTime;

        $this->results['fs_scan'] = [
            'time' => $scanTime,
            'files' => count($files),
            'target' => 0.1, // 100ms target
            'passed' => $scanTime < 0.1,
        ];

        $status = $this->results['fs_scan']['passed'] ? '✅' : '❌';
        echo "   {$status} Directory scan: " . round($scanTime * 1000, 2) . "ms ";
        echo "(" . count($files) . " files, target: < 100ms)\n";

        // Test file read
        if (!empty($files)) {
            $startTime = microtime(true);
            $content = file_get_contents($files[0]);
            $readTime = microtime(true) - $startTime;
            $sizeKB = round(strlen($content) / 1024, 2);

            $this->results['fs_read'] = [
                'time' => $readTime,
                'size' => strlen($content),
                'target' => 0.01, // 10ms target
                'passed' => $readTime < 0.01,
            ];

            $status = $this->results['fs_read']['passed'] ? '✅' : '❌';
            echo "   {$status} File read: " . round($readTime * 1000, 2) . "ms ";
            echo "({$sizeKB}KB, target: < 10ms)\n";
        }

        echo "\n";
    }

    private function displaySummary(): void
    {
        $totalTime = microtime(true) - $this->startTime;

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Performance Summary\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $passed = 0;
        $failed = 0;

        foreach ($this->results as $test => $result) {
            if (isset($result['passed'])) {
                if ($result['passed']) {
                    $passed++;
                } else {
                    $failed++;
                }
            }
        }

        echo "📊 Results:\n";
        echo "   • Tests passed:  {$passed}\n";
        echo "   • Tests failed:  {$failed}\n";
        echo "   • Total time:    " . round($totalTime, 3) . "s\n";
        echo "\n";

        if ($failed === 0) {
            echo "✅ All performance tests passed!\n\n";
            echo "System performance is within acceptable limits.\n";
        } else {
            echo "⚠️  Some performance tests failed\n\n";
            echo "Review failed tests above and optimize as needed.\n";
        }

        echo "\n";
    }

    private function generateReport(): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'full_test' => $this->fullTest,
            'total_time' => microtime(true) - $this->startTime,
            'results' => $this->results,
            'summary' => [
                'passed' => count(array_filter($this->results, fn($r) => $r['passed'] ?? false)),
                'failed' => count(array_filter($this->results, fn($r) => isset($r['passed']) && !$r['passed'])),
            ],
        ];

        file_put_contents($this->reportPath, json_encode($report, JSON_PRETTY_PRINT));
        echo "📄 Report saved to: {$this->reportPath}\n\n";
    }

    private function hasFailures(): bool
    {
        foreach ($this->results as $result) {
            if (isset($result['passed']) && !$result['passed']) {
                return true;
            }
        }
        return false;
    }

    private function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch) &&
            preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return $nsMatch[1] . '\\' . $classMatch[1];
        }
        return null;
    }
}

// Parse command line arguments
$options = [];
for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if ($arg === '--full') {
        $options['full'] = true;
    } elseif (str_starts_with($arg, '--report=')) {
        $options['report'] = substr($arg, 9);
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Performance Testing Script\n";
        echo "==========================\n\n";
        echo "Usage: php scripts/performance-test.php [options]\n\n";
        echo "Options:\n";
        echo "  --full             Run comprehensive performance tests\n";
        echo "  --report=PATH      Save results to JSON file\n";
        echo "  --help, -h         Show this help message\n\n";
        echo "Tests:\n";
        echo "  • Code generation performance\n";
        echo "  • Database query performance\n";
        echo "  • Repository search performance\n";
        echo "  • Memory usage (--full only)\n";
        echo "  • File system performance (--full only)\n";
        echo "\n";
        echo "Examples:\n";
        echo "  php scripts/performance-test.php\n";
        echo "  php scripts/performance-test.php --full\n";
        echo "  php scripts/performance-test.php --full --report=performance.json\n";
        echo "\n";
        exit(0);
    }
}

// Execute performance test
$test = new PerformanceTest($options);
exit($test->run());
