<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LostReason;
use App\Repository\Generated\LostReasonRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LostReasonRepository
 *
 * Tracks and categorizes reasons for lost deals with advanced analytics capabilities. Supports win-loss analysis, competitor tracking, and actionable insights to improve win rates. Implements CRM best practices for structured data collection and longitudinal analysis.
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
 * @extends LostReasonRepositoryGenerated
 * @generated once by Genmax Code Generator
 */
final class LostReasonRepository extends LostReasonRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LostReason::class);
    }

    // Add custom query methods below
    // Example:
    //
    // /**
    //  * Find active lostreasons by category
    //  */
    // public function findActiveByCategory(Category $category): array
    // {
    //     return $this->createQueryBuilder('e')
    //         ->where('e.active = :active')
    //         ->andWhere('e.category = :category')
    //         ->setParameter('active', true)
    //         ->setParameter('category', $category)
    //         ->orderBy('e.createdAt', 'DESC')
    //         ->getQuery()
    //         ->getResult();
    // }
}
