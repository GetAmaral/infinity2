<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BillingFrequency;
use App\Repository\Generated\BillingFrequencyRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BillingFrequencyRepository
 *
 * Defines billing frequency options for subscriptions (Daily, Weekly, Biweekly, Monthly, Quarterly, Semi-Annual, Annual, Biennial). Controls recurring billing intervals with support for custom cycles and discount management.
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
 * @extends BillingFrequencyRepositoryGenerated
 * @generated once by Genmax Code Generator
 */
final class BillingFrequencyRepository extends BillingFrequencyRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BillingFrequency::class);
    }

    // Add custom query methods below
    // Example:
    //
    // /**
    //  * Find active billingfrequencys by category
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
