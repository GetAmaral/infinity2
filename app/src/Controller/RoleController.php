<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\RoleControllerGenerated;
use App\Entity\Role;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Role Controller
 *
 * This controller handles all role operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see RoleControllerGenerated for available lifecycle hooks
 */
#[Route('/role')]
final class RoleController extends RoleControllerGenerated
{
    /**
     * List all roles
     */
    #[Route('', name: 'role_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching roles
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'role_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new role
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'role_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing role
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'role_edit', methods: ['GET', 'POST'])]
    public function edit(Role $role, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($role, $request);
        }

        return $this->editFormAction($role, $request);
    }

    /**
     * Delete role
     */
    #[Route('/{id}', name: 'role_delete', methods: ['POST'])]
    public function delete(Role $role, Request $request): Response
    {
        return $this->deleteAction($role, $request);
    }

    /**
     * Show role details
     */
    #[Route('/{id}', name: 'role_show', methods: ['GET'])]
    public function show(Role $role): Response
    {
        return $this->showAction($role);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Role $role): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($role);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new RoleCreatedEvent($role));
    // }
    //
    // protected function beforeDelete(Role $role): void
    // {
    //     // Check for dependencies
    //     // if ($role->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete role with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
