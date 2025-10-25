<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\ProductBatchControllerGenerated;
use App\Entity\ProductBatch;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ProductBatch Controller
 *
 * This controller handles all productBatch operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see ProductBatchControllerGenerated for available lifecycle hooks
 */
#[Route('/productbatch')]
final class ProductBatchController extends ProductBatchControllerGenerated
{
    /**
     * List all productBatches
     */
    #[Route('', name: 'productbatch_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching productBatches
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'productbatch_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new productBatch
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'productbatch_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing productBatch
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'productbatch_edit', methods: ['GET', 'POST'])]
    public function edit(ProductBatch $productBatch, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($productBatch, $request);
        }

        return $this->editFormAction($productBatch, $request);
    }

    /**
     * Delete productBatch
     */
    #[Route('/{id}', name: 'productbatch_delete', methods: ['POST'])]
    public function delete(ProductBatch $productBatch, Request $request): Response
    {
        return $this->deleteAction($productBatch, $request);
    }

    /**
     * Show productBatch details
     */
    #[Route('/{id}', name: 'productbatch_show', methods: ['GET'])]
    public function show(ProductBatch $productBatch): Response
    {
        return $this->showAction($productBatch);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(ProductBatch $productBatch): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($productBatch);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new ProductBatchCreatedEvent($productBatch));
    // }
    //
    // protected function beforeDelete(ProductBatch $productBatch): void
    // {
    //     // Check for dependencies
    //     // if ($productBatch->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete productBatch with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
