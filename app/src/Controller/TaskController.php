<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\TaskControllerGenerated;
use App\Entity\Task;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Task Controller
 *
 * This controller handles all task operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see TaskControllerGenerated for available lifecycle hooks
 */
#[Route('/task')]
final class TaskController extends TaskControllerGenerated
{
    /**
     * List all tasks
     */
    #[Route('', name: 'task_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching tasks
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'task_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new task
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'task_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing task
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Task $task, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($task, $request);
        }

        return $this->editFormAction($task, $request);
    }

    /**
     * Delete task
     */
    #[Route('/{id}', name: 'task_delete', methods: ['POST'])]
    public function delete(Task $task, Request $request): Response
    {
        return $this->deleteAction($task, $request);
    }

    /**
     * Show task details
     */
    #[Route('/{id}', name: 'task_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        return $this->showAction($task);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Task $task): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($task);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new TaskCreatedEvent($task));
    // }
    //
    // protected function beforeDelete(Task $task): void
    // {
    //     // Check for dependencies
    //     // if ($task->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete task with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
