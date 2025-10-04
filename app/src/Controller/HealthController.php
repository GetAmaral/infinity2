<?php

namespace App\Controller;

use App\Service\PerformanceMonitor;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class HealthController extends AbstractController
{
    public function __construct(
        private readonly PerformanceMonitor $performanceMonitor,
        private readonly Connection $connection,
        private readonly CacheInterface $cache,
        private readonly ParameterBagInterface $params
    ) {
    }

    #[Route('/health', name: 'app_health')]
    public function check(): JsonResponse
    {
        return $this->json([
            'status' => 'OK',
            'timestamp' => new \DateTimeImmutable(),
            'version' => '1.0.0'
        ]);
    }

    #[Route('/health/detailed', name: 'app_health_detailed')]
    public function detailedCheck(Request $request): JsonResponse
    {
        $this->performanceMonitor->startTimer('health_check');

        $checks = [];

        // Database connectivity check
        try {
            $this->connection->executeQuery('SELECT 1');
            $checks['database'] = [
                'status' => 'OK',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $checks['database'] = [
                'status' => 'ERROR',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }

        // PostgreSQL version and UUIDv7 support check
        try {
            $result = $this->connection->executeQuery('SELECT version(), uuidv7()')->fetchAssociative();
            $checks['postgresql'] = [
                'status' => 'OK',
                'version' => $result['version'] ?? 'Unknown',
                'uuidv7_support' => !empty($result['uuidv7']),
                'message' => 'PostgreSQL with UUIDv7 support confirmed'
            ];
        } catch (\Exception $e) {
            $checks['postgresql'] = [
                'status' => 'WARNING',
                'message' => 'PostgreSQL version/UUIDv7 check failed: ' . $e->getMessage()
            ];
        }

        // Redis connectivity and info check
        try {
            // Test Redis connection by setting and getting a test value
            $testKey = 'health_check_' . time();
            $testValue = 'OK';

            // Use cache to test Redis
            $this->cache->get($testKey, function() use ($testValue) {
                return $testValue;
            });

            // Get Redis info if extension is loaded
            $redisInfo = [];
            if (extension_loaded('redis')) {
                try {
                    $redis = new \Redis();
                    $redis->connect($_ENV['REDIS_HOST'] ?? 'redis', (int)($_ENV['REDIS_PORT'] ?? 6379));
                    $info = $redis->info();
                    $redisInfo = [
                        'version' => $info['redis_version'] ?? 'Unknown',
                        'used_memory' => $info['used_memory_human'] ?? 'Unknown',
                        'connected_clients' => $info['connected_clients'] ?? 0,
                        'uptime_days' => round(($info['uptime_in_seconds'] ?? 0) / 86400, 1)
                    ];
                    $redis->close();
                } catch (\Exception $e) {
                    $redisInfo = ['info_error' => $e->getMessage()];
                }
            }

            $checks['redis'] = [
                'status' => 'OK',
                'message' => 'Redis connection successful',
                'extension_loaded' => extension_loaded('redis'),
                'info' => $redisInfo
            ];
        } catch (\Exception $e) {
            $checks['redis'] = [
                'status' => 'ERROR',
                'message' => 'Redis connection failed: ' . $e->getMessage(),
                'extension_loaded' => extension_loaded('redis')
            ];
        }

        // Messenger queue status check
        try {
            $queueStats = $this->connection->executeQuery("
                SELECT queue_name, COUNT(*) as message_count
                FROM messenger_messages
                WHERE delivered_at IS NULL
                GROUP BY queue_name
            ")->fetchAllAssociative();

            $failedCount = $this->connection->executeQuery("
                SELECT COUNT(*) as count FROM messenger_messages WHERE queue_name = 'failed'
            ")->fetchAssociative()['count'] ?? 0;

            $totalPending = array_sum(array_column($queueStats, 'message_count'));

            $checks['messenger'] = [
                'status' => $failedCount > 10 ? 'WARNING' : 'OK',
                'message' => 'Messenger queue operational',
                'queues' => $queueStats,
                'total_pending' => $totalPending,
                'failed_count' => (int)$failedCount
            ];

            if ($failedCount > 10) {
                $checks['messenger']['message'] = "Warning: {$failedCount} failed messages in queue";
            }
        } catch (\Exception $e) {
            $checks['messenger'] = [
                'status' => 'WARNING',
                'message' => 'Could not check messenger queue: ' . $e->getMessage()
            ];
        }

        // Disk space check
        $projectDir = $this->params->get('kernel.project_dir');
        $diskTotal = disk_total_space($projectDir);
        $diskFree = disk_free_space($projectDir);
        $diskUsed = $diskTotal - $diskFree;
        $diskUsedPercent = round(($diskUsed / $diskTotal) * 100, 2);

        $checks['disk_space'] = [
            'status' => $diskUsedPercent > 90 ? 'ERROR' : ($diskUsedPercent > 80 ? 'WARNING' : 'OK'),
            'total' => $this->formatBytes($diskTotal),
            'used' => $this->formatBytes($diskUsed),
            'free' => $this->formatBytes($diskFree),
            'used_percent' => $diskUsedPercent,
            'message' => $diskUsedPercent > 90 ? 'Critical: Disk usage above 90%' : 'Disk space OK'
        ];

        // Storage directories check
        $storageChecks = [];
        $criticalDirs = [
            'videos_originals' => $projectDir . '/var/videos/originals',
            'videos_hls' => $projectDir . '/var/videos/hls',
            'uploads' => $projectDir . '/public/uploads',
            'cache' => $projectDir . '/var/cache',
            'logs' => $projectDir . '/var/log'
        ];

        foreach ($criticalDirs as $name => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $size = $exists ? $this->getDirectorySize($path) : 0;

            $storageChecks[$name] = [
                'exists' => $exists,
                'writable' => $writable,
                'path' => $path,
                'size' => $this->formatBytes($size)
            ];
        }

        $allWritable = !in_array(false, array_column($storageChecks, 'writable'));
        $checks['storage'] = [
            'status' => $allWritable ? 'OK' : 'ERROR',
            'message' => $allWritable ? 'All storage directories writable' : 'Some directories not writable',
            'directories' => $storageChecks
        ];

        // PHP extensions check
        $extensionsStatus = [
            'pdo_pgsql' => extension_loaded('pdo_pgsql'),
            'opcache' => function_exists('opcache_get_status'), // OPcache check works in CLI
            'intl' => extension_loaded('intl'),
            'gd' => extension_loaded('gd'),
            'redis' => extension_loaded('redis')
        ];

        $loadedExtensions = array_keys(array_filter($extensionsStatus));
        $missingExtensions = array_keys(array_filter($extensionsStatus, fn($loaded) => !$loaded));

        $checks['php_extensions'] = [
            'status' => empty($missingExtensions) ? 'OK' : 'ERROR',
            'loaded' => $loadedExtensions,
            'missing' => $missingExtensions,
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false,
            'message' => empty($missingExtensions) ? 'All required extensions loaded' : 'Missing extensions: ' . implode(', ', $missingExtensions)
        ];

        // Environment configuration check
        $envVars = [
            'APP_ENV' => $_ENV['APP_ENV'] ?? 'not set',
            'DATABASE_URL' => isset($_ENV['DATABASE_URL']) ? 'configured' : 'not set',
            'REDIS_URL' => isset($_ENV['REDIS_URL']) ? 'configured' : 'not set',
            'APP_SECRET' => isset($_ENV['APP_SECRET']) ? 'configured' : 'not set',
        ];

        $checks['environment'] = [
            'status' => 'OK',
            'variables' => $envVars,
            'message' => 'Environment variables configured'
        ];

        // System metrics
        $systemMetrics = $this->performanceMonitor->getSystemMetrics();
        $checks['system'] = [
            'status' => 'OK',
            'metrics' => $systemMetrics
        ];

        // Performance metrics
        $checkDuration = $this->performanceMonitor->endTimer('health_check');

        $response = [
            'status' => $this->determineOverallStatus($checks),
            'timestamp' => new \DateTimeImmutable(),
            'version' => '1.0.0',
            'environment' => $this->getParameter('kernel.environment'),
            'checks' => $checks,
            'performance' => [
                'check_duration_ms' => round($checkDuration * 1000, 2),
                'system_metrics' => $systemMetrics
            ]
        ];

        return $this->json($response);
    }

    #[Route('/health/metrics', name: 'app_health_metrics')]
    public function metrics(): JsonResponse
    {
        $this->performanceMonitor->startTimer('metrics_collection');

        $systemMetrics = $this->performanceMonitor->getSystemMetrics();

        // Database metrics
        try {
            $dbMetrics = $this->connection->executeQuery("
                SELECT
                    schemaname,
                    relname as table_name,
                    n_tup_ins as inserts,
                    n_tup_upd as updates,
                    n_tup_del as deletes,
                    n_live_tup as live_tuples,
                    n_dead_tup as dead_tuples
                FROM pg_stat_user_tables
                ORDER BY schemaname, relname
            ")->fetchAllAssociative();

            // Add database size information
            $dbSizeResult = $this->connection->executeQuery("
                SELECT pg_size_pretty(pg_database_size(current_database())) as database_size
            ")->fetchAssociative();

            $dbMetrics = [
                'tables' => $dbMetrics,
                'database_size' => $dbSizeResult['database_size'] ?? 'Unknown',
                'connection_count' => $this->connection->executeQuery(
                    "SELECT count(*) as count FROM pg_stat_activity WHERE datname = current_database()"
                )->fetchAssociative()['count'] ?? 0
            ];
        } catch (\Exception $e) {
            $dbMetrics = ['error' => 'Could not fetch database metrics: ' . $e->getMessage()];
        }

        $collectionDuration = $this->performanceMonitor->endTimer('metrics_collection');

        return $this->json([
            'timestamp' => new \DateTimeImmutable(),
            'system' => $systemMetrics,
            'database' => $dbMetrics,
            'collection_duration_ms' => round($collectionDuration * 1000, 2)
        ]);
    }

    private function determineOverallStatus(array $checks): string
    {
        foreach ($checks as $check) {
            if (isset($check['status']) && $check['status'] === 'ERROR') {
                return 'ERROR';
            }
        }

        foreach ($checks as $check) {
            if (isset($check['status']) && $check['status'] === 'WARNING') {
                return 'WARNING';
            }
        }

        return 'OK';
    }

    private function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;

        try {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Exception $e) {
            // Directory might not exist or be accessible
            $size = 0;
        }

        return $size;
    }
}