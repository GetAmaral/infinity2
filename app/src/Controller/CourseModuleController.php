<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CourseModuleControllerGenerated;
use App\Entity\CourseModule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CourseModule Controller
 *
 * This controller handles all courseModule operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CourseModuleControllerGenerated for available lifecycle hooks
 */
#[Route('/coursemodule')]
final class CourseModuleController extends CourseModuleControllerGenerated
{
    /**
     * List all courseModules
     */
    #[Route('', name: 'coursemodule_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching courseModules
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'coursemodule_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new courseModule
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'coursemodule_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing courseModule
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'coursemodule_edit', methods: ['GET', 'POST'])]
    public function edit(CourseModule $courseModule, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($courseModule, $request);
        }

        return $this->editFormAction($courseModule, $request);
    }

    /**
     * Delete courseModule
     */
    #[Route('/{id}', name: 'coursemodule_delete', methods: ['POST'])]
    public function delete(CourseModule $courseModule, Request $request): Response
    {
        return $this->deleteAction($courseModule, $request);
    }

    /**
     * Show courseModule details
     */
    #[Route('/{id}', name: 'coursemodule_show', methods: ['GET'])]
    public function show(CourseModule $courseModule): Response
    {
        return $this->showAction($courseModule);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(CourseModule $courseModule): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($courseModule);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new CourseModuleCreatedEvent($courseModule));
    // }
    //
    // protected function beforeDelete(CourseModule $courseModule): void
    // {
    //     // Check for dependencies
    //     // if ($courseModule->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete courseModule with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
