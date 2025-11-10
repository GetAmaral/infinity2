<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CompetitorControllerGenerated;
use App\Entity\Competitor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Competitor Controller
 *
 * This controller handles all competitor operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CompetitorControllerGenerated for available lifecycle hooks
 */
#[Route('/competitor')]
final class CompetitorController extends CompetitorControllerGenerated
{
    /**
     * List all competitors
     */
    #[Route('', name: 'competitor_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching competitors
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'competitor_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new competitor
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'competitor_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing competitor
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'competitor_edit', methods: ['GET', 'POST'])]
    public function edit(Competitor $competitor, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($competitor, $request);
        }

        return $this->editFormAction($competitor, $request);
    }

    /**
     * Delete competitor
     */
    #[Route('/{id}', name: 'competitor_delete', methods: ['POST'])]
    public function delete(Competitor $competitor, Request $request): Response
    {
        return $this->deleteAction($competitor, $request);
    }

    /**
     * Show competitor details
     */
    #[Route('/{id}', name: 'competitor_show', methods: ['GET'])]
    public function show(Competitor $competitor): Response
    {
        return $this->showAction($competitor);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Competitor $competitor): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($competitor);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new CompetitorCreatedEvent($competitor));
    // }
    //
    // protected function beforeDelete(Competitor $competitor): void
    // {
    //     // Check for dependencies
    //     // if ($competitor->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete competitor with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
