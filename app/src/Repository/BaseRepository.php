<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\SearchCriteria;
use App\DTO\PaginatedResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Base Repository with common API search functionality
 *
 * Provides:
 * - Full-text search with UNACCENT and LIKE
 * - Multi-column sorting
 * - Pagination with metadata
 * - DRY principle for all entity repositories
 *
 * @template T of object
 * @extends ServiceEntityRepository<T>
 */
abstract class BaseRepository extends ServiceEntityRepository
{
    /**
     * Define which fields are searchable for this entity
     * Override in child repositories
     *
     * @return array<string> Field names to search (e.g., ['name', 'description'])
     */
    abstract protected function getSearchableFields(): array;

    /**
     * Define valid sortable fields and their mapping
     * Override in child repositories
     *
     * @return array<string, string> Map of API field names to entity properties
     */
    abstract protected function getSortableFields(): array;

    /**
     * Main API search method - handles search, sort, and pagination
     *
     * @return PaginatedResult<T>
     */
    public function apiSearch(SearchCriteria $criteria): PaginatedResult
    {
        // Create base query
        $qb = $this->createQueryBuilder('e');

        // Apply search filter if provided
        if ($criteria->isSearching()) {
            $this->applySearchFilter($qb, $criteria->query);
        }

        // Get total count before pagination
        $countQb = clone $qb;
        $countQb->select('COUNT(e.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Apply sorting
        $this->applySorting($qb, $criteria->sortBy, $criteria->sortDir);

        // Apply pagination
        $qb->setFirstResult($criteria->getOffset())
           ->setMaxResults($criteria->limit);

        // Execute and return results
        $items = $qb->getQuery()->getResult();

        return new PaginatedResult(
            items: $items,
            total: $total,
            page: $criteria->page,
            limit: $criteria->limit,
            sortBy: $criteria->sortBy,
            sortDir: $criteria->sortDir,
        );
    }

    /**
     * Apply full-text search with UNACCENT and LIKE
     * Searches across all searchable fields defined by entity
     *
     * Uses custom DQL UNACCENT function for accent-insensitive search
     * Registered in config/packages/doctrine.yaml
     */
    protected function applySearchFilter(QueryBuilder $qb, string $searchTerm): void
    {
        $searchableFields = $this->getSearchableFields();

        if (empty($searchableFields)) {
            return;
        }

        // Normalize search term (remove accents for consistent matching)
        $normalizedTerm = $this->normalizeSearchTerm($searchTerm);

        // Build OR conditions for each searchable field using DQL
        $orX = $qb->expr()->orX();

        foreach ($searchableFields as $index => $field) {
            // Use LOWER + UNACCENT for accent and case insensitive search
            // UNACCENT is a custom DQL function mapped to PostgreSQL unaccent()
            $orX->add(
                $qb->expr()->like(
                    "LOWER(UNACCENT(e.{$field}))",
                    ":searchTerm{$index}"
                )
            );
            $qb->setParameter("searchTerm{$index}", '%' . strtolower($normalizedTerm) . '%');
        }

        $qb->andWhere($orX);
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting(QueryBuilder $qb, string $sortBy, string $sortDir): void
    {
        $sortableFields = $this->getSortableFields();

        // Validate sort field exists in allowed list
        if (!isset($sortableFields[$sortBy])) {
            // Fallback to default sort
            $sortBy = array_key_first($sortableFields) ?? 'id';
        }

        $entityProperty = $sortableFields[$sortBy];
        $direction = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

        $qb->orderBy("e.{$entityProperty}", $direction);
    }

    /**
     * Normalize search term by removing accents
     * Uses PHP iconv as fallback for client-side processing
     */
    protected function normalizeSearchTerm(string $term): string
    {
        // Remove accents using transliteration
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $term);
        return $normalized ?: $term;
    }

    /**
     * Get all entities with optional eager loading
     *
     * @param array<string> $relations Relations to eager load
     * @return array<T>
     */
    public function findAllWithRelations(array $relations = []): array
    {
        $qb = $this->createQueryBuilder('e');

        foreach ($relations as $relation) {
            $qb->leftJoin("e.{$relation}", $relation)
               ->addSelect($relation);
        }

        return $qb->getQuery()->getResult();
    }
}