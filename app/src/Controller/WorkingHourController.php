<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\WorkingHourControllerGenerated;
use App\Entity\WorkingHour;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * WorkingHour Controller
 *
 * This controller handles all workingHour operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see WorkingHourControllerGenerated for available lifecycle hooks
 */
#[Route('/workinghour')]
final class WorkingHourController extends WorkingHourControllerGenerated
{
    /**
     * List all workingHours
     */
    #[Route('', name: 'workinghour_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching workingHours
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'workinghour_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new workingHour
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'workinghour_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing workingHour
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'workinghour_edit', methods: ['GET', 'POST'])]
    public function edit(WorkingHour $workingHour, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($workingHour, $request);
        }

        return $this->editFormAction($workingHour, $request);
    }

    /**
     * Delete workingHour
     */
    #[Route('/{id}', name: 'workinghour_delete', methods: ['POST'])]
    public function delete(WorkingHour $workingHour, Request $request): Response
    {
        return $this->deleteAction($workingHour, $request);
    }

    /**
     * Show workingHour details
     */
    #[Route('/{id}', name: 'workinghour_show', methods: ['GET'])]
    public function show(WorkingHour $workingHour): Response
    {
        return $this->showAction($workingHour);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(WorkingHour $workingHour): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($workingHour);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new WorkingHourCreatedEvent($workingHour));
    // }
    //
    // protected function beforeDelete(WorkingHour $workingHour): void
    // {
    //     // Check for dependencies
    //     // if ($workingHour->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete workingHour with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
