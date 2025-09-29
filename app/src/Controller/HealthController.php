<?php

namespace App\Controller;

use App\Service\PerformanceMonitor;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    public function __construct(
        private readonly PerformanceMonitor $performanceMonitor,
        private readonly Connection $connection
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
}