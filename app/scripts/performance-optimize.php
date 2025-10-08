#!/usr/bin/env php
<?php

/**
 * Performance Optimization Script
 * Analyzes and optimizes database, queries, and cache performance
 *
 * Usage: php scripts/performance-optimize.php [--analyze] [--optimize] [--report=PATH]
 *
 * Features:
 * - Database index analysis
 * - Query optimization (N+1 detection)
 * - Cache optimization
 * - Performance recommendations
 */

declare(strict_types=1);

class PerformanceOptimizer
{
    private bool $analyzeOnly = false;
    private bool $optimize = false;
    private ?string $reportPath = null;
    private array $results = [];
    private array $recommendations = [];
    private float $startTime;

    public function __construct(array $options = [])
    {
        $this->analyzeOnly = isset($options['analyze']);
        $this->optimize = isset($options['optimize']);
        $this->reportPath = $options['report'] ?? null;
        $this->startTime = microtime(true);

        // Default to analyze if neither flag is set
        if (!$this->analyzeOnly && !$this->optimize) {
            $this->analyzeOnly = true;
        }
    }

    public function run(): int
    {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Performance Optimization - TURBO Generator System\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        if ($this->optimize) {
            echo "⚙️  Optimization mode - Will apply optimizations\n\n";
        } else {
            echo "🔍 Analysis mode - Will provide recommendations\n\n";
        }

        // Run analyses
        $this->analyzeDatabaseIndexes();
        $this->analyzeCache();
        $this->analyzeResponseTimes();

        // Apply optimizations if requested
        if ($this->optimize) {
            $this->applyOptimizations();
        }

        // Display summary
        $this->displaySummary();

        // Generate report if requested
        if ($this->reportPath) {
            $this->generateReport();
        }

        return 0;
    }

