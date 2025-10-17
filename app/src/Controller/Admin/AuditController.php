<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\AuditSearchType;
use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use App\Service\AuditExportService;
use App\Service\AuditAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

/**
 * Audit Log Admin Controller
 *
 * Provides web-based interface for viewing and analyzing audit logs.
 * Accessible only to administrators (ROLE_ADMIN).
 */
#[Route('/admin/audit')]
#[IsGranted('ROLE_ADMIN')]
class AuditController extends AbstractController
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository,
        private readonly UserRepository $userRepository,
        private readonly AuditExportService $exportService,
        private readonly AuditAnalyticsService $analyticsService
    ) {}

    /**
     * Audit log index with search and filtering
     */
    #[Route('/', name: 'admin_audit_index')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(AuditSearchType::class);
        $form->handleRequest($request);

        // Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = 50; // Optimal for audit logs

        $auditLogs = [];
        $totalCount = 0;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $auditLogs = $this->searchAuditLogs($data, $page, $itemsPerPage);
            $totalCount = $this->countSearchResults($data);
        } else {
            // Default: show recent audit events with pagination
            $offset = ($page - 1) * $itemsPerPage;
            $allLogs = $this->auditLogRepository->findRecent(10000); // Get all for total count
            $totalCount = count($allLogs);
            $auditLogs = array_slice($allLogs, $offset, $itemsPerPage);
        }

        $totalPages = (int) ceil($totalCount / $itemsPerPage);

        // Get analytics data for dashboard
        $stats = [
            'total_events' => $this->auditLogRepository->count([]),
            'events_today' => $this->analyticsService->getEventsToday(),
            'events_week' => $this->analyticsService->getEventsThisWeek(),
            'events_hour' => $this->auditLogRepository->countInLastHour(),
        ];

        // Get raw action breakdown from repository
        $actionBreakdown = $this->auditLogRepository->getActionBreakdown(new \DateTimeImmutable('-7 days'));
        $topUsers = $this->analyticsService->getTopActiveUsers(5);

        return $this->render('admin/audit/index.html.twig', [
            'searchForm' => $form,
            'auditLogs' => $auditLogs,
            'totalCount' => $totalCount,
            'stats' => $stats,
            'actionBreakdown' => $actionBreakdown,
            'topUsers' => $topUsers,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'itemsPerPage' => $itemsPerPage,
        ]);
    }

    /**
     * View full audit trail for a specific entity
     */
    #[Route('/entity/{class}/{id}', name: 'admin_audit_entity')]
    public function viewEntity(string $class, string $id): Response
    {
        // Decode the class name (URL-encoded)
        $entityClass = urldecode($class);

        try {
            $entityId = Uuid::fromString($id);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException('Invalid entity ID');
        }

        $auditLogs = $this->auditLogRepository->findByEntity($entityClass, $entityId);

        if (empty($auditLogs)) {
            $this->addFlash('warning', 'No audit history found for this entity.');
        }

        return $this->render('admin/audit/entity_timeline.html.twig', [
            'entityClass' => $entityClass,
            'entityId' => $entityId,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * View all actions by a specific user
     */
    #[Route('/user/{id}', name: 'admin_audit_user')]
    public function viewUser(string $id): Response
    {
        try {
            $userId = Uuid::fromString($id);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException('Invalid user ID');
        }

        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $auditLogs = $this->auditLogRepository->findByUser($user);

        return $this->render('admin/audit/user_actions.html.twig', [
            'user' => $user,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Export audit logs to CSV or JSON
     */
    #[Route('/export', name: 'admin_audit_export')]
    public function export(Request $request): Response
    {
        $format = $request->query->get('format', 'csv');

        $form = $this->createForm(AuditSearchType::class);
        $form->handleRequest($request);

        $auditLogs = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $auditLogs = $this->searchAuditLogs($data);
        } else {
            // Export recent 1000 if no filters
            $auditLogs = $this->auditLogRepository->findRecent(1000);
        }

        if ($format === 'json') {
            return $this->exportService->exportToJson($auditLogs);
        }

        return $this->exportService->exportToCsv($auditLogs);
    }

    /**
     * Search audit logs based on form criteria with pagination
     */
    private function searchAuditLogs(array $data, int $page, int $itemsPerPage): array
    {
        // Start with all logs
        $auditLogs = $this->auditLogRepository->findRecent(10000);

        // Filter by entity class
        if (!empty($data['entityClass'])) {
            $auditLogs = array_filter($auditLogs, function ($log) use ($data) {
                return $log->getEntityClass() === $data['entityClass'];
            });
        }

        // Filter by action
        if (!empty($data['action'])) {
            $auditLogs = array_filter($auditLogs, function ($log) use ($data) {
                return $log->getAction() === $data['action'];
            });
        }

        // Filter by user
        if (!empty($data['user'])) {
            $auditLogs = array_filter($auditLogs, function ($log) use ($data) {
                return $log->getUser() && $log->getUser()->getId()->equals($data['user']->getId());
            });
        }

        // Filter by date from
        if (!empty($data['dateFrom'])) {
            $auditLogs = array_filter($auditLogs, function ($log) use ($data) {
                return $log->getCreatedAt() >= $data['dateFrom'];
            });
        }

        // Filter by date to
        if (!empty($data['dateTo'])) {
            $auditLogs = array_filter($auditLogs, function ($log) use ($data) {
                return $log->getCreatedAt() <= $data['dateTo'];
            });
        }

        $auditLogs = array_values($auditLogs);

        // Apply pagination
        $offset = ($page - 1) * $itemsPerPage;
        return array_slice($auditLogs, $offset, $itemsPerPage);
    }

    /**
     * Count search results for pagination
     */
    private function countSearchResults(array $data): int
    {
        // Start with all logs
        $auditLogs = $this->auditLogRepository->findRecent(10000);

        // Apply same filters
        if (!empty($data['entityClass'])) {
            $auditLogs = array_filter($auditLogs, fn($log) => $log->getEntityClass() === $data['entityClass']);
        }

        if (!empty($data['action'])) {
            $auditLogs = array_filter($auditLogs, fn($log) => $log->getAction() === $data['action']);
        }

        if (!empty($data['user'])) {
            $auditLogs = array_filter($auditLogs, fn($log) =>
                $log->getUser() && $log->getUser()->getId()->equals($data['user']->getId())
            );
        }

        if (!empty($data['dateFrom'])) {
            $auditLogs = array_filter($auditLogs, fn($log) => $log->getCreatedAt() >= $data['dateFrom']);
        }

        if (!empty($data['dateTo'])) {
            $auditLogs = array_filter($auditLogs, fn($log) => $log->getCreatedAt() <= $data['dateTo']);
        }

        return count($auditLogs);
    }
}
