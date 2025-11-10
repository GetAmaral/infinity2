<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\DealTypeControllerGenerated;
use App\Entity\DealType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * DealType Controller
 *
 * This controller handles all dealType operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see DealTypeControllerGenerated for available lifecycle hooks
 */
#[Route('/dealtype')]
final class DealTypeController extends DealTypeControllerGenerated
{
    /**
     * List all dealTypes
     */
    #[Route('', name: 'dealtype_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching dealTypes
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'dealtype_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new dealType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'dealtype_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing dealType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'dealtype_edit', methods: ['GET', 'POST'])]
    public function edit(DealType $dealType, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($dealType, $request);
        }

        return $this->editFormAction($dealType, $request);
    }

    /**
     * Delete dealType
     */
    #[Route('/{id}', name: 'dealtype_delete', methods: ['POST'])]
    public function delete(DealType $dealType, Request $request): Response
    {
        return $this->deleteAction($dealType, $request);
    }

    /**
     * Show dealType details
     */
    #[Route('/{id}', name: 'dealtype_show', methods: ['GET'])]
    public function show(DealType $dealType): Response
    {
        return $this->showAction($dealType);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(DealType $dealType): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($dealType);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new DealTypeCreatedEvent($dealType));
    // }
    //
    // protected function beforeDelete(DealType $dealType): void
    // {
    //     // Check for dependencies
    //     // if ($dealType->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete dealType with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
