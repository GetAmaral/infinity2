<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\TaskTemplateControllerGenerated;
use App\Entity\TaskTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * TaskTemplate Controller
 *
 * This controller handles all taskTemplate operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see TaskTemplateControllerGenerated for available lifecycle hooks
 */
#[Route('/tasktemplate')]
final class TaskTemplateController extends TaskTemplateControllerGenerated
{
    /**
     * List all taskTemplates
     */
    #[Route('', name: 'tasktemplate_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching taskTemplates
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'tasktemplate_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new taskTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'tasktemplate_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing taskTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'tasktemplate_edit', methods: ['GET', 'POST'])]
    public function edit(TaskTemplate $taskTemplate, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($taskTemplate, $request);
        }

        return $this->editFormAction($taskTemplate, $request);
    }

    /**
     * Delete taskTemplate
     */
    #[Route('/{id}', name: 'tasktemplate_delete', methods: ['POST'])]
    public function delete(TaskTemplate $taskTemplate, Request $request): Response
    {
        return $this->deleteAction($taskTemplate, $request);
    }

    /**
     * Show taskTemplate details
     */
    #[Route('/{id}', name: 'tasktemplate_show', methods: ['GET'])]
    public function show(TaskTemplate $taskTemplate): Response
    {
        return $this->showAction($taskTemplate);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(TaskTemplate $taskTemplate): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($taskTemplate);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new TaskTemplateCreatedEvent($taskTemplate));
    // }
    //
    // protected function beforeDelete(TaskTemplate $taskTemplate): void
    // {
    //     // Check for dependencies
    //     // if ($taskTemplate->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete taskTemplate with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
