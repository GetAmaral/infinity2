<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CommunicationMethodControllerGenerated;
use App\Entity\CommunicationMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CommunicationMethod Controller
 *
 * This controller handles all communicationMethod operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CommunicationMethodControllerGenerated for available lifecycle hooks
 */
#[Route('/communicationmethod')]
final class CommunicationMethodController extends CommunicationMethodControllerGenerated
{
    /**
     * List all communicationMethods
     */
    #[Route('', name: 'communicationmethod_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching communicationMethods
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'communicationmethod_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new communicationMethod
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'communicationmethod_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing communicationMethod
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'communicationmethod_edit', methods: ['GET', 'POST'])]
    public function edit(CommunicationMethod $communicationMethod, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($communicationMethod, $request);
        }

        return $this->editFormAction($communicationMethod, $request);
    }

    /**
     * Delete communicationMethod
     */
    #[Route('/{id}', name: 'communicationmethod_delete', methods: ['POST'])]
    public function delete(CommunicationMethod $communicationMethod, Request $request): Response
    {
        return $this->deleteAction($communicationMethod, $request);
    }

    /**
     * Show communicationMethod details
     */
    #[Route('/{id}', name: 'communicationmethod_show', methods: ['GET'])]
    public function show(CommunicationMethod $communicationMethod): Response
    {
        return $this->showAction($communicationMethod);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(CommunicationMethod $communicationMethod): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($communicationMethod);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new CommunicationMethodCreatedEvent($communicationMethod));
    // }
    //
    // protected function beforeDelete(CommunicationMethod $communicationMethod): void
    // {
    //     // Check for dependencies
    //     // if ($communicationMethod->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete communicationMethod with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
