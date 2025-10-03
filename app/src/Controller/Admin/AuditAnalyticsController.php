<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\AuditAlertService;
use App\Service\AuditAnalyticsService;
use App\Service\PredictiveAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Audit Analytics Dashboard Controller
 *
 * Provides real-time analytics, anomaly detection, and predictive insights
 * for audit log data. Accessible only to administrators.
 */
#[Route('/admin/audit/analytics')]
#[IsGranted('ROLE_ADMIN')]
class AuditAnalyticsController extends AbstractController
{
    public function __construct(
        private readonly AuditAnalyticsService $analyticsService,
        private readonly PredictiveAnalyticsService $predictiveService,
        private readonly AuditAlertService $alertService
    ) {}

    /**
     * Analytics dashboard index
     */
    #[Route('/', name: 'admin_audit_analytics')]
    public function index(): Response
    {
        // Get current metrics
        $metrics = [
            'summary' => $this->analyticsService->getSummaryStatistics(),
            'events_today' => $this->analyticsService->getEventsToday(),
            'events_week' => $this->analyticsService->getEventsThisWeek(),
            'top_users' => $this->analyticsService->getTopActiveUsers(10),
            'top_entities' => $this->analyticsService->getMostModifiedEntities(10),
            'hourly_distribution' => $this->analyticsService->getHourlyDistribution(),
            'action_breakdown' => $this->analyticsService->getActionBreakdown(),
        ];

        // Detect anomalies
        $anomalies = $this->analyticsService->detectAnomalies();

        // Send alerts for detected anomalies
        if (!empty($anomalies)) {
            $this->alertService->sendAnomalyAlerts($anomalies);
        }

        // Get predictions
        $prediction = $this->predictiveService->predictNextWeekActivity();
        $capacityRecommendation = $this->predictiveService->getCapacityRecommendation();

        // Send capacity alerts if needed
        if ($capacityRecommendation['status'] === 'warning') {
            $this->alertService->sendCapacityAlert($capacityRecommendation);
        }

        return $this->render('admin/audit/analytics.html.twig', [
            'metrics' => $metrics,
            'anomalies' => $anomalies,
            'prediction' => $prediction,
            'capacity' => $capacityRecommendation,
        ]);
    }
}
