<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\StepInputControllerGenerated;
use App\Entity\StepInput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * StepInput Controller
 *
 * This controller handles all stepInput operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see StepInputControllerGenerated for available lifecycle hooks
 */
#[Route('/stepinput')]
final class StepInputController extends StepInputControllerGenerated
{
    /**
     * List all stepInputs
     */
    #[Route('', name: 'stepinput_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * Create new stepInput
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'stepinput_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing stepInput
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'stepinput_edit', methods: ['GET', 'POST'])]
    public function edit(StepInput $stepInput, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($stepInput, $request);
        }

        return $this->editFormAction($stepInput, $request);
    }

    /**
     * Delete stepInput
     */
    #[Route('/{id}', name: 'stepinput_delete', methods: ['POST'])]
    public function delete(StepInput $stepInput, Request $request): Response
    {
        return $this->deleteAction($stepInput, $request);
    }

    /**
     * Show stepInput details
     */
    #[Route('/{id}', name: 'stepinput_show', methods: ['GET'])]
    public function show(StepInput $stepInput): Response
    {
        return $this->showAction($stepInput);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(StepInput $stepInput): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($stepInput);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new StepInputCreatedEvent($stepInput));
    // }
    //
    // protected function beforeDelete(StepInput $stepInput): void
    // {
    //     // Check for dependencies
    //     // if ($stepInput->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete stepInput with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
