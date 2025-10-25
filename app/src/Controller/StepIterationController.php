<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\StepIterationControllerGenerated;
use App\Entity\StepIteration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * StepIteration Controller
 *
 * This controller handles all stepIteration operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see StepIterationControllerGenerated for available lifecycle hooks
 */
#[Route('/stepiteration')]
final class StepIterationController extends StepIterationControllerGenerated
{
    /**
     * List all stepIteratia
     */
    #[Route('', name: 'stepiteration_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * Create new stepIteration
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'stepiteration_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing stepIteration
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'stepiteration_edit', methods: ['GET', 'POST'])]
    public function edit(StepIteration $stepIteration, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($stepIteration, $request);
        }

        return $this->editFormAction($stepIteration, $request);
    }

    /**
     * Delete stepIteration
     */
    #[Route('/{id}', name: 'stepiteration_delete', methods: ['POST'])]
    public function delete(StepIteration $stepIteration, Request $request): Response
    {
        return $this->deleteAction($stepIteration, $request);
    }

    /**
     * Show stepIteration details
     */
    #[Route('/{id}', name: 'stepiteration_show', methods: ['GET'])]
    public function show(StepIteration $stepIteration): Response
    {
        return $this->showAction($stepIteration);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(StepIteration $stepIteration): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($stepIteration);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new StepIterationCreatedEvent($stepIteration));
    // }
    //
    // protected function beforeDelete(StepIteration $stepIteration): void
    // {
    //     // Check for dependencies
    //     // if ($stepIteration->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete stepIteration with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
