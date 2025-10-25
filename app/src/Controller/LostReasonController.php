<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\LostReasonControllerGenerated;
use App\Entity\LostReason;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * LostReason Controller
 *
 * This controller handles all lostReason operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see LostReasonControllerGenerated for available lifecycle hooks
 */
#[Route('/lostreason')]
final class LostReasonController extends LostReasonControllerGenerated
{
    /**
     * List all lostReasa
     */
    #[Route('', name: 'lostreason_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching lostReasa
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'lostreason_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new lostReason
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'lostreason_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing lostReason
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'lostreason_edit', methods: ['GET', 'POST'])]
    public function edit(LostReason $lostReason, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($lostReason, $request);
        }

        return $this->editFormAction($lostReason, $request);
    }

    /**
     * Delete lostReason
     */
    #[Route('/{id}', name: 'lostreason_delete', methods: ['POST'])]
    public function delete(LostReason $lostReason, Request $request): Response
    {
        return $this->deleteAction($lostReason, $request);
    }

    /**
     * Show lostReason details
     */
    #[Route('/{id}', name: 'lostreason_show', methods: ['GET'])]
    public function show(LostReason $lostReason): Response
    {
        return $this->showAction($lostReason);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(LostReason $lostReason): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($lostReason);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new LostReasonCreatedEvent($lostReason));
    // }
    //
    // protected function beforeDelete(LostReason $lostReason): void
    // {
    //     // Check for dependencies
    //     // if ($lostReason->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete lostReason with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
