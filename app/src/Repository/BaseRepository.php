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
     * Define valid filterable fields and their mapping
     * Override in child repositories to exclude computed/aggregate fields
     * Defaults to all sortable fields
     *
     * @return array<string, string> Map of API field names to entity properties
     */
    protected function getFilterableFields(): array
    {
        return $this->getSortableFields();
    }

    /**
     * Define relationship filter mappings
     * Maps API field names to relationship paths with searchable fields
     * Example: 'userCount' => ['relation' => 'users', 'field' => 'name']
     *
     * @return array<string, array{relation: string, field: string}>
     */
    protected function getRelationshipFilterFields(): array
    {
        return [];
    }

    /**
     * Define boolean fields that should use equality instead of LIKE
     * Example: ['active', 'isVerified', 'isLocked']
     *
     * @return array<string>
     */
    protected function getBooleanFilterFields(): array
    {
        return [];
    }

    /**
     * Define date/datetime fields that should use range filtering
     * Example: ['createdAt', 'updatedAt', 'lastLoginAt', 'releaseDate']
     *
     * @return array<string>
     */
    protected function getDateFilterFields(): array
    {
        return [];
    }

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

        // Apply column filters if provided
        if (!empty($criteria->filters)) {
            $this->applyColumnFilters($qb, $criteria->filters);
        }

        // Get total count before pagination
        // Use DISTINCT to handle JOIN duplicates properly
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT e)');
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
     * Apply column-specific filters to query
     * Filters specific columns with exact or partial match
     * Supports both direct fields and relationship fields
     *
     * @param array<string, string> $filters Map of field names to filter values
     */
    protected function applyColumnFilters(QueryBuilder $qb, array $filters): void
    {
        $filterableFields = $this->getFilterableFields();
        $relationshipFilters = $this->getRelationshipFilterFields();
        $booleanFields = $this->getBooleanFilterFields();
        $dateFields = $this->getDateFilterFields();

        foreach ($filters as $field => $value) {
            // Check if this is a relationship filter
            if (isset($relationshipFilters[$field])) {
                $this->applyRelationshipFilter($qb, $field, $value, $relationshipFilters[$field]);
                continue;
            }

            // Only apply filters for valid filterable fields
            if (!isset($filterableFields[$field])) {
                continue;
            }

            $entityProperty = $filterableFields[$field];
            $paramName = 'filter_' . str_replace('.', '_', $field);

            // Handle boolean fields with equality
            if (in_array($field, $booleanFields, true)) {
                $boolValue = $this->parseBooleanValue($value);
                if ($boolValue !== null) {
                    $qb->andWhere("e.{$entityProperty} = :{$paramName}");
                    $qb->setParameter($paramName, $boolValue);
                }
                continue;
            }

            // Handle date range fields
            if (in_array($field, $dateFields, true)) {
                $this->applyDateRangeFilter($qb, $entityProperty, $paramName, $value);
                continue;
            }

            // Use LOWER + UNACCENT for accent and case insensitive filtering
            $qb->andWhere(
                $qb->expr()->like(
                    "LOWER(UNACCENT(e.{$entityProperty}))",
                    ":{$paramName}"
                )
            );
            $qb->setParameter($paramName, '%' . strtolower($this->normalizeSearchTerm($value)) . '%');
        }
    }

    /**
     * Apply date range filter to query
     * Expects value in format "from:to" where from/to are ISO date strings (YYYY-MM-DD)
     * Either from or to can be empty for open-ended ranges
     */
    protected function applyDateRangeFilter(QueryBuilder $qb, string $entityProperty, string $paramName, string $value): void
    {
        // Parse date range format "from:to"
        $parts = explode(':', $value);
        $from = $parts[0] ?? '';
        $to = $parts[1] ?? '';

        // If both are empty, no filter needed
        if (empty($from) && empty($to)) {
            return;
        }

        // Apply from date (>=)
        if (!empty($from)) {
            try {
                $fromDate = new \DateTime($from);
                $fromDate->setTime(0, 0, 0); // Start of day
                $qb->andWhere("e.{$entityProperty} >= :{$paramName}_from");
                $qb->setParameter("{$paramName}_from", $fromDate);
            } catch (\Exception $e) {
                // Invalid date format, skip this filter
            }
        }

        // Apply to date (<=)
        if (!empty($to)) {
            try {
                $toDate = new \DateTime($to);
                $toDate->setTime(23, 59, 59); // End of day
                $qb->andWhere("e.{$entityProperty} <= :{$paramName}_to");
                $qb->setParameter("{$paramName}_to", $toDate);
            } catch (\Exception $e) {
                // Invalid date format, skip this filter
            }
        }
    }

    /**
     * Parse boolean value from user input
     * Accepts: true/false, 1/0, yes/no, y/n, active/inactive
     */
    protected function parseBooleanValue(string $value): ?bool
    {
        $normalized = strtolower(trim($value));

        if (in_array($normalized, ['true', '1', 'yes', 'y', 'active'], true)) {
            return true;
        }

        if (in_array($normalized, ['false', '0', 'no', 'n', 'inactive'], true)) {
            return false;
        }

        return null;
    }

    /**
     * Apply filter on a relationship field
     *
     * @param array{relation: string, field: string} $config
     */
    protected function applyRelationshipFilter(QueryBuilder $qb, string $fieldName, string $value, array $config): void
    {
        $relation = $config['relation'];
        $relationField = $config['field'];
        $alias = 'rel_' . $relation;
        $paramName = 'filter_' . str_replace('.', '_', $fieldName);

        // Check if join already exists
        $existingJoins = $qb->getDQLPart('join');
        $joinExists = false;
        if (isset($existingJoins['e'])) {
            foreach ($existingJoins['e'] as $join) {
                if ($join->getAlias() === $alias) {
                    $joinExists = true;
                    break;
                }
            }
        }

        // Add join if not exists
        if (!$joinExists) {
            $qb->leftJoin("e.{$relation}", $alias);
        }

        // Apply filter on relationship field
        $qb->andWhere(
            $qb->expr()->like(
                "LOWER(UNACCENT({$alias}.{$relationField}))",
                ":{$paramName}"
            )
        );
        $qb->setParameter($paramName, '%' . strtolower($this->normalizeSearchTerm($value)) . '%');
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