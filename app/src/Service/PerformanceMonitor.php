<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class PerformanceMonitor
{
    private array $timers = [];

    public function __construct(
        #[Autowire(service: 'monolog.logger.performance')]
        private readonly LoggerInterface $performanceLogger
    ) {
    }

    public function startTimer(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }

    public function endTimer(string $name, array $context = []): float
    {
        if (!isset($this->timers[$name])) {
            throw new \InvalidArgumentException(sprintf('Timer "%s" was not started', $name));
        }

        $duration = microtime(true) - $this->timers[$name];
        unset($this->timers[$name]);

        $this->performanceLogger->info('Performance metric recorded', [
            'timer' => $name,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'context' => $context,
            'timestamp' => date('c'),
        ]);

        return $duration;
    }

    public function logMemoryUsage(string $context, array $additionalData = []): void
    {
        $this->performanceLogger->info('Memory usage recorded', [
            'context' => $context,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'additional_data' => $additionalData,
            'timestamp' => date('c'),
        ]);
    }

    public function logSlowQuery(string $query, float $duration, array $parameters = []): void
    {
        if ($duration > 0.1) { // Log queries slower than 100ms
            $this->performanceLogger->warning('Slow query detected', [
                'query' => $query,
                'duration_ms' => round($duration * 1000, 2),
                'parameters' => $parameters,
                'timestamp' => date('c'),
            ]);
        }
    }

    public function getSystemMetrics(): array
    {
        $loadAverage = sys_getloadavg();

        return [
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit'),
            'load_average_1min' => $loadAverage[0] ?? null,
            'load_average_5min' => $loadAverage[1] ?? null,
            'load_average_15min' => $loadAverage[2] ?? null,
            'php_version' => PHP_VERSION,
            'opcache_enabled' => extension_loaded('opcache') && opcache_get_status(),
        ];
    }
}