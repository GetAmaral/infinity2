<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\AgentTypeControllerGenerated;
use App\Entity\AgentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * AgentType Controller
 *
 * This controller handles all agentType operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see AgentTypeControllerGenerated for available lifecycle hooks
 */
#[Route('/agenttype')]
final class AgentTypeController extends AgentTypeControllerGenerated
{
    /**
     * List all agentTypes
     */
    #[Route('', name: 'agenttype_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching agentTypes
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'agenttype_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new agentType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'agenttype_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing agentType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'agenttype_edit', methods: ['GET', 'POST'])]
    public function edit(AgentType $agentType, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($agentType, $request);
        }

        return $this->editFormAction($agentType, $request);
    }

    /**
     * Delete agentType
     */
    #[Route('/{id}', name: 'agenttype_delete', methods: ['POST'])]
    public function delete(AgentType $agentType, Request $request): Response
    {
        return $this->deleteAction($agentType, $request);
    }

    /**
     * Show agentType details
     */
    #[Route('/{id}', name: 'agenttype_show', methods: ['GET'])]
    public function show(AgentType $agentType): Response
    {
        return $this->showAction($agentType);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(AgentType $agentType): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($agentType);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new AgentTypeCreatedEvent($agentType));
    // }
    //
    // protected function beforeDelete(AgentType $agentType): void
    // {
    //     // Check for dependencies
    //     // if ($agentType->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete agentType with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
