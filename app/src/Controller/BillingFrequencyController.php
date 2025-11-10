<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\BillingFrequencyControllerGenerated;
use App\Entity\BillingFrequency;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * BillingFrequency Controller
 *
 * This controller handles all billingFrequency operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see BillingFrequencyControllerGenerated for available lifecycle hooks
 */
#[Route('/billingfrequency')]
final class BillingFrequencyController extends BillingFrequencyControllerGenerated
{
    /**
     * List all cies
     */
    #[Route('', name: 'billingfrequency_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching cies
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'billingfrequency_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new billingFrequency
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'billingfrequency_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing billingFrequency
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'billingfrequency_edit', methods: ['GET', 'POST'])]
    public function edit(BillingFrequency $billingFrequency, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($billingFrequency, $request);
        }

        return $this->editFormAction($billingFrequency, $request);
    }

    /**
     * Delete billingFrequency
     */
    #[Route('/{id}', name: 'billingfrequency_delete', methods: ['POST'])]
    public function delete(BillingFrequency $billingFrequency, Request $request): Response
    {
        return $this->deleteAction($billingFrequency, $request);
    }

    /**
     * Show billingFrequency details
     */
    #[Route('/{id}', name: 'billingfrequency_show', methods: ['GET'])]
    public function show(BillingFrequency $billingFrequency): Response
    {
        return $this->showAction($billingFrequency);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(BillingFrequency $billingFrequency): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($billingFrequency);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new BillingFrequencyCreatedEvent($billingFrequency));
    // }
    //
    // protected function beforeDelete(BillingFrequency $billingFrequency): void
    // {
    //     // Check for dependencies
    //     // if ($billingFrequency->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete billingFrequency with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
