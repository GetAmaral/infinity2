<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\LeadSourceControllerGenerated;
use App\Entity\LeadSource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * LeadSource Controller
 *
 * This controller handles all leadSource operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see LeadSourceControllerGenerated for available lifecycle hooks
 */
#[Route('/leadsource')]
final class LeadSourceController extends LeadSourceControllerGenerated
{
    /**
     * List all leadSources
     */
    #[Route('', name: 'leadsource_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching leadSources
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'leadsource_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new leadSource
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'leadsource_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing leadSource
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'leadsource_edit', methods: ['GET', 'POST'])]
    public function edit(LeadSource $leadSource, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($leadSource, $request);
        }

        return $this->editFormAction($leadSource, $request);
    }

    /**
     * Delete leadSource
     */
    #[Route('/{id}', name: 'leadsource_delete', methods: ['POST'])]
    public function delete(LeadSource $leadSource, Request $request): Response
    {
        return $this->deleteAction($leadSource, $request);
    }

    /**
     * Show leadSource details
     */
    #[Route('/{id}', name: 'leadsource_show', methods: ['GET'])]
    public function show(LeadSource $leadSource): Response
    {
        return $this->showAction($leadSource);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(LeadSource $leadSource): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($leadSource);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new LeadSourceCreatedEvent($leadSource));
    // }
    //
    // protected function beforeDelete(LeadSource $leadSource): void
    // {
    //     // Check for dependencies
    //     // if ($leadSource->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete leadSource with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
