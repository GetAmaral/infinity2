<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\EventCategoryControllerGenerated;
use App\Entity\EventCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * EventCategory Controller
 *
 * This controller handles all eventCategory operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see EventCategoryControllerGenerated for available lifecycle hooks
 */
#[Route('/eventcategory')]
final class EventCategoryController extends EventCategoryControllerGenerated
{
    /**
     * List all ries
     */
    #[Route('', name: 'eventcategory_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching ries
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'eventcategory_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new eventCategory
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'eventcategory_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing eventCategory
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'eventcategory_edit', methods: ['GET', 'POST'])]
    public function edit(EventCategory $eventCategory, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($eventCategory, $request);
        }

        return $this->editFormAction($eventCategory, $request);
    }

    /**
     * Delete eventCategory
     */
    #[Route('/{id}', name: 'eventcategory_delete', methods: ['POST'])]
    public function delete(EventCategory $eventCategory, Request $request): Response
    {
        return $this->deleteAction($eventCategory, $request);
    }

    /**
     * Show eventCategory details
     */
    #[Route('/{id}', name: 'eventcategory_show', methods: ['GET'])]
    public function show(EventCategory $eventCategory): Response
    {
        return $this->showAction($eventCategory);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(EventCategory $eventCategory): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($eventCategory);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new EventCategoryCreatedEvent($eventCategory));
    // }
    //
    // protected function beforeDelete(EventCategory $eventCategory): void
    // {
    //     // Check for dependencies
    //     // if ($eventCategory->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete eventCategory with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
