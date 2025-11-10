<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\TimeZoneControllerGenerated;
use App\Entity\TimeZone;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * TimeZone Controller
 *
 * This controller handles all timeZone operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see TimeZoneControllerGenerated for available lifecycle hooks
 */
#[Route('/timezone')]
final class TimeZoneController extends TimeZoneControllerGenerated
{
    /**
     * List all timeZones
     */
    #[Route('', name: 'timezone_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching timeZones
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'timezone_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new timeZone
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'timezone_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing timeZone
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'timezone_edit', methods: ['GET', 'POST'])]
    public function edit(TimeZone $timeZone, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($timeZone, $request);
        }

        return $this->editFormAction($timeZone, $request);
    }

    /**
     * Delete timeZone
     */
    #[Route('/{id}', name: 'timezone_delete', methods: ['POST'])]
    public function delete(TimeZone $timeZone, Request $request): Response
    {
        return $this->deleteAction($timeZone, $request);
    }

    /**
     * Show timeZone details
     */
    #[Route('/{id}', name: 'timezone_show', methods: ['GET'])]
    public function show(TimeZone $timeZone): Response
    {
        return $this->showAction($timeZone);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(TimeZone $timeZone): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($timeZone);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new TimeZoneCreatedEvent($timeZone));
    // }
    //
    // protected function beforeDelete(TimeZone $timeZone): void
    // {
    //     // Check for dependencies
    //     // if ($timeZone->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete timeZone with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
