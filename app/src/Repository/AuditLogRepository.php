<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Repository for querying audit log history
 *
 * @extends ServiceEntityRepository<AuditLog>
 */
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * Find all audit entries for a specific entity
     *
     * @return AuditLog[]
     */
    public function findByEntity(string $entityClass, Uuid $entityId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.entityClass = :class')
            ->andWhere('a.entityId = :id')
            ->orderBy('a.createdAt', 'DESC')
            ->setParameter('class', $entityClass)
            ->setParameter('id', $entityId, UuidType::NAME)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all actions performed by a specific user
     *
     * @return AuditLog[]
     */
    public function findByUser(User $user, ?\DateTimeInterface $since = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->orderBy('a.createdAt', 'DESC')
            ->setParameter('user', $user);

        if ($since) {
            $qb->andWhere('a.createdAt >= :since')
               ->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all changes to a specific field across all entities of a type
     *
     * @return AuditLog[]
     */
    public function findChangesByField(string $entityClass, string $fieldName): array
    {
        // PostgreSQL JSON query - check if field exists in changes JSON
        return $this->createQueryBuilder('a')
            ->where('a.entityClass = :class')
            ->andWhere("jsonb_exists(a.changes, :field)")
            ->orderBy('a.createdAt', 'DESC')
            ->setParameter('class', $entityClass)
            ->setParameter('field', $fieldName)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get audit statistics grouped by action and entity type
     *
     * @return array<array{action: string, entityClass: string, count: int}>
     */
    public function getStatistics(\DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.action', 'a.entityClass', 'COUNT(a.id) as count')
            ->where('a.createdAt >= :since')
            ->groupBy('a.action', 'a.entityClass')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent audit events (last N entries)
     *
     * @return AuditLog[]
     */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count audit events in a time period
     */
    public function countInPeriod(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :from')
            ->andWhere('a.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find all deletions in a time period
     *
     * @return AuditLog[]
     */
    public function findDeletions(\DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.action = :action')
            ->andWhere('a.createdAt >= :since')
            ->orderBy('a.createdAt', 'DESC')
            ->setParameter('action', 'entity_deleted')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find audit trail for entities created by a user
     *
     * @return AuditLog[]
     */
    public function findCreatedByUser(User $user, ?string $entityClass = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.action = :action')
            ->orderBy('a.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('action', 'entity_created');

        if ($entityClass) {
            $qb->andWhere('a.entityClass = :class')
               ->setParameter('class', $entityClass);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Delete old audit records (for retention policy enforcement)
     */
    public function deleteOlderThan(string $entityClass, \DateTimeInterface $before): int
    {
        return $this->createQueryBuilder('a')
            ->delete()
            ->where('a.entityClass = :class')
            ->andWhere('a.createdAt < :before')
            ->setParameter('class', $entityClass)
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }

    /**
     * Anonymize user data in old audit records (for GDPR compliance)
     */
    public function anonymizeUserData(\DateTimeInterface $before): int
    {
        return $this->createQueryBuilder('a')
            ->update()
            ->set('a.user', ':null')
            ->set('a.metadata', "jsonb_set(a.metadata, '{user_email}', '\"anonymized@gdpr.local\"')")
            ->set('a.metadata', "jsonb_set(a.metadata, '{ip_address}', '\"0.0.0.0\"')")
            ->where('a.createdAt < :before')
            ->setParameter('null', null)
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }

    // ========== ANALYTICS METHODS (Phase 6) ==========

    /**
     * Count events in the last hour
     */
    public function countInLastHour(): int
    {
        $oneHourAgo = new \DateTimeImmutable('-1 hour');

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :since')
            ->setParameter('since', $oneHourAgo)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find users with high volume of operations in a time window
     *
     * @return array<array{user_id: string, user_email: string, operation_count: int}>
     */
    public function findHighVolumeUsers(\DateTimeInterface $since, int $threshold): array
    {
        return $this->createQueryBuilder('a')
            ->select('IDENTITY(a.user) as user_id', 'COUNT(a.id) as operation_count')
            ->where('a.createdAt >= :since')
            ->andWhere('a.user IS NOT NULL')
            ->groupBy('a.user')
            ->having('COUNT(a.id) >= :threshold')
            ->orderBy('operation_count', 'DESC')
            ->setParameter('since', $since)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find entities that were rapidly changed
     *
     * @return array<array{entity_class: string, entity_id: string, change_count: int}>
     */
    public function findRapidlyChangingEntities(\DateTimeInterface $since, int $threshold): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.entityClass as entity_class', 'a.entityId as entity_id', 'COUNT(a.id) as change_count')
            ->where('a.createdAt >= :since')
            ->groupBy('a.entityClass', 'a.entityId')
            ->having('COUNT(a.id) >= :threshold')
            ->orderBy('change_count', 'DESC')
            ->setParameter('since', $since)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get hourly distribution of audit events for last 24 hours
     *
     * @return array<array{hour: string, count: int}>
     */
    public function getHourlyDistribution(): array
    {
        $oneDayAgo = new \DateTimeImmutable('-24 hours');

        $sql = '
            SELECT EXTRACT(HOUR FROM created_at) as hour, COUNT(id) as count
            FROM audit_log
            WHERE created_at >= :since
            GROUP BY hour
            ORDER BY hour ASC
        ';

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['since' => $oneDayAgo->format('Y-m-d H:i:s')]);

        return $result->fetchAllAssociative();
    }

    /**
     * Get top N most active users
     *
     * @return array<array{0: User, action_count: int}>
     */
    public function getTopActiveUsers(int $limit, ?\DateTimeInterface $since = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('u.id as user_id', 'u.email as user_email', 'COUNT(a.id) as action_count')
            ->join('a.user', 'u')
            ->where('a.user IS NOT NULL')
            ->groupBy('u.id', 'u.email')
            ->orderBy('action_count', 'DESC')
            ->setMaxResults($limit);

        if ($since) {
            $qb->andWhere('a.createdAt >= :since')
               ->setParameter('since', $since);
        }

        $results = $qb->getQuery()->getResult();

        // Hydrate users
        $userRepository = $this->getEntityManager()->getRepository(User::class);
        $hydrated = [];

        foreach ($results as $row) {
            $user = $userRepository->find($row['user_id']);
            if ($user) {
                $hydrated[] = [$user, 'action_count' => (int)$row['action_count']];
            }
        }

        return $hydrated;
    }

    /**
     * Get most modified entities by type
     *
     * @return array<array{entity_class: string, modification_count: int}>
     */
    public function getMostModifiedEntities(int $limit, ?\DateTimeInterface $since = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.entityClass as entity_class', 'COUNT(a.id) as modification_count')
            ->where("a.action IN ('entity_updated', 'entity_deleted')")
            ->groupBy('a.entityClass')
            ->orderBy('modification_count', 'DESC')
            ->setMaxResults($limit);

        if ($since) {
            $qb->andWhere('a.createdAt >= :since')
               ->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get action breakdown (created/updated/deleted counts)
     *
     * @return array<array{action: string, count: int}>
     */
    public function getActionBreakdown(?\DateTimeInterface $since = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.action', 'COUNT(a.id) as count')
            ->groupBy('a.action')
            ->orderBy('count', 'DESC');

        if ($since) {
            $qb->where('a.createdAt >= :since')
               ->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }
}
