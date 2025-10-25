<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\PipelineStageTemplateControllerGenerated;
use App\Entity\PipelineStageTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * PipelineStageTemplate Controller
 *
 * This controller handles all pipelineStageTemplate operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see PipelineStageTemplateControllerGenerated for available lifecycle hooks
 */
#[Route('/pipelinestagetemplate')]
final class PipelineStageTemplateController extends PipelineStageTemplateControllerGenerated
{
    /**
     * List all pipelineStageTemplates
     */
    #[Route('', name: 'pipelinestagetemplate_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching pipelineStageTemplates
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'pipelinestagetemplate_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new pipelineStageTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'pipelinestagetemplate_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing pipelineStageTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'pipelinestagetemplate_edit', methods: ['GET', 'POST'])]
    public function edit(PipelineStageTemplate $pipelineStageTemplate, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($pipelineStageTemplate, $request);
        }

        return $this->editFormAction($pipelineStageTemplate, $request);
    }

    /**
     * Delete pipelineStageTemplate
     */
    #[Route('/{id}', name: 'pipelinestagetemplate_delete', methods: ['POST'])]
    public function delete(PipelineStageTemplate $pipelineStageTemplate, Request $request): Response
    {
        return $this->deleteAction($pipelineStageTemplate, $request);
    }

    /**
     * Show pipelineStageTemplate details
     */
    #[Route('/{id}', name: 'pipelinestagetemplate_show', methods: ['GET'])]
    public function show(PipelineStageTemplate $pipelineStageTemplate): Response
    {
        return $this->showAction($pipelineStageTemplate);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(PipelineStageTemplate $pipelineStageTemplate): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($pipelineStageTemplate);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new PipelineStageTemplateCreatedEvent($pipelineStageTemplate));
    // }
    //
    // protected function beforeDelete(PipelineStageTemplate $pipelineStageTemplate): void
    // {
    //     // Check for dependencies
    //     // if ($pipelineStageTemplate->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete pipelineStageTemplate with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
