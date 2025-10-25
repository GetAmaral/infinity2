<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CalendarTypeControllerGenerated;
use App\Entity\CalendarType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CalendarType Controller
 *
 * This controller handles all calendarType operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CalendarTypeControllerGenerated for available lifecycle hooks
 */
#[Route('/calendartype')]
final class CalendarTypeController extends CalendarTypeControllerGenerated
{
    /**
     * List all calendarTypes
     */
    #[Route('', name: 'calendartype_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching calendarTypes
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'calendartype_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new calendarType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'calendartype_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing calendarType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'calendartype_edit', methods: ['GET', 'POST'])]
    public function edit(CalendarType $calendarType, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($calendarType, $request);
        }

        return $this->editFormAction($calendarType, $request);
    }

    /**
     * Delete calendarType
     */
    #[Route('/{id}', name: 'calendartype_delete', methods: ['POST'])]
    public function delete(CalendarType $calendarType, Request $request): Response
    {
        return $this->deleteAction($calendarType, $request);
    }

    /**
     * Show calendarType details
     */
    #[Route('/{id}', name: 'calendartype_show', methods: ['GET'])]
    public function show(CalendarType $calendarType): Response
    {
        return $this->showAction($calendarType);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(CalendarType $calendarType): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($calendarType);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new CalendarTypeCreatedEvent($calendarType));
    // }
    //
    // protected function beforeDelete(CalendarType $calendarType): void
    // {
    //     // Check for dependencies
    //     // if ($calendarType->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete calendarType with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
