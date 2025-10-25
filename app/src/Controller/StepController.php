<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\StepControllerGenerated;
use App\Entity\Step;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Step Controller
 *
 * This controller handles all step operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see StepControllerGenerated for available lifecycle hooks
 */
#[Route('/step')]
final class StepController extends StepControllerGenerated
{
    /**
     * List all steps
     */
    #[Route('', name: 'step_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * Create new step
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'step_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing step
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'step_edit', methods: ['GET', 'POST'])]
    public function edit(Step $step, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($step, $request);
        }

        return $this->editFormAction($step, $request);
    }

    /**
     * Delete step
     */
    #[Route('/{id}', name: 'step_delete', methods: ['POST'])]
    public function delete(Step $step, Request $request): Response
    {
        return $this->deleteAction($step, $request);
    }

    /**
     * Show step details
     */
    #[Route('/{id}', name: 'step_show', methods: ['GET'])]
    public function show(Step $step): Response
    {
        return $this->showAction($step);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Step $step): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($step);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new StepCreatedEvent($step));
    // }
    //
    // protected function beforeDelete(Step $step): void
    // {
    //     // Check for dependencies
    //     // if ($step->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete step with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
