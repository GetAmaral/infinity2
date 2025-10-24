<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CalendarExternalLink;
use App\Repository\Generated\CalendarExternalLinkRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CalendarExternalLinkRepository
 *
 * Manages OAuth-based external calendar integrations (Google Calendar, Microsoft Outlook, Apple Calendar) with bi-directional sync, webhook support, and token refresh management
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
 * @extends CalendarExternalLinkRepositoryGenerated
 * @generated once by Genmax Code Generator
 */
final class CalendarExternalLinkRepository extends CalendarExternalLinkRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalendarExternalLink::class);
    }

    // Add custom query methods below
    // Example:
    //
    // /**
    //  * Find active calendarexternallinks by category
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
