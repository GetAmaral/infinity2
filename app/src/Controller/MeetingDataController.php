<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\MeetingDataControllerGenerated;
use App\Entity\MeetingData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * MeetingData Controller
 *
 * This controller handles all meetingData operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see MeetingDataControllerGenerated for available lifecycle hooks
 */
#[Route('/meetingdata')]
final class MeetingDataController extends MeetingDataControllerGenerated
{
    /**
     * List all meetingDatas
     */
    #[Route('', name: 'meetingdata_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching meetingDatas
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'meetingdata_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new meetingData
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'meetingdata_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing meetingData
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'meetingdata_edit', methods: ['GET', 'POST'])]
    public function edit(MeetingData $meetingData, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($meetingData, $request);
        }

        return $this->editFormAction($meetingData, $request);
    }

    /**
     * Delete meetingData
     */
    #[Route('/{id}', name: 'meetingdata_delete', methods: ['POST'])]
    public function delete(MeetingData $meetingData, Request $request): Response
    {
        return $this->deleteAction($meetingData, $request);
    }

    /**
     * Show meetingData details
     */
    #[Route('/{id}', name: 'meetingdata_show', methods: ['GET'])]
    public function show(MeetingData $meetingData): Response
    {
        return $this->showAction($meetingData);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(MeetingData $meetingData): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($meetingData);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new MeetingDataCreatedEvent($meetingData));
    // }
    //
    // protected function beforeDelete(MeetingData $meetingData): void
    // {
    //     // Check for dependencies
    //     // if ($meetingData->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete meetingData with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
