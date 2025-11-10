<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\ProductCategoryControllerGenerated;
use App\Entity\ProductCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ProductCategory Controller
 *
 * This controller handles all productCategory operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see ProductCategoryControllerGenerated for available lifecycle hooks
 */
#[Route('/productcategory')]
final class ProductCategoryController extends ProductCategoryControllerGenerated
{
    /**
     * List all ries
     */
    #[Route('', name: 'productcategory_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching ries
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'productcategory_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new productCategory
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'productcategory_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing productCategory
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'productcategory_edit', methods: ['GET', 'POST'])]
    public function edit(ProductCategory $productCategory, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($productCategory, $request);
        }

        return $this->editFormAction($productCategory, $request);
    }

    /**
     * Delete productCategory
     */
    #[Route('/{id}', name: 'productcategory_delete', methods: ['POST'])]
    public function delete(ProductCategory $productCategory, Request $request): Response
    {
        return $this->deleteAction($productCategory, $request);
    }

    /**
     * Show productCategory details
     */
    #[Route('/{id}', name: 'productcategory_show', methods: ['GET'])]
    public function show(ProductCategory $productCategory): Response
    {
        return $this->showAction($productCategory);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(ProductCategory $productCategory): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($productCategory);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new ProductCategoryCreatedEvent($productCategory));
    // }
    //
    // protected function beforeDelete(ProductCategory $productCategory): void
    // {
    //     // Check for dependencies
    //     // if ($productCategory->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete productCategory with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
