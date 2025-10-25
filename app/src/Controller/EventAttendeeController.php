<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\EventAttendeeControllerGenerated;
use App\Entity\EventAttendee;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * EventAttendee Controller
 *
 * This controller handles all eventAttendee operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see EventAttendeeControllerGenerated for available lifecycle hooks
 */
#[Route('/eventattendee')]
final class EventAttendeeController extends EventAttendeeControllerGenerated
{
    /**
     * List all eventAttendees
     */
    #[Route('', name: 'eventattendee_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching eventAttendees
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'eventattendee_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new eventAttendee
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'eventattendee_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing eventAttendee
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'eventattendee_edit', methods: ['GET', 'POST'])]
    public function edit(EventAttendee $eventAttendee, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($eventAttendee, $request);
        }

        return $this->editFormAction($eventAttendee, $request);
    }

    /**
     * Delete eventAttendee
     */
    #[Route('/{id}', name: 'eventattendee_delete', methods: ['POST'])]
    public function delete(EventAttendee $eventAttendee, Request $request): Response
    {
        return $this->deleteAction($eventAttendee, $request);
    }

    /**
     * Show eventAttendee details
     */
    #[Route('/{id}', name: 'eventattendee_show', methods: ['GET'])]
    public function show(EventAttendee $eventAttendee): Response
    {
        return $this->showAction($eventAttendee);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(EventAttendee $eventAttendee): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($eventAttendee);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new EventAttendeeCreatedEvent($eventAttendee));
    // }
    //
    // protected function beforeDelete(EventAttendee $eventAttendee): void
    // {
    //     // Check for dependencies
    //     // if ($eventAttendee->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete eventAttendee with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
