<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Data Transfer Object for API search parameters
 * Provides type-safe, validated search criteria
 */
final readonly class SearchCriteria
{
    public function __construct(
        public string $query = '',
        public int $page = 1,
        public int $limit = 10,
        public string $sortBy = 'id',
        public string $sortDir = 'asc',
        /** @var array<string, string> */
        public array $filters = [],
    ) {
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    public function isSearching(): bool
    {
        return !empty($this->query);
    }

    public function isDescending(): bool
    {
        return strtolower($this->sortDir) === 'desc';
    }

    public static function fromRequest(array $params): self
    {
        // Extract column filters from filter[field] parameters
        $filters = [];
        if (isset($params['filter']) && is_array($params['filter'])) {
            foreach ($params['filter'] as $field => $value) {
                if (is_string($value) && !empty(trim($value))) {
                    $filters[$field] = trim($value);
                }
            }
        }

        return new self(
            query: trim($params['q'] ?? ''),
            page: max(1, (int) ($params['page'] ?? 1)),
            limit: min(100, max(1, (int) ($params['limit'] ?? 10))),
            sortBy: $params['sortBy'] ?? 'id',
            sortDir: in_array(strtolower($params['sortDir'] ?? 'asc'), ['asc', 'desc'])
                ? strtolower($params['sortDir'])
                : 'asc',
            filters: $filters,
        );
    }
}