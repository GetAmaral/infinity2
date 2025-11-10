<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\TaxCategoryControllerGenerated;
use App\Entity\TaxCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * TaxCategory Controller
 *
 * This controller handles all taxCategory operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see TaxCategoryControllerGenerated for available lifecycle hooks
 */
#[Route('/taxcategory')]
final class TaxCategoryController extends TaxCategoryControllerGenerated
{
    /**
     * List all ries
     */
    #[Route('', name: 'taxcategory_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching ries
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'taxcategory_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new taxCategory
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'taxcategory_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing taxCategory
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'taxcategory_edit', methods: ['GET', 'POST'])]
    public function edit(TaxCategory $taxCategory, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($taxCategory, $request);
        }

        return $this->editFormAction($taxCategory, $request);
    }

    /**
     * Delete taxCategory
     */
    #[Route('/{id}', name: 'taxcategory_delete', methods: ['POST'])]
    public function delete(TaxCategory $taxCategory, Request $request): Response
    {
        return $this->deleteAction($taxCategory, $request);
    }

    /**
     * Show taxCategory details
     */
    #[Route('/{id}', name: 'taxcategory_show', methods: ['GET'])]
    public function show(TaxCategory $taxCategory): Response
    {
        return $this->showAction($taxCategory);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(TaxCategory $taxCategory): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($taxCategory);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new TaxCategoryCreatedEvent($taxCategory));
    // }
    //
    // protected function beforeDelete(TaxCategory $taxCategory): void
    // {
    //     // Check for dependencies
    //     // if ($taxCategory->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete taxCategory with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
