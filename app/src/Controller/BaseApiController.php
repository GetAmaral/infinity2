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

    /**
     * Get the singular entity name for routes (e.g., "user", "organization")
     * Override in child controllers if needed
     */
    protected function getEntitySingularName(): string
    {
        // Default: remove trailing 's' from plural name
        $plural = $this->getEntityPluralName();
        return rtrim($plural, 's');
    }

    /**
     * Smart redirect based on referer
     * Supports multiple redirect patterns:
     * - Entity show page: /entity/{id}
     * - Organization users page: /organization/{id}/users
     * - Default route fallback
     *
     * Child controllers can override getRedirectPatterns() to add custom patterns
     */
    protected function redirectToRefererOrRoute(Request $request, string $defaultRoute): Response
    {
        $referer = $request->headers->get('referer');

        if (!$referer) {
            return $this->redirectToRoute($defaultRoute, [], Response::HTTP_SEE_OTHER);
        }

        // Get entity-specific patterns
        $patterns = $this->getRedirectPatterns();

        // Check each pattern
        foreach ($patterns as $pattern => $routeInfo) {
            if (preg_match($pattern, $referer, $matches)) {
                $params = [];
                // Build parameters from matches
                if (isset($routeInfo['params'])) {
                    foreach ($routeInfo['params'] as $paramName => $matchIndex) {
                        $params[$paramName] = $matches[$matchIndex];
                    }
                }
                return $this->redirectToRoute($routeInfo['route'], $params, Response::HTTP_SEE_OTHER);
            }
        }

        // Default redirect
        return $this->redirectToRoute($defaultRoute, [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Get redirect patterns for referer-based routing
     * Returns array of regex patterns => route config
     *
     * Override in child controllers to add custom patterns
     *
     * @return array<string, array{route: string, params?: array<string, int>}>
     */
    protected function getRedirectPatterns(): array
    {
        $entityName = $this->getEntitySingularName();

        return [
            // Entity show page: /entity/{id}
            '#/' . $entityName . '/([0-9a-f-]+)$#' => [
                'route' => $entityName . '_show',
                'params' => ['id' => 1]
            ],
            // Organization users page: /organization/{id}/users
            '#/organization/([0-9a-f-]+)/users#' => [
                'route' => 'organization_users',
                'params' => ['id' => 1]
            ],
        ];
    }
}