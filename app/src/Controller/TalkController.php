<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\TalkControllerGenerated;
use App\Entity\Talk;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Talk Controller
 *
 * This controller handles all talk operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see TalkControllerGenerated for available lifecycle hooks
 */
#[Route('/talk')]
final class TalkController extends TalkControllerGenerated
{
    /**
     * List all talks
     */
    #[Route('', name: 'talk_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching talks
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'talk_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new talk
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'talk_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing talk
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'talk_edit', methods: ['GET', 'POST'])]
    public function edit(Talk $talk, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($talk, $request);
        }

        return $this->editFormAction($talk, $request);
    }

    /**
     * Delete talk
     */
    #[Route('/{id}', name: 'talk_delete', methods: ['POST'])]
    public function delete(Talk $talk, Request $request): Response
    {
        return $this->deleteAction($talk, $request);
    }

    /**
     * Show talk details
     */
    #[Route('/{id}', name: 'talk_show', methods: ['GET'])]
    public function show(Talk $talk): Response
    {
        return $this->showAction($talk);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Talk $talk): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($talk);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new TalkCreatedEvent($talk));
    // }
    //
    // protected function beforeDelete(Talk $talk): void
    // {
    //     // Check for dependencies
    //     // if ($talk->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete talk with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
