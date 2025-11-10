<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\BrandControllerGenerated;
use App\Entity\Brand;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Brand Controller
 *
 * This controller handles all brand operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see BrandControllerGenerated for available lifecycle hooks
 */
#[Route('/brand')]
final class BrandController extends BrandControllerGenerated
{
    /**
     * List all brands
     */
    #[Route('', name: 'brand_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching brands
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'brand_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new brand
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'brand_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing brand
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'brand_edit', methods: ['GET', 'POST'])]
    public function edit(Brand $brand, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($brand, $request);
        }

        return $this->editFormAction($brand, $request);
    }

    /**
     * Delete brand
     */
    #[Route('/{id}', name: 'brand_delete', methods: ['POST'])]
    public function delete(Brand $brand, Request $request): Response
    {
        return $this->deleteAction($brand, $request);
    }

    /**
     * Show brand details
     */
    #[Route('/{id}', name: 'brand_show', methods: ['GET'])]
    public function show(Brand $brand): Response
    {
        return $this->showAction($brand);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Brand $brand): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($brand);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new BrandCreatedEvent($brand));
    // }
    //
    // protected function beforeDelete(Brand $brand): void
    // {
    //     // Check for dependencies
    //     // if ($brand->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete brand with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
