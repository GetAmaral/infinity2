<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CityControllerGenerated;
use App\Entity\City;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * City Controller
 *
 * This controller handles all city operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CityControllerGenerated for available lifecycle hooks
 */
#[Route('/city')]
final class CityController extends CityControllerGenerated
{
    /**
     * List all ties
     */
    #[Route('', name: 'city_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching ties
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'city_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new city
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'city_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing city
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'city_edit', methods: ['GET', 'POST'])]
    public function edit(City $city, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($city, $request);
        }

        return $this->editFormAction($city, $request);
    }

    /**
     * Delete city
     */
    #[Route('/{id}', name: 'city_delete', methods: ['POST'])]
    public function delete(City $city, Request $request): Response
    {
        return $this->deleteAction($city, $request);
    }

    /**
     * Show city details
     */
    #[Route('/{id}', name: 'city_show', methods: ['GET'])]
    public function show(City $city): Response
    {
        return $this->showAction($city);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(City $city): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($city);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new CityCreatedEvent($city));
    // }
    //
    // protected function beforeDelete(City $city): void
    // {
    //     // Check for dependencies
    //     // if ($city->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete city with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
