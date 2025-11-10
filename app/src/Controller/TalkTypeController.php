<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\TalkTypeControllerGenerated;
use App\Entity\TalkType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * TalkType Controller
 *
 * This controller handles all talkType operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see TalkTypeControllerGenerated for available lifecycle hooks
 */
#[Route('/talktype')]
final class TalkTypeController extends TalkTypeControllerGenerated
{
    /**
     * List all talkTypes
     */
    #[Route('', name: 'talktype_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching talkTypes
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'talktype_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new talkType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'talktype_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing talkType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'talktype_edit', methods: ['GET', 'POST'])]
    public function edit(TalkType $talkType, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($talkType, $request);
        }

        return $this->editFormAction($talkType, $request);
    }

    /**
     * Delete talkType
     */
    #[Route('/{id}', name: 'talktype_delete', methods: ['POST'])]
    public function delete(TalkType $talkType, Request $request): Response
    {
        return $this->deleteAction($talkType, $request);
    }

    /**
     * Show talkType details
     */
    #[Route('/{id}', name: 'talktype_show', methods: ['GET'])]
    public function show(TalkType $talkType): Response
    {
        return $this->showAction($talkType);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(TalkType $talkType): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($talkType);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new TalkTypeCreatedEvent($talkType));
    // }
    //
    // protected function beforeDelete(TalkType $talkType): void
    // {
    //     // Check for dependencies
    //     // if ($talkType->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete talkType with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
