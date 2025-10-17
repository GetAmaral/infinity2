<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\AuditLogRepository;

/**
 * Service for predictive analytics on audit data
 *
 * Uses statistical methods to forecast future audit activity
 * and detect trends for capacity planning.
 */
final class PredictiveAnalyticsService
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository
    ) {}

    /**
     * Predict next week's audit activity using linear regression
     *
     * @return array{predicted_events: int, trend: string, confidence: float, historical_data: array<int>}
     */
    public function predictNextWeekActivity(): array
    {
        // Analyze last 4 weeks
        $weeks = [];
        for ($i = 4; $i >= 1; $i--) {
            $from = new \DateTimeImmutable("-{$i} weeks");
            $to = new \DateTimeImmutable("-" . ($i - 1) . " weeks");
            $weeks[] = $this->auditLogRepository->countInPeriod($from, $to);
        }

        // Calculate prediction using linear regression
        $prediction = $this->linearRegression($weeks);

        return [
            'predicted_events' => max(0, (int)round($prediction)),
            'trend' => $this->calculateTrend($weeks),
            'confidence' => $this->calculateConfidence($weeks),
            'historical_data' => $weeks,
        ];
    }

    /**
     * Simple linear regression for time series prediction
     *
     * Formula: y = mx + b
     * Where m = slope, b = intercept
     */
    private function linearRegression(array $data): float
    {
        $n = count($data);

        if ($n === 0) {
            return 0;
        }

        if ($n === 1) {
            return $data[0];
        }

        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($data as $i => $y) {
            $x = $i + 1;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        // Calculate slope and intercept
        $denominator = ($n * $sumX2 - $sumX * $sumX);

        if ($denominator == 0) {
            // Avoid division by zero - return average
            return $sumY / $n;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Predict next value (n + 1)
        return $slope * ($n + 1) + $intercept;
    }

    /**
     * Calculate trend direction
     */
    private function calculateTrend(array $data): string
    {
        if (count($data) < 2) {
            return 'stable';
        }

        $first = array_slice($data, 0, 2);
        $last = array_slice($data, -2);

        $firstAvg = array_sum($first) / count($first);
        $lastAvg = array_sum($last) / count($last);

        $percentChange = ($lastAvg - $firstAvg) / max(1, $firstAvg) * 100;

        if ($percentChange > 10) {
            return 'increasing';
        } elseif ($percentChange < -10) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Calculate prediction confidence (0.0 to 1.0)
     *
     * Higher confidence when data shows consistent pattern
     */
    private function calculateConfidence(array $data): float
    {
        if (count($data) < 2) {
            return 0.5;
        }

        // Calculate coefficient of variation (CV)
        // Lower CV = more consistent = higher confidence
        $mean = array_sum($data) / count($data);

        if ($mean == 0) {
            return 0.5;
        }

        $variance = 0;
        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }
        $variance /= count($data);

        $stdDev = sqrt($variance);
        $cv = $stdDev / $mean;

        // Convert CV to confidence (0-1 scale)
        // CV of 0 = 100% confidence, CV of 1+ = 0% confidence
        $confidence = max(0, min(1, 1 - $cv));

        return round($confidence, 2);
    }

    /**
     * Predict daily activity for next 7 days
     *
     * @return array<array{date: string, predicted_count: int}>
     */
    public function predictDailyActivity(int $days = 7): array
    {
        // Get last 7 days of data
        $historicalData = [];
        for ($i = 7; $i >= 1; $i--) {
            $from = new \DateTimeImmutable("-{$i} days 00:00:00");
            $to = new \DateTimeImmutable("-" . ($i - 1) . " days 00:00:00");
            $historicalData[] = $this->auditLogRepository->countInPeriod($from, $to);
        }

        // Calculate average and trend
        $average = array_sum($historicalData) / count($historicalData);
        $prediction = $this->linearRegression($historicalData);

        // Generate predictions
        $predictions = [];
        $now = new \DateTimeImmutable();

        for ($i = 1; $i <= $days; $i++) {
            $date = $now->modify("+{$i} days");

            // Use regression prediction with some variation
            $predictedCount = max(0, (int)round($prediction + ($i - 1) * ($prediction - $average) / 7));

            $predictions[] = [
                'date' => $date->format('Y-m-d'),
                'predicted_count' => $predictedCount,
            ];
        }

        return $predictions;
    }

    /**
     * Get capacity recommendations based on trends
     *
     * @return array{status: string, message: string, recommendation: string}
     */
    public function getCapacityRecommendation(): array
    {
        $prediction = $this->predictNextWeekActivity();

        $currentWeekStart = new \DateTimeImmutable('monday this week');
        $now = new \DateTimeImmutable();
        $currentWeekCount = $this->auditLogRepository->countInPeriod($currentWeekStart, $now);

        $predictedCount = $prediction['predicted_events'];
        $percentIncrease = ($predictedCount - $currentWeekCount) / max(1, $currentWeekCount) * 100;

        if ($percentIncrease > 50) {
            return [
                'status' => 'warning',
                'message' => sprintf('Significant increase predicted: +%.1f%%', $percentIncrease),
                'recommendation' => 'Consider increasing audit log retention capacity and monitoring resources',
            ];
        } elseif ($percentIncrease > 25) {
            return [
                'status' => 'info',
                'message' => sprintf('Moderate increase predicted: +%.1f%%', $percentIncrease),
                'recommendation' => 'Monitor audit system performance closely next week',
            ];
        } elseif ($percentIncrease < -25) {
            return [
                'status' => 'info',
                'message' => sprintf('Activity decrease predicted: %.1f%%', $percentIncrease),
                'recommendation' => 'Normal operations expected, capacity is sufficient',
            ];
        } else {
            return [
                'status' => 'success',
                'message' => 'Stable activity predicted',
                'recommendation' => 'Current capacity is appropriate',
            ];
        }
    }
}
