<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CountryControllerGenerated;
use App\Entity\Country;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Country Controller
 *
 * This controller handles all country operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CountryControllerGenerated for available lifecycle hooks
 */
#[Route('/country')]
final class CountryController extends CountryControllerGenerated
{
    /**
     * List all ries
     */
    #[Route('', name: 'country_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching ries
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'country_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new country
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'country_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing country
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'country_edit', methods: ['GET', 'POST'])]
    public function edit(Country $country, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($country, $request);
        }

        return $this->editFormAction($country, $request);
    }

    /**
     * Delete country
     */
    #[Route('/{id}', name: 'country_delete', methods: ['POST'])]
    public function delete(Country $country, Request $request): Response
    {
        return $this->deleteAction($country, $request);
    }

    /**
     * Show country details
     */
    #[Route('/{id}', name: 'country_show', methods: ['GET'])]
    public function show(Country $country): Response
    {
        return $this->showAction($country);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Country $country): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($country);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new CountryCreatedEvent($country));
    // }
    //
    // protected function beforeDelete(Country $country): void
    // {
    //     // Check for dependencies
    //     // if ($country->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete country with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
