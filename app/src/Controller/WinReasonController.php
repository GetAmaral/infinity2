<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\WinReasonControllerGenerated;
use App\Entity\WinReason;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * WinReason Controller
 *
 * This controller handles all winReason operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see WinReasonControllerGenerated for available lifecycle hooks
 */
#[Route('/winreason')]
final class WinReasonController extends WinReasonControllerGenerated
{
    /**
     * List all winReasa
     */
    #[Route('', name: 'winreason_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching winReasa
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'winreason_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new winReason
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'winreason_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing winReason
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'winreason_edit', methods: ['GET', 'POST'])]
    public function edit(WinReason $winReason, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($winReason, $request);
        }

        return $this->editFormAction($winReason, $request);
    }

    /**
     * Delete winReason
     */
    #[Route('/{id}', name: 'winreason_delete', methods: ['POST'])]
    public function delete(WinReason $winReason, Request $request): Response
    {
        return $this->deleteAction($winReason, $request);
    }

    /**
     * Show winReason details
     */
    #[Route('/{id}', name: 'winreason_show', methods: ['GET'])]
    public function show(WinReason $winReason): Response
    {
        return $this->showAction($winReason);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(WinReason $winReason): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($winReason);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new WinReasonCreatedEvent($winReason));
    // }
    //
    // protected function beforeDelete(WinReason $winReason): void
    // {
    //     // Check for dependencies
    //     // if ($winReason->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete winReason with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
