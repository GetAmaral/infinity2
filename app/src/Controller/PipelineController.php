<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\PipelineControllerGenerated;
use App\Entity\Pipeline;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pipeline Controller
 *
 * This controller handles all pipeline operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see PipelineControllerGenerated for available lifecycle hooks
 */
#[Route('/pipeline')]
final class PipelineController extends PipelineControllerGenerated
{
    /**
     * List all pipelines
     */
    #[Route('', name: 'pipeline_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching pipelines
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'pipeline_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new pipeline
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'pipeline_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing pipeline
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'pipeline_edit', methods: ['GET', 'POST'])]
    public function edit(Pipeline $pipeline, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($pipeline, $request);
        }

        return $this->editFormAction($pipeline, $request);
    }

    /**
     * Delete pipeline
     */
    #[Route('/{id}', name: 'pipeline_delete', methods: ['POST'])]
    public function delete(Pipeline $pipeline, Request $request): Response
    {
        return $this->deleteAction($pipeline, $request);
    }

    /**
     * Show pipeline details
     */
    #[Route('/{id}', name: 'pipeline_show', methods: ['GET'])]
    public function show(Pipeline $pipeline): Response
    {
        return $this->showAction($pipeline);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Pipeline $pipeline): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($pipeline);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new PipelineCreatedEvent($pipeline));
    // }
    //
    // protected function beforeDelete(Pipeline $pipeline): void
    // {
    //     // Check for dependencies
    //     // if ($pipeline->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete pipeline with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
