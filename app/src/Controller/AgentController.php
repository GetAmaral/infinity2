<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\AgentControllerGenerated;
use App\Entity\Agent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Agent Controller
 *
 * This controller handles all agent operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see AgentControllerGenerated for available lifecycle hooks
 */
#[Route('/agent')]
final class AgentController extends AgentControllerGenerated
{
    /**
     * List all agents
     */
    #[Route('', name: 'agent_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching agents
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'agent_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new agent
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'agent_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing agent
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'agent_edit', methods: ['GET', 'POST'])]
    public function edit(Agent $agent, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($agent, $request);
        }

        return $this->editFormAction($agent, $request);
    }

    /**
     * Delete agent
     */
    #[Route('/{id}', name: 'agent_delete', methods: ['POST'])]
    public function delete(Agent $agent, Request $request): Response
    {
        return $this->deleteAction($agent, $request);
    }

    /**
     * Show agent details
     */
    #[Route('/{id}', name: 'agent_show', methods: ['GET'])]
    public function show(Agent $agent): Response
    {
        return $this->showAction($agent);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Agent $agent): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($agent);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new AgentCreatedEvent($agent));
    // }
    //
    // protected function beforeDelete(Agent $agent): void
    // {
    //     // Check for dependencies
    //     // if ($agent->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete agent with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
