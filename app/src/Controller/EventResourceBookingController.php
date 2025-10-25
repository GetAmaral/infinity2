<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\EventResourceBookingControllerGenerated;
use App\Entity\EventResourceBooking;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * EventResourceBooking Controller
 *
 * This controller handles all eventResourceBooking operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see EventResourceBookingControllerGenerated for available lifecycle hooks
 */
#[Route('/eventresourcebooking')]
final class EventResourceBookingController extends EventResourceBookingControllerGenerated
{
    /**
     * List all eventResourceBookings
     */
    #[Route('', name: 'eventresourcebooking_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching eventResourceBookings
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'eventresourcebooking_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new eventResourceBooking
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'eventresourcebooking_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing eventResourceBooking
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'eventresourcebooking_edit', methods: ['GET', 'POST'])]
    public function edit(EventResourceBooking $eventResourceBooking, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($eventResourceBooking, $request);
        }

        return $this->editFormAction($eventResourceBooking, $request);
    }

    /**
     * Delete eventResourceBooking
     */
    #[Route('/{id}', name: 'eventresourcebooking_delete', methods: ['POST'])]
    public function delete(EventResourceBooking $eventResourceBooking, Request $request): Response
    {
        return $this->deleteAction($eventResourceBooking, $request);
    }

    /**
     * Show eventResourceBooking details
     */
    #[Route('/{id}', name: 'eventresourcebooking_show', methods: ['GET'])]
    public function show(EventResourceBooking $eventResourceBooking): Response
    {
        return $this->showAction($eventResourceBooking);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(EventResourceBooking $eventResourceBooking): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($eventResourceBooking);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new EventResourceBookingCreatedEvent($eventResourceBooking));
    // }
    //
    // protected function beforeDelete(EventResourceBooking $eventResourceBooking): void
    // {
    //     // Check for dependencies
    //     // if ($eventResourceBooking->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete eventResourceBooking with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
