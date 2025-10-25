<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\FlagControllerGenerated;
use App\Entity\Flag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Flag Controller
 *
 * This controller handles all flag operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see FlagControllerGenerated for available lifecycle hooks
 */
#[Route('/flag')]
final class FlagController extends FlagControllerGenerated
{
    /**
     * List all flags
     */
    #[Route('', name: 'flag_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching flags
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'flag_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new flag
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'flag_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing flag
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'flag_edit', methods: ['GET', 'POST'])]
    public function edit(Flag $flag, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($flag, $request);
        }

        return $this->editFormAction($flag, $request);
    }

    /**
     * Delete flag
     */
    #[Route('/{id}', name: 'flag_delete', methods: ['POST'])]
    public function delete(Flag $flag, Request $request): Response
    {
        return $this->deleteAction($flag, $request);
    }

    /**
     * Show flag details
     */
    #[Route('/{id}', name: 'flag_show', methods: ['GET'])]
    public function show(Flag $flag): Response
    {
        return $this->showAction($flag);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Flag $flag): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($flag);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new FlagCreatedEvent($flag));
    // }
    //
    // protected function beforeDelete(Flag $flag): void
    // {
    //     // Check for dependencies
    //     // if ($flag->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete flag with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
