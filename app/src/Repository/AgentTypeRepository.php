<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AgentType;
use App\Repository\Generated\AgentTypeRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * AgentTypeRepository
 *
 * Agent types for customer support and sales teams
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
 * @extends AgentTypeRepositoryGenerated
 * @generated once by Genmax Code Generator
 */
final class AgentTypeRepository extends AgentTypeRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentType::class);
    }

    // Add custom query methods below
    // Example:
    //
    // /**
    //  * Find active agenttypes by category
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
