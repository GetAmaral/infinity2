<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\HolidayControllerGenerated;
use App\Entity\Holiday;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Holiday Controller
 *
 * This controller handles all holiday operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see HolidayControllerGenerated for available lifecycle hooks
 */
#[Route('/holiday')]
final class HolidayController extends HolidayControllerGenerated
{
    /**
     * List all holidays
     */
    #[Route('', name: 'holiday_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching holidays
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'holiday_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new holiday
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'holiday_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing holiday
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'holiday_edit', methods: ['GET', 'POST'])]
    public function edit(Holiday $holiday, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($holiday, $request);
        }

        return $this->editFormAction($holiday, $request);
    }

    /**
     * Delete holiday
     */
    #[Route('/{id}', name: 'holiday_delete', methods: ['POST'])]
    public function delete(Holiday $holiday, Request $request): Response
    {
        return $this->deleteAction($holiday, $request);
    }

    /**
     * Show holiday details
     */
    #[Route('/{id}', name: 'holiday_show', methods: ['GET'])]
    public function show(Holiday $holiday): Response
    {
        return $this->showAction($holiday);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Holiday $holiday): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($holiday);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new HolidayCreatedEvent($holiday));
    // }
    //
    // protected function beforeDelete(Holiday $holiday): void
    // {
    //     // Check for dependencies
    //     // if ($holiday->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete holiday with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
