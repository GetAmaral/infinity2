<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\ModuleControllerGenerated;
use App\Entity\Module;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Module Controller
 *
 * This controller handles all module operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see ModuleControllerGenerated for available lifecycle hooks
 */
#[Route('/module')]
final class ModuleController extends ModuleControllerGenerated
{
    /**
     * List all modules
     */
    #[Route('', name: 'module_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching modules
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'module_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new module
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'module_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing module
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'module_edit', methods: ['GET', 'POST'])]
    public function edit(Module $module, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($module, $request);
        }

        return $this->editFormAction($module, $request);
    }

    /**
     * Delete module
     */
    #[Route('/{id}', name: 'module_delete', methods: ['POST'])]
    public function delete(Module $module, Request $request): Response
    {
        return $this->deleteAction($module, $request);
    }

    /**
     * Show module details
     */
    #[Route('/{id}', name: 'module_show', methods: ['GET'])]
    public function show(Module $module): Response
    {
        return $this->showAction($module);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Module $module): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($module);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new ModuleCreatedEvent($module));
    // }
    //
    // protected function beforeDelete(Module $module): void
    // {
    //     // Check for dependencies
    //     // if ($module->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete module with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
