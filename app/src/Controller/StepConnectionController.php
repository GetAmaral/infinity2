<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\StepConnectionControllerGenerated;
use App\Entity\StepConnection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * StepConnection Controller
 *
 * This controller handles all stepConnection operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see StepConnectionControllerGenerated for available lifecycle hooks
 */
#[Route('/stepconnection')]
final class StepConnectionController extends StepConnectionControllerGenerated
{
    /**
     * List all stepConnectia
     */
    #[Route('', name: 'stepconnection_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * Create new stepConnection
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'stepconnection_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing stepConnection
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'stepconnection_edit', methods: ['GET', 'POST'])]
    public function edit(StepConnection $stepConnection, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($stepConnection, $request);
        }

        return $this->editFormAction($stepConnection, $request);
    }

    /**
     * Delete stepConnection
     */
    #[Route('/{id}', name: 'stepconnection_delete', methods: ['POST'])]
    public function delete(StepConnection $stepConnection, Request $request): Response
    {
        return $this->deleteAction($stepConnection, $request);
    }

    /**
     * Show stepConnection details
     */
    #[Route('/{id}', name: 'stepconnection_show', methods: ['GET'])]
    public function show(StepConnection $stepConnection): Response
    {
        return $this->showAction($stepConnection);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(StepConnection $stepConnection): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($stepConnection);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new StepConnectionCreatedEvent($stepConnection));
    // }
    //
    // protected function beforeDelete(StepConnection $stepConnection): void
    // {
    //     // Check for dependencies
    //     // if ($stepConnection->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete stepConnection with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
