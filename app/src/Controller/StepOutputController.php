<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\StepOutputControllerGenerated;
use App\Entity\StepOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * StepOutput Controller
 *
 * This controller handles all stepOutput operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see StepOutputControllerGenerated for available lifecycle hooks
 */
#[Route('/stepoutput')]
final class StepOutputController extends StepOutputControllerGenerated
{
    /**
     * List all stepOutputs
     */
    #[Route('', name: 'stepoutput_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * Create new stepOutput
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'stepoutput_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing stepOutput
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'stepoutput_edit', methods: ['GET', 'POST'])]
    public function edit(StepOutput $stepOutput, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($stepOutput, $request);
        }

        return $this->editFormAction($stepOutput, $request);
    }

    /**
     * Delete stepOutput
     */
    #[Route('/{id}', name: 'stepoutput_delete', methods: ['POST'])]
    public function delete(StepOutput $stepOutput, Request $request): Response
    {
        return $this->deleteAction($stepOutput, $request);
    }

    /**
     * Show stepOutput details
     */
    #[Route('/{id}', name: 'stepoutput_show', methods: ['GET'])]
    public function show(StepOutput $stepOutput): Response
    {
        return $this->showAction($stepOutput);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(StepOutput $stepOutput): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($stepOutput);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new StepOutputCreatedEvent($stepOutput));
    // }
    //
    // protected function beforeDelete(StepOutput $stepOutput): void
    // {
    //     // Check for dependencies
    //     // if ($stepOutput->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete stepOutput with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
