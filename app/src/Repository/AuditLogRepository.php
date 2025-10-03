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
}
