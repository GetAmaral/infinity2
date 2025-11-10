<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CampaignControllerGenerated;
use App\Entity\Campaign;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Campaign Controller
 *
 * This controller handles all campaign operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CampaignControllerGenerated for available lifecycle hooks
 */
#[Route('/campaign')]
final class CampaignController extends CampaignControllerGenerated
{
    /**
     * List all campaigns
     */
    #[Route('', name: 'campaign_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching campaigns
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'campaign_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new campaign
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'campaign_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing campaign
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'campaign_edit', methods: ['GET', 'POST'])]
    public function edit(Campaign $campaign, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($campaign, $request);
        }

        return $this->editFormAction($campaign, $request);
    }

    /**
     * Delete campaign
     */
    #[Route('/{id}', name: 'campaign_delete', methods: ['POST'])]
    public function delete(Campaign $campaign, Request $request): Response
    {
        return $this->deleteAction($campaign, $request);
    }

    /**
     * Show campaign details
     */
    #[Route('/{id}', name: 'campaign_show', methods: ['GET'])]
    public function show(Campaign $campaign): Response
    {
        return $this->showAction($campaign);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Campaign $campaign): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($campaign);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new CampaignCreatedEvent($campaign));
    // }
    //
    // protected function beforeDelete(Campaign $campaign): void
    // {
    //     // Check for dependencies
    //     // if ($campaign->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete campaign with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
