<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\EventControllerGenerated;
use App\Entity\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Event Controller
 *
 * This controller handles all event operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see EventControllerGenerated for available lifecycle hooks
 */
#[Route('/event')]
final class EventController extends EventControllerGenerated
{
    /**
     * List all events
     */
    #[Route('', name: 'event_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching events
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'event_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new event
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing event
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'])]
    public function edit(Event $event, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($event, $request);
        }

        return $this->editFormAction($event, $request);
    }

    /**
     * Delete event
     */
    #[Route('/{id}', name: 'event_delete', methods: ['POST'])]
    public function delete(Event $event, Request $request): Response
    {
        return $this->deleteAction($event, $request);
    }

    /**
     * Show event details
     */
    #[Route('/{id}', name: 'event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->showAction($event);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Event $event): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($event);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new EventCreatedEvent($event));
    // }
    //
    // protected function beforeDelete(Event $event): void
    // {
    //     // Check for dependencies
    //     // if ($event->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete event with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
