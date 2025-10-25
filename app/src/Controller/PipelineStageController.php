<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\PipelineStageControllerGenerated;
use App\Entity\PipelineStage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * PipelineStage Controller
 *
 * This controller handles all pipelineStage operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see PipelineStageControllerGenerated for available lifecycle hooks
 */
#[Route('/pipelinestage')]
final class PipelineStageController extends PipelineStageControllerGenerated
{
    /**
     * List all pipelineStages
     */
    #[Route('', name: 'pipelinestage_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching pipelineStages
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'pipelinestage_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new pipelineStage
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'pipelinestage_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing pipelineStage
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'pipelinestage_edit', methods: ['GET', 'POST'])]
    public function edit(PipelineStage $pipelineStage, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($pipelineStage, $request);
        }

        return $this->editFormAction($pipelineStage, $request);
    }

    /**
     * Delete pipelineStage
     */
    #[Route('/{id}', name: 'pipelinestage_delete', methods: ['POST'])]
    public function delete(PipelineStage $pipelineStage, Request $request): Response
    {
        return $this->deleteAction($pipelineStage, $request);
    }

    /**
     * Show pipelineStage details
     */
    #[Route('/{id}', name: 'pipelinestage_show', methods: ['GET'])]
    public function show(PipelineStage $pipelineStage): Response
    {
        return $this->showAction($pipelineStage);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(PipelineStage $pipelineStage): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($pipelineStage);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new PipelineStageCreatedEvent($pipelineStage));
    // }
    //
    // protected function beforeDelete(PipelineStage $pipelineStage): void
    // {
    //     // Check for dependencies
    //     // if ($pipelineStage->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete pipelineStage with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
