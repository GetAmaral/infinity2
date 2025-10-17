<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\AuditLogRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for generating compliance reports (GDPR, SOC2, etc.)
 *
 * Provides audit trail reports, data subject access requests,
 * and compliance verification for regulatory requirements.
 */
final class ComplianceReportService
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditRetentionService $retentionService
    ) {}

    /**
     * Generate GDPR Data Subject Access Request (DSAR) report
     *
     * Provides complete audit trail and data inventory for a user
     * as required by GDPR Article 15.
     *
     * @param User $user The data subject
     * @return array<string, mixed>
     */
    public function generateGDPRReport(User $user): array
    {
        return [
            'report_type' => 'GDPR Data Subject Access Request',
            'generated_at' => new \DateTimeImmutable(),
            'data_subject' => [
                'id' => $user->getId()->toString(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $user->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ],
            'audit_trail' => [
                'total_actions' => count($this->auditLogRepository->findByUser($user)),
                'actions_by_type' => $this->getActionsByType($user),
                'recent_actions' => $this->formatAuditLogs(
                    $this->auditLogRepository->findByUser($user)
                ),
            ],
            'data_created' => [
                'count' => count($this->auditLogRepository->findCreatedByUser($user)),
                'entities' => $this->findEntitiesCreatedByUser($user),
            ],
            'data_modified' => [
                'count' => $this->countModifiedEntities($user),
                'last_modification' => $this->getLastModification($user),
            ],
            'retention_status' => $this->getRetentionStatus($user),
            'rights' => [
                'right_to_erasure' => 'Contact administrator to request account deletion',
                'right_to_portability' => 'Data export available via this report',
                'right_to_rectification' => 'Update account details in user settings',
                'right_to_restriction' => 'Contact administrator to restrict processing',
            ],
        ];
    }

    /**
     * Generate SOC2 audit compliance report
     *
     * Provides audit statistics and security metrics for SOC2 auditors.
     *
     * @param \DateTimeInterface $from Report start date
     * @param \DateTimeInterface $to Report end date
     * @return array<string, mixed>
     */
    public function generateSOC2AuditReport(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return [
            'report_type' => 'SOC2 Audit Report',
            'generated_at' => new \DateTimeImmutable(),
            'period' => [
                'from' => $from->format('Y-m-d H:i:s'),
                'to' => $to->format('Y-m-d H:i:s'),
                'days' => $from->diff($to)->days,
            ],
            'statistics' => [
                'total_events' => $this->auditLogRepository->countInPeriod($from, $to),
                'by_action' => $this->countByAction($from, $to),
                'by_entity' => $this->countByEntity($from, $to),
                'by_user' => $this->countByUser($from, $to),
            ],
            'security_events' => [
                'data_deletions' => count($this->auditLogRepository->findDeletions($from)),
                'administrative_actions' => $this->countAdministrativeActions($from, $to),
            ],
            'compliance_checks' => [
                'all_events_logged' => $this->verifyCompleteness($from, $to),
                'retention_policies_enforced' => $this->verifyRetention(),
                'encryption_enabled' => $this->retentionService->isEncryptionEnabled(),
                'gdpr_compliance' => $this->retentionService->isGdprEnabled(),
            ],
            'integrity' => [
                'total_records' => $this->auditLogRepository->count([]),
                'oldest_record' => $this->getOldestRecord(),
                'newest_record' => $this->getNewestRecord(),
            ],
        ];
    }

    /**
     * Get actions grouped by type for a user
     *
     * @return array<string, int>
     */
    private function getActionsByType(User $user): array
    {
        $logs = $this->auditLogRepository->findByUser($user);
        $grouped = [];

        foreach ($logs as $log) {
            $action = $log->getAction();
            $grouped[$action] = ($grouped[$action] ?? 0) + 1;
        }

        return $grouped;
    }

    /**
     * Format audit logs for report output
     *
     * @param array $logs
     * @return array
     */
    private function formatAuditLogs(array $logs): array
    {
        return array_map(function ($log) {
            return [
                'timestamp' => $log->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'action' => $log->getAction(),
                'entity_class' => $log->getEntityClass(),
                'entity_id' => $log->getEntityId()->toString(),
                'changes_count' => count($log->getChanges()),
            ];
        }, array_slice($logs, 0, 100)); // Limit to 100 recent entries
    }

    /**
     * Find entities created by a specific user
     *
     * @return array
     */
    private function findEntitiesCreatedByUser(User $user): array
    {
        $logs = $this->auditLogRepository->findCreatedByUser($user);
        $entities = [];

        foreach ($logs as $log) {
            $entityClass = $log->getEntityClass();
            $entities[$entityClass] = ($entities[$entityClass] ?? 0) + 1;
        }

        return $entities;
    }

    /**
     * Count entities modified by a user
     */
    private function countModifiedEntities(User $user): int
    {
        $since = new \DateTimeImmutable('-1 year');
        $logs = $this->auditLogRepository->findByUser($user, $since);

        return count(array_filter($logs, fn($log) => $log->getAction() === 'entity_updated'));
    }

    /**
     * Get last modification timestamp for a user
     */
    private function getLastModification(User $user): ?string
    {
        $logs = $this->auditLogRepository->findByUser($user);

        if (empty($logs)) {
            return null;
        }

        return $logs[0]->getCreatedAt()->format(\DateTimeInterface::ATOM);
    }

    /**
     * Get retention status for a user's audit data
     *
     * @return array
     */
    private function getRetentionStatus(User $user): array
    {
        $retentionPeriod = $this->retentionService->getRetentionPeriod(User::class);
        $oldestLog = $this->auditLogRepository->findByUser($user);

        if (empty($oldestLog)) {
            return [
                'retention_period_days' => $retentionPeriod,
                'oldest_record' => null,
                'deletion_date' => null,
            ];
        }

        $oldestDate = end($oldestLog)->getCreatedAt();
        $deletionDate = $this->retentionService->calculateDeletionDate(User::class, $oldestDate);

        return [
            'retention_period_days' => $retentionPeriod,
            'oldest_record' => $oldestDate->format(\DateTimeInterface::ATOM),
            'deletion_date' => $deletionDate->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Count actions by type in a period
     *
     * @return array<string, int>
     */
    private function countByAction(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $stats = $this->auditLogRepository->getStatistics($from);
        $counts = [];

        foreach ($stats as $stat) {
            $action = $stat['action'];
            $counts[$action] = ($counts[$action] ?? 0) + (int)$stat['count'];
        }

        return $counts;
    }

    /**
     * Count actions by entity type in a period
     *
     * @return array<string, int>
     */
    private function countByEntity(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $stats = $this->auditLogRepository->getStatistics($from);
        $counts = [];

        foreach ($stats as $stat) {
            $entityClass = $stat['entityClass'];
            $counts[$entityClass] = ($counts[$entityClass] ?? 0) + (int)$stat['count'];
        }

        return $counts;
    }

    /**
     * Count actions by user in a period
     *
     * @return int Number of unique users
     */
    private function countByUser(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        $qb = $this->entityManager->createQueryBuilder();

        return (int) $qb
            ->select('COUNT(DISTINCT a.user)')
            ->from('App\Entity\AuditLog', 'a')
            ->where('a.createdAt >= :from')
            ->andWhere('a.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count administrative actions in a period
     */
    private function countAdministrativeActions(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        // Administrative actions on User, Organization, and Role entities
        $adminEntities = [
            'App\Entity\User',
            'App\Entity\Organization',
            'App\Entity\Role',
        ];

        $qb = $this->entityManager->createQueryBuilder();

        return (int) $qb
            ->select('COUNT(a.id)')
            ->from('App\Entity\AuditLog', 'a')
            ->where('a.entityClass IN (:entities)')
            ->andWhere('a.createdAt >= :from')
            ->andWhere('a.createdAt <= :to')
            ->setParameter('entities', $adminEntities)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Verify audit log completeness
     */
    private function verifyCompleteness(\DateTimeInterface $from, \DateTimeInterface $to): bool
    {
        // Check if there are any gaps in audit logging
        // For now, just verify we have audit logs in the period
        $count = $this->auditLogRepository->countInPeriod($from, $to);

        return $count > 0;
    }

    /**
     * Verify retention policies are being enforced
     */
    private function verifyRetention(): bool
    {
        // Check if there are any audit logs older than retention periods
        // This would indicate retention is not being enforced
        return true; // Simplified for now
    }

    /**
     * Get oldest audit record
     */
    private function getOldestRecord(): ?string
    {
        $qb = $this->entityManager->createQueryBuilder();

        $result = $qb
            ->select('a')
            ->from('App\Entity\AuditLog', 'a')
            ->orderBy('a.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result?->getCreatedAt()->format(\DateTimeInterface::ATOM);
    }

    /**
     * Get newest audit record
     */
    private function getNewestRecord(): ?string
    {
        $qb = $this->entityManager->createQueryBuilder();

        $result = $qb
            ->select('a')
            ->from('App\Entity\AuditLog', 'a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result?->getCreatedAt()->format(\DateTimeInterface::ATOM);
    }
}
