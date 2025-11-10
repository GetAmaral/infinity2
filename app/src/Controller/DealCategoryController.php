<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\DealCategoryControllerGenerated;
use App\Entity\DealCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * DealCategory Controller
 *
 * This controller handles all dealCategory operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see DealCategoryControllerGenerated for available lifecycle hooks
 */
#[Route('/dealcategory')]
final class DealCategoryController extends DealCategoryControllerGenerated
{
    /**
     * List all ries
     */
    #[Route('', name: 'dealcategory_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching ries
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'dealcategory_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new dealCategory
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'dealcategory_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing dealCategory
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'dealcategory_edit', methods: ['GET', 'POST'])]
    public function edit(DealCategory $dealCategory, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($dealCategory, $request);
        }

        return $this->editFormAction($dealCategory, $request);
    }

    /**
     * Delete dealCategory
     */
    #[Route('/{id}', name: 'dealcategory_delete', methods: ['POST'])]
    public function delete(DealCategory $dealCategory, Request $request): Response
    {
        return $this->deleteAction($dealCategory, $request);
    }

    /**
     * Show dealCategory details
     */
    #[Route('/{id}', name: 'dealcategory_show', methods: ['GET'])]
    public function show(DealCategory $dealCategory): Response
    {
        return $this->showAction($dealCategory);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(DealCategory $dealCategory): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($dealCategory);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new DealCategoryCreatedEvent($dealCategory));
    // }
    //
    // protected function beforeDelete(DealCategory $dealCategory): void
    // {
    //     // Check for dependencies
    //     // if ($dealCategory->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete dealCategory with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
