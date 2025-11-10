<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\DealControllerGenerated;
use App\Entity\Deal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Deal Controller
 *
 * This controller handles all deal operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see DealControllerGenerated for available lifecycle hooks
 */
#[Route('/deal')]
final class DealController extends DealControllerGenerated
{
    /**
     * List all deals
     */
    #[Route('', name: 'deal_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching deals
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'deal_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new deal
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'deal_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing deal
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'deal_edit', methods: ['GET', 'POST'])]
    public function edit(Deal $deal, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($deal, $request);
        }

        return $this->editFormAction($deal, $request);
    }

    /**
     * Delete deal
     */
    #[Route('/{id}', name: 'deal_delete', methods: ['POST'])]
    public function delete(Deal $deal, Request $request): Response
    {
        return $this->deleteAction($deal, $request);
    }

    /**
     * Show deal details
     */
    #[Route('/{id}', name: 'deal_show', methods: ['GET'])]
    public function show(Deal $deal): Response
    {
        return $this->showAction($deal);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Deal $deal): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($deal);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new DealCreatedEvent($deal));
    // }
    //
    // protected function beforeDelete(Deal $deal): void
    // {
    //     // Check for dependencies
    //     // if ($deal->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete deal with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
