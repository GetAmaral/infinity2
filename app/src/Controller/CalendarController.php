<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CalendarControllerGenerated;
use App\Entity\Calendar;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Calendar Controller
 *
 * This controller handles all calendar operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CalendarControllerGenerated for available lifecycle hooks
 */
#[Route('/calendar')]
final class CalendarController extends CalendarControllerGenerated
{
    /**
     * List all calendars
     */
    #[Route('', name: 'calendar_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching calendars
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'calendar_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new calendar
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'calendar_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing calendar
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'calendar_edit', methods: ['GET', 'POST'])]
    public function edit(Calendar $calendar, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($calendar, $request);
        }

        return $this->editFormAction($calendar, $request);
    }

    /**
     * Delete calendar
     */
    #[Route('/{id}', name: 'calendar_delete', methods: ['POST'])]
    public function delete(Calendar $calendar, Request $request): Response
    {
        return $this->deleteAction($calendar, $request);
    }

    /**
     * Show calendar details
     */
    #[Route('/{id}', name: 'calendar_show', methods: ['GET'])]
    public function show(Calendar $calendar): Response
    {
        return $this->showAction($calendar);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Calendar $calendar): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($calendar);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new CalendarCreatedEvent($calendar));
    // }
    //
    // protected function beforeDelete(Calendar $calendar): void
    // {
    //     // Check for dependencies
    //     // if ($calendar->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete calendar with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
