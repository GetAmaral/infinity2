<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TalkType;
use App\Repository\Generated\TalkTypeRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TalkTypeRepository
 *
 * Communication types for the organization
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
 * @extends TalkTypeRepositoryGenerated
 * @generated once by Genmax Code Generator
 */
final class TalkTypeRepository extends TalkTypeRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TalkType::class);
    }

    // Add custom query methods below
    // Example:
    //
    // /**
    //  * Find active talktypes by category
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
