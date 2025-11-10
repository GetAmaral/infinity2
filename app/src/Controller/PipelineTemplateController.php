<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\PipelineTemplateControllerGenerated;
use App\Entity\PipelineTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * PipelineTemplate Controller
 *
 * This controller handles all pipelineTemplate operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see PipelineTemplateControllerGenerated for available lifecycle hooks
 */
#[Route('/pipelinetemplate')]
final class PipelineTemplateController extends PipelineTemplateControllerGenerated
{
    /**
     * List all pipelineTemplates
     */
    #[Route('', name: 'pipelinetemplate_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching pipelineTemplates
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'pipelinetemplate_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new pipelineTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'pipelinetemplate_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing pipelineTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'pipelinetemplate_edit', methods: ['GET', 'POST'])]
    public function edit(PipelineTemplate $pipelineTemplate, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($pipelineTemplate, $request);
        }

        return $this->editFormAction($pipelineTemplate, $request);
    }

    /**
     * Delete pipelineTemplate
     */
    #[Route('/{id}', name: 'pipelinetemplate_delete', methods: ['POST'])]
    public function delete(PipelineTemplate $pipelineTemplate, Request $request): Response
    {
        return $this->deleteAction($pipelineTemplate, $request);
    }

    /**
     * Show pipelineTemplate details
     */
    #[Route('/{id}', name: 'pipelinetemplate_show', methods: ['GET'])]
    public function show(PipelineTemplate $pipelineTemplate): Response
    {
        return $this->showAction($pipelineTemplate);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(PipelineTemplate $pipelineTemplate): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($pipelineTemplate);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new PipelineTemplateCreatedEvent($pipelineTemplate));
    // }
    //
    // protected function beforeDelete(PipelineTemplate $pipelineTemplate): void
    // {
    //     // Check for dependencies
    //     // if ($pipelineTemplate->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete pipelineTemplate with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