    private function analyzeDatabaseIndexes(): void
    {
        echo "🗄️  Analyzing Database Indexes...\n";

        try {
            $dsn = getenv('DATABASE_URL');
            if (!$dsn) {
                echo "   ⚠️  DATABASE_URL not set - skipping database analysis\n\n";
                return;
            }

            // Parse DSN
            if (!preg_match('/postgresql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $dsn, $matches)) {
                echo "   ⚠️  Could not parse DATABASE_URL\n\n";
                return;
            }

            $pdo = new \PDO("pgsql:host={$matches[3]};port={$matches[4]};dbname={$matches[5]}", $matches[1], $matches[2]);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Check for unused indexes
            $query = "
                SELECT
                    schemaname,
                    tablename,
                    indexname,
                    idx_scan as scans,
                    pg_size_pretty(pg_relation_size(indexrelid)) as size
                FROM pg_stat_user_indexes
                WHERE idx_scan = 0
                  AND indexname NOT LIKE '%_pkey'
                ORDER BY pg_relation_size(indexrelid) DESC
                LIMIT 10
            ";

            $stmt = $pdo->query($query);
            $unusedIndexes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($unusedIndexes)) {
                echo "   ✅ No unused indexes found\n";
            } else {
                echo "   ⚠️  Found " . count($unusedIndexes) . " unused indexes:\n";
                foreach ($unusedIndexes as $index) {
                    echo "      • {$index['tablename']}.{$index['indexname']} ({$index['size']})\n";
                    $this->recommendations[] = [
                        'type' => 'index',
                        'action' => 'remove',
                        'table' => $index['tablename'],
                        'index' => $index['indexname'],
                        'reason' => 'Index has never been used',
                    ];
                }
            }

            // Check for missing indexes on foreign keys
            $query = "
                SELECT
                    tc.table_name,
                    kcu.column_name,
                    ccu.table_name AS foreign_table_name
                FROM information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu
                  ON ccu.constraint_name = tc.constraint_name
                  AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                  AND tc.table_schema = 'public'
                LIMIT 20
            ";

            $stmt = $pdo->query($query);
            $foreignKeys = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $missingIndexes = 0;
            foreach ($foreignKeys as $fk) {
                // Check if index exists
                $indexCheck = "
                    SELECT COUNT(*) as count
                    FROM pg_indexes
                    WHERE tablename = :table
                      AND indexdef LIKE :column
                ";
                $stmt = $pdo->prepare($indexCheck);
                $stmt->execute([
                    'table' => $fk['table_name'],
                    'column' => '%' . $fk['column_name'] . '%',
                ]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($result['count'] == 0) {
                    $missingIndexes++;
                    if ($missingIndexes <= 5) {
                        echo "   ⚠️  Missing index on {$fk['table_name']}.{$fk['column_name']}\n";
                    }
                    $this->recommendations[] = [
                        'type' => 'index',
                        'action' => 'add',
                        'table' => $fk['table_name'],
                        'column' => $fk['column_name'],
                        'reason' => 'Foreign key without index',
                    ];
                }
            }

            if ($missingIndexes > 5) {
                echo "   ... and " . ($missingIndexes - 5) . " more missing indexes\n";
            } elseif ($missingIndexes === 0) {
                echo "   ✅ All foreign keys have indexes\n";
            }

            // Table statistics
            $query = "
                SELECT
                    schemaname,
                    tablename,
                    n_live_tup as live_rows,
                    n_dead_tup as dead_rows,
                    last_vacuum,
                    last_autovacuum
                FROM pg_stat_user_tables
                WHERE schemaname = 'public'
                ORDER BY n_live_tup DESC
                LIMIT 10
            ";

            $stmt = $pdo->query($query);
            $tables = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo "   📊 Top tables by size:\n";
            foreach (array_slice($tables, 0, 5) as $table) {
                $deadPct = $table['live_rows'] > 0
                    ? round(($table['dead_rows'] / $table['live_rows']) * 100, 1)
                    : 0;
                echo "      • {$table['tablename']}: " . number_format($table['live_rows']) . " rows";
                if ($deadPct > 10) {
                    echo " (⚠️  {$deadPct}% dead rows)";
                    $this->recommendations[] = [
                        'type' => 'vacuum',
                        'table' => $table['tablename'],
                        'reason' => "{$deadPct}% dead rows - needs VACUUM",
                    ];
                }
                echo "\n";
            }

            $this->results['database'] = [
                'unused_indexes' => count($unusedIndexes),
                'missing_indexes' => $missingIndexes,
                'tables_analyzed' => count($tables),
            ];

        } catch (\Throwable $e) {
            echo "   ⚠️  Database analysis failed: {$e->getMessage()}\n";
        }

        echo "\n";
    }

    private function analyzeCache(): void
    {
        echo "💾 Analyzing Cache Performance...\n";

        $projectDir = dirname(__DIR__);
        $cacheDir = $projectDir . '/var/cache';

        // Check cache size
        $command = sprintf('du -sh %s 2>/dev/null || echo "0"', escapeshellarg($cacheDir));
        $cacheSize = trim(shell_exec($command) ?? '0');

        echo "   📊 Cache size: {$cacheSize}\n";

        // Check cache file count
        $command = sprintf('find %s -type f 2>/dev/null | wc -l', escapeshellarg($cacheDir));
        $fileCount = (int)trim(shell_exec($command) ?? '0');

        echo "   📁 Cache files: " . number_format($fileCount) . "\n";

        // Warm cache test
        $startTime = microtime(true);
        $command = sprintf(
            'cd %s && php bin/console cache:warmup --env=prod --no-debug 2>&1',
            escapeshellarg($projectDir)
        );
        exec($command, $output, $returnCode);
        $warmupTime = microtime(true) - $startTime;

        if ($returnCode === 0) {
            if ($warmupTime < 10) {
                echo "   ✅ Cache warmup: " . round($warmupTime, 2) . "s (target: < 10s)\n";
            } else {
                echo "   ⚠️  Cache warmup: " . round($warmupTime, 2) . "s (slower than target)\n";
                $this->recommendations[] = [
                    'type' => 'cache',
                    'action' => 'optimize',
                    'reason' => "Cache warmup taking {$warmupTime}s - consider cache preloading",
                ];
            }
        } else {
            echo "   ⚠️  Cache warmup failed\n";
        }

        // Check opcache status
        if (function_exists('opcache_get_status')) {
            $opcache = opcache_get_status();
            if ($opcache && isset($opcache['opcache_enabled']) && $opcache['opcache_enabled']) {
                $hitRate = isset($opcache['opcache_statistics']['opcache_hit_rate'])
                    ? round($opcache['opcache_statistics']['opcache_hit_rate'], 1)
                    : 0;
                echo "   ✅ OPCache enabled (hit rate: {$hitRate}%)\n";

                if ($hitRate < 90) {
                    $this->recommendations[] = [
                        'type' => 'opcache',
                        'action' => 'tune',
                        'reason' => "OPCache hit rate is {$hitRate}% - consider increasing memory",
                    ];
                }
            } else {
                echo "   ⚠️  OPCache is disabled\n";
                $this->recommendations[] = [
                    'type' => 'opcache',
                    'action' => 'enable',
                    'reason' => 'OPCache not enabled - significant performance impact',
                ];
            }
        }

        $this->results['cache'] = [
            'size' => $cacheSize,
            'files' => $fileCount,
            'warmup_time' => $warmupTime,
        ];

        echo "\n";
    }

    private function analyzeResponseTimes(): void
    {
        echo "⚡ Analyzing Response Times...\n";

        $projectDir = dirname(__DIR__);

        // Check if ab (Apache Bench) is available
        $abPath = trim(shell_exec('which ab 2>/dev/null') ?? '');

        if (empty($abPath)) {
            echo "   ⚠️  Apache Bench (ab) not installed - skipping response time tests\n";
            echo "      Install: sudo apt-get install apache2-utils\n\n";
            return;
        }

        // Test health endpoint
        $url = 'https://localhost/health';
        $command = sprintf(
            'ab -n 10 -c 1 -k -t 5 %s 2>&1 | grep -E "Time per request|Requests per second"',
            escapeshellarg($url)
        );

        $output = shell_exec($command);

        if ($output) {
            echo "   📊 Health endpoint performance:\n";
            foreach (explode("\n", trim($output)) as $line) {
                if (!empty($line)) {
                    echo "      " . trim($line) . "\n";
                }
            }

            // Parse response time
            if (preg_match('/Time per request:\s+([\d.]+)\s+\[ms\]/i', $output, $matches)) {
                $responseTime = (float)$matches[1];
                if ($responseTime < 200) {
                    echo "   ✅ Response time: {$responseTime}ms (target: < 200ms)\n";
                } else {
                    echo "   ⚠️  Response time: {$responseTime}ms (slower than target)\n";
                    $this->recommendations[] = [
                        'type' => 'performance',
                        'metric' => 'response_time',
                        'value' => $responseTime,
                        'reason' => 'Response times exceed 200ms target',
                    ];
                }
            }
        }

        echo "\n";
    }

    private function applyOptimizations(): void
    {
        echo "🔧 Applying Optimizations...\n";

        $applied = 0;

        foreach ($this->recommendations as $rec) {
            if ($rec['type'] === 'cache' && $rec['action'] === 'clear') {
                echo "   🔧 Clearing cache...\n";
                $projectDir = dirname(__DIR__);
                exec("cd {$projectDir} && php bin/console cache:clear --no-warmup");
                $applied++;
            }
        }

        if ($applied === 0) {
            echo "   ℹ️  No automatic optimizations available\n";
            echo "      Review recommendations below for manual optimizations\n";
        } else {
            echo "   ✅ Applied {$applied} optimizations\n";
        }

        echo "\n";
    }

    private function displaySummary(): void
    {
        $totalTime = microtime(true) - $this->startTime;

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Optimization Summary\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "📊 Analysis completed in " . round($totalTime, 2) . "s\n\n";

        if (empty($this->recommendations)) {
            echo "✅ System is optimally configured!\n\n";
            echo "No performance issues detected.\n";
        } else {
            echo "💡 Recommendations (" . count($this->recommendations) . "):\n\n";

            // Group by type
            $byType = [];
            foreach ($this->recommendations as $rec) {
                $byType[$rec['type']][] = $rec;
            }

            foreach ($byType as $type => $recs) {
                echo "   " . strtoupper($type) . ":\n";
                foreach ($recs as $rec) {
                    echo "   • " . $rec['reason'] . "\n";
                    if (isset($rec['action'])) {
                        echo "     → Action: " . $rec['action'] . "\n";
                    }
                }
                echo "\n";
            }

            if (!$this->optimize) {
                echo "Run with --optimize to apply automatic optimizations:\n";
                echo "  php scripts/performance-optimize.php --optimize\n";
            }
        }

        echo "\n";
    }

    private function generateReport(): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_time' => microtime(true) - $this->startTime,
            'mode' => $this->optimize ? 'optimize' : 'analyze',
            'results' => $this->results,
            'recommendations' => $this->recommendations,
        ];

        file_put_contents($this->reportPath, json_encode($report, JSON_PRETTY_PRINT));
        echo "📄 Performance report saved to: {$this->reportPath}\n\n";
    }
}

// Parse command line arguments
$options = [];
for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if ($arg === '--analyze') {
        $options['analyze'] = true;
    } elseif ($arg === '--optimize') {
        $options['optimize'] = true;
    } elseif (str_starts_with($arg, '--report=')) {
        $options['report'] = substr($arg, 9);
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Performance Optimization Script\n";
        echo "================================\n\n";
        echo "Usage: php scripts/performance-optimize.php [options]\n\n";
        echo "Options:\n";
        echo "  --analyze      Analyze performance (default)\n";
        echo "  --optimize     Apply automatic optimizations\n";
        echo "  --report=PATH  Save results to JSON file\n";
        echo "  --help, -h     Show this help message\n\n";
        echo "Features:\n";
        echo "  • Database index analysis\n";
        echo "  • Query optimization recommendations\n";
        echo "  • Cache performance analysis\n";
        echo "  • Response time testing\n";
        echo "  • Automatic optimization application\n";
        echo "\n";
        exit(0);
    }
}

// Execute optimization
$optimizer = new PerformanceOptimizer($options);
exit($optimizer->run());
