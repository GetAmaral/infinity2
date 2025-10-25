<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\TaskTypeControllerGenerated;
use App\Entity\TaskType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * TaskType Controller
 *
 * This controller handles all taskType operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see TaskTypeControllerGenerated for available lifecycle hooks
 */
#[Route('/tasktype')]
final class TaskTypeController extends TaskTypeControllerGenerated
{
    /**
     * List all taskTypes
     */
    #[Route('', name: 'tasktype_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching taskTypes
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'tasktype_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new taskType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'tasktype_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing taskType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'tasktype_edit', methods: ['GET', 'POST'])]
    public function edit(TaskType $taskType, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($taskType, $request);
        }

        return $this->editFormAction($taskType, $request);
    }

    /**
     * Delete taskType
     */
    #[Route('/{id}', name: 'tasktype_delete', methods: ['POST'])]
    public function delete(TaskType $taskType, Request $request): Response
    {
        return $this->deleteAction($taskType, $request);
    }

    /**
     * Show taskType details
     */
    #[Route('/{id}', name: 'tasktype_show', methods: ['GET'])]
    public function show(TaskType $taskType): Response
    {
        return $this->showAction($taskType);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(TaskType $taskType): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($taskType);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new TaskTypeCreatedEvent($taskType));
    // }
    //
    // protected function beforeDelete(TaskType $taskType): void
    // {
    //     // Check for dependencies
    //     // if ($taskType->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete taskType with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
