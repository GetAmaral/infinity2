<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\SearchCriteria;
use App\DTO\PaginatedResult;
use App\Repository\BaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base API Controller
 *
 * Provides common REST API patterns for all entity controllers
 * Implements DRY principle for API endpoints
 *
 * @template T of object
 */
abstract class BaseApiController extends AbstractController
{
    /**
     * Get the repository for this controller's entity
     * Child controllers must implement this
     *
     * @return BaseRepository<T>
     */
    abstract protected function getRepository(): BaseRepository;

    /**
     * Get the plural entity name for JSON response key
     * e.g., "organizations", "users"
     */
    abstract protected function getEntityPluralName(): string;

    /**
     * Transform entity to array for JSON response
     * Child controllers must implement entity-specific serialization
     *
     * @param T $entity
     * @return array<string, mixed>
     */
    abstract protected function entityToArray(object $entity): array;

    /**
     * Common API search endpoint
     * Handles search, filtering, sorting, and pagination
     * All logic delegated to repository
     */
    protected function apiSearchAction(Request $request): JsonResponse
    {
        try {
            // Parse and validate search criteria from request
            $criteria = SearchCriteria::fromRequest($request->query->all());

            // Delegate to repository - all business logic there
            $result = $this->getRepository()->apiSearch($criteria);

            // Transform entities to arrays
            $responseData = $result->toArray(
                fn($entity) => $this->entityToArray($entity)
            );

            // Rename 'items' to entity-specific key
            $entityKey = $this->getEntityPluralName();
            $responseData[$entityKey] = $responseData['items'];
            unset($responseData['items']);

            return $this->json($responseData);

        } catch (\Exception $e) {
            // In dev mode, return full error details
            if ($_SERVER['APP_ENV'] === 'dev') {
                return $this->json([
                    'error' => 'Search failed',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return $this->json([
                'error' => 'Search failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Helper: Create success response
     */
    protected function jsonSuccess(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($data, $status);
    }

    /**
     * Helper: Create error response
     */
    protected function jsonError(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->json([
            'error' => true,
            'message' => $message,
        ], $status);
    }
}