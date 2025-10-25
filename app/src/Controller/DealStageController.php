<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\DealStageControllerGenerated;
use App\Entity\DealStage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * DealStage Controller
 *
 * This controller handles all dealStage operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see DealStageControllerGenerated for available lifecycle hooks
 */
#[Route('/dealstage')]
final class DealStageController extends DealStageControllerGenerated
{
    /**
     * List all dealStages
     */
    #[Route('', name: 'dealstage_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching dealStages
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'dealstage_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new dealStage
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'dealstage_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing dealStage
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'dealstage_edit', methods: ['GET', 'POST'])]
    public function edit(DealStage $dealStage, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($dealStage, $request);
        }

        return $this->editFormAction($dealStage, $request);
    }

    /**
     * Delete dealStage
     */
    #[Route('/{id}', name: 'dealstage_delete', methods: ['POST'])]
    public function delete(DealStage $dealStage, Request $request): Response
    {
        return $this->deleteAction($dealStage, $request);
    }

    /**
     * Show dealStage details
     */
    #[Route('/{id}', name: 'dealstage_show', methods: ['GET'])]
    public function show(DealStage $dealStage): Response
    {
        return $this->showAction($dealStage);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(DealStage $dealStage): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($dealStage);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new DealStageCreatedEvent($dealStage));
    // }
    //
    // protected function beforeDelete(DealStage $dealStage): void
    // {
    //     // Check for dependencies
    //     // if ($dealStage->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete dealStage with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
