<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Deal;
use App\Entity\PipelineStage;
use App\Repository\Generated\DealRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * DealRepository
 *
 * Sales opportunities and deals tracking
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom query methods, eager loading, and business logic here.
 *
 * Examples of what to add here:
 * - Custom find methods (findActiveByCategory, findRecentOrders, etc.)
 * - Complex queries with joins
 * - Eager loading strategies
 * - Custom aggregations
 * - Business-specific filters
 *
 * DO NOT modify getSearchableFields(), getSortableFields(), etc.
 * Those are managed in the generated parent class.
 *
 * @extends DealRepositoryGenerated
 * @generated once by Genmax Code Generator
 */
final class DealRepository extends DealRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deal::class);
    }

    /**
     * Find deals by stage with optional filters
     */
    public function findByStageWithFilters(PipelineStage $stage, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.currentStage = :stage')
            ->andWhere('d.dealStatus = :active')
            ->setParameter('stage', $stage)
            ->setParameter('active', 'active')
            ->orderBy('d.createdAt', 'DESC');

        // Apply filters
        if (!empty($filters['owner'])) {
            $qb->andWhere('d.owner = :owner')
               ->setParameter('owner', $filters['owner']);
        }

        if (isset($filters['minAmount']) && $filters['minAmount'] !== '') {
            $qb->andWhere('d.expectedAmount >= :minAmount')
               ->setParameter('minAmount', $filters['minAmount']);
        }

        if (isset($filters['maxAmount']) && $filters['maxAmount'] !== '') {
            $qb->andWhere('d.expectedAmount <= :maxAmount')
               ->setParameter('maxAmount', $filters['maxAmount']);
        }

        if (!empty($filters['tags'])) {
            $qb->join('d.tags', 't')
               ->andWhere('t.id IN (:tags)')
               ->setParameter('tags', $filters['tags']);
        }

        // AI-based filters
        if (!empty($filters['aiScore'])) {
            switch ($filters['aiScore']) {
                case 'hot':
                    $qb->andWhere('d.aiScore >= 75');
                    break;
                case 'at_risk':
                    $qb->andWhere('d.aiScore < 50');
                    break;
                case 'stale':
                    $qb->andWhere('d.daysStale > 30');
                    break;
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find deals needing AI analysis
     */
    public function findNeedingAiAnalysis(int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.dealStatus = :active')
            ->setParameter('active', 'active')
            ->andWhere(
                $qb->expr()->orX(
                    'd.lastAiAnalysisAt IS NULL',
                    'd.lastAiAnalysisAt < :threshold'
                )
            )
            ->setParameter('threshold', new \DateTimeImmutable('-1 day'))
            ->setMaxResults($limit)
            ->orderBy('d.expectedAmount', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get deals by AI score range
     */
    public function findByAiScoreRange(int $min, int $max): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.aiScore >= :min')
            ->andWhere('d.aiScore <= :max')
            ->setParameter('min', $min)
            ->setParameter('max', $max)
            ->orderBy('d.aiScore', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
