<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Data Transfer Object for paginated API results
 *
 * @template T
 */
final readonly class PaginatedResult
{
    /**
     * @param array<T> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
        public string $sortBy,
        public string $sortDir,
    ) {
    }

    public function getTotalPages(): int
    {
        return $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 0;
    }

    public function toArray(callable $itemTransformer): array
    {
        return [
            'items' => array_map($itemTransformer, $this->items),
            'pagination' => [
                'page' => $this->page,
                'limit' => $this->limit,
                'total' => $this->total,
                'totalPages' => $this->getTotalPages(),
            ],
            'sort' => [
                'sortBy' => $this->sortBy,
                'sortDir' => $this->sortDir,
            ],
        ];
    }
}