<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\ProductLineControllerGenerated;
use App\Entity\ProductLine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ProductLine Controller
 *
 * This controller handles all productLine operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see ProductLineControllerGenerated for available lifecycle hooks
 */
#[Route('/productline')]
final class ProductLineController extends ProductLineControllerGenerated
{
    /**
     * List all productLines
     */
    #[Route('', name: 'productline_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching productLines
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'productline_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new productLine
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'productline_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing productLine
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'productline_edit', methods: ['GET', 'POST'])]
    public function edit(ProductLine $productLine, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($productLine, $request);
        }

        return $this->editFormAction($productLine, $request);
    }

    /**
     * Delete productLine
     */
    #[Route('/{id}', name: 'productline_delete', methods: ['POST'])]
    public function delete(ProductLine $productLine, Request $request): Response
    {
        return $this->deleteAction($productLine, $request);
    }

    /**
     * Show productLine details
     */
    #[Route('/{id}', name: 'productline_show', methods: ['GET'])]
    public function show(ProductLine $productLine): Response
    {
        return $this->showAction($productLine);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(ProductLine $productLine): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($productLine);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new ProductLineCreatedEvent($productLine));
    // }
    //
    // protected function beforeDelete(ProductLine $productLine): void
    // {
    //     // Check for dependencies
    //     // if ($productLine->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete productLine with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
