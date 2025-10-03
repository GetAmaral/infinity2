<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\AuditLogRepository;

/**
 * Service for audit log analytics and anomaly detection
 *
 * Provides metrics, trend analysis, and security anomaly detection
 * for audit log data visualization and monitoring.
 */
final class AuditAnalyticsService
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository
    ) {}

    /**
     * Get number of events today
     */
    public function getEventsToday(): int
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = new \DateTimeImmutable('tomorrow');

        return $this->auditLogRepository->countInPeriod($today, $tomorrow);
    }

    /**
     * Get number of events this week
     */
    public function getEventsThisWeek(): int
    {
        $weekStart = new \DateTimeImmutable('monday this week');
        $now = new \DateTimeImmutable();

        return $this->auditLogRepository->countInPeriod($weekStart, $now);
    }

    /**
     * Get top N most active users
     *
     * @return array<array{user: \App\Entity\User, action_count: int}>
     */
    public function getTopActiveUsers(int $limit = 10): array
    {
        $oneWeekAgo = new \DateTimeImmutable('-7 days');
        return $this->auditLogRepository->getTopActiveUsers($limit, $oneWeekAgo);
    }

    /**
     * Get most modified entities
     *
     * @return array<array{entity_class: string, modification_count: int}>
     */
    public function getMostModifiedEntities(int $limit = 10): array
    {
        $oneWeekAgo = new \DateTimeImmutable('-7 days');
        return $this->auditLogRepository->getMostModifiedEntities($limit, $oneWeekAgo);
    }

    /**
     * Get hourly distribution for charts
     *
     * @return array{hours: array<int>, counts: array<int>}
     */
    public function getHourlyDistribution(): array
    {
        $distribution = $this->auditLogRepository->getHourlyDistribution();

        // Initialize all 24 hours with 0
        $hours = [];
        $counts = [];

        for ($i = 0; $i < 24; $i++) {
            $hours[] = $i;
            $counts[$i] = 0;
        }

        // Fill in actual counts
        foreach ($distribution as $row) {
            $hour = (int)$row['hour'];
            $counts[$hour] = (int)$row['count'];
        }

        return [
            'hours' => $hours,
            'counts' => array_values($counts),
        ];
    }

    /**
     * Get action breakdown for pie chart
     *
     * @return array{labels: array<string>, data: array<int>, colors: array<string>}
     */
    public function getActionBreakdown(): array
    {
        $oneWeekAgo = new \DateTimeImmutable('-7 days');
        $breakdown = $this->auditLogRepository->getActionBreakdown($oneWeekAgo);

        $labels = [];
        $data = [];
        $colors = [
            'entity_created' => 'rgba(34, 197, 94, 0.7)',   // Green
            'entity_updated' => 'rgba(59, 130, 246, 0.7)',  // Blue
            'entity_deleted' => 'rgba(239, 68, 68, 0.7)',   // Red
        ];

        $chartColors = [];

        foreach ($breakdown as $row) {
            $action = $row['action'];
            $label = str_replace('entity_', '', $action);
            $label = ucfirst($label);

            $labels[] = $label;
            $data[] = (int)$row['count'];
            $chartColors[] = $colors[$action] ?? 'rgba(156, 163, 175, 0.7)';
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $chartColors,
        ];
    }

    /**
     * Detect anomalies in audit logs
     *
     * @return array<array{type: string, severity: string, message: string, data?: mixed}>
     */
    public function detectAnomalies(): array
    {
        $anomalies = [];

        // Detect unusual activity patterns
        if ($bulkOps = $this->detectBulkOperations()) {
            $anomalies[] = $bulkOps;
        }

        if ($offHours = $this->detectOffHoursActivity()) {
            $anomalies[] = $offHours;
        }

        if ($rapidChanges = $this->detectRapidChanges()) {
            $anomalies[] = $rapidChanges;
        }

        return $anomalies;
    }

    /**
     * Detect bulk operations (>100 ops in 1 hour by single user)
     */
    private function detectBulkOperations(): ?array
    {
        $threshold = 100;
        $window = new \DateTimeImmutable('-1 hour');

        $results = $this->auditLogRepository->findHighVolumeUsers($window, $threshold);

        if (!empty($results)) {
            return [
                'type' => 'bulk_operations',
                'severity' => 'medium',
                'message' => sprintf('%d user(s) performed bulk operations (>%d ops/hour)', count($results), $threshold),
                'data' => $results,
            ];
        }

        return null;
    }

    /**
     * Detect off-hours activity (outside 9am-6pm)
     */
    private function detectOffHoursActivity(): ?array
    {
        $now = new \DateTimeImmutable();
        $hour = (int) $now->format('H');

        // Check if current time is outside business hours
        if ($hour < 9 || $hour > 18) {
            $count = $this->auditLogRepository->countInLastHour();

            if ($count > 20) {
                return [
                    'type' => 'off_hours_activity',
                    'severity' => 'low',
                    'message' => sprintf('%d operations outside business hours (current hour: %d:00)', $count, $hour),
                    'data' => ['count' => $count, 'hour' => $hour],
                ];
            }
        }

        return null;
    }

    /**
     * Detect rapid changes (same entity modified >10 times in 5 minutes)
     */
    private function detectRapidChanges(): ?array
    {
        $threshold = 10;
        $window = new \DateTimeImmutable('-5 minutes');

        $results = $this->auditLogRepository->findRapidlyChangingEntities($window, $threshold);

        if (!empty($results)) {
            return [
                'type' => 'rapid_changes',
                'severity' => 'high',
                'message' => sprintf('%d entit(y/ies) changed rapidly (>%d times in 5 min)', count($results), $threshold),
                'data' => $results,
            ];
        }

        return null;
    }

    /**
     * Get summary statistics for dashboard
     *
     * @return array{total_events: int, events_today: int, events_week: int, anomaly_count: int}
     */
    public function getSummaryStatistics(): array
    {
        return [
            'total_events' => $this->auditLogRepository->count([]),
            'events_today' => $this->getEventsToday(),
            'events_week' => $this->getEventsThisWeek(),
            'anomaly_count' => count($this->detectAnomalies()),
        ];
    }
}
