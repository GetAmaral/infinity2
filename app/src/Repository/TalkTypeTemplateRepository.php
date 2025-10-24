<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TalkTypeTemplate;
use App\Repository\Generated\TalkTypeTemplateRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TalkTypeTemplateRepository
 *
 * Templates for communication types (Call, Email, Meeting, etc.)
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
 * @extends TalkTypeTemplateRepositoryGenerated
 * @generated once by Genmax Code Generator
 */
final class TalkTypeTemplateRepository extends TalkTypeTemplateRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TalkTypeTemplate::class);
    }

    // Add custom query methods below
    // Example:
    //
    // /**
    //  * Find active talktypetemplates by category
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
