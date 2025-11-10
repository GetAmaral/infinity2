<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\EventResourceControllerGenerated;
use App\Entity\EventResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * EventResource Controller
 *
 * This controller handles all eventResource operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see EventResourceControllerGenerated for available lifecycle hooks
 */
#[Route('/eventresource')]
final class EventResourceController extends EventResourceControllerGenerated
{
    /**
     * List all eventResources
     */
    #[Route('', name: 'eventresource_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching eventResources
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'eventresource_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new eventResource
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'eventresource_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing eventResource
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'eventresource_edit', methods: ['GET', 'POST'])]
    public function edit(EventResource $eventResource, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($eventResource, $request);
        }

        return $this->editFormAction($eventResource, $request);
    }

    /**
     * Delete eventResource
     */
    #[Route('/{id}', name: 'eventresource_delete', methods: ['POST'])]
    public function delete(EventResource $eventResource, Request $request): Response
    {
        return $this->deleteAction($eventResource, $request);
    }

    /**
     * Show eventResource details
     */
    #[Route('/{id}', name: 'eventresource_show', methods: ['GET'])]
    public function show(EventResource $eventResource): Response
    {
        return $this->showAction($eventResource);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(EventResource $eventResource): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($eventResource);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new EventResourceCreatedEvent($eventResource));
    // }
    //
    // protected function beforeDelete(EventResource $eventResource): void
    // {
    //     // Check for dependencies
    //     // if ($eventResource->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete eventResource with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
