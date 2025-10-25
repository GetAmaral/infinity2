<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\TalkMessageControllerGenerated;
use App\Entity\TalkMessage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * TalkMessage Controller
 *
 * This controller handles all talkMessage operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see TalkMessageControllerGenerated for available lifecycle hooks
 */
#[Route('/talkmessage')]
final class TalkMessageController extends TalkMessageControllerGenerated
{
    /**
     * List all talkMessages
     */
    #[Route('', name: 'talkmessage_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching talkMessages
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'talkmessage_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new talkMessage
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'talkmessage_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing talkMessage
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'talkmessage_edit', methods: ['GET', 'POST'])]
    public function edit(TalkMessage $talkMessage, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($talkMessage, $request);
        }

        return $this->editFormAction($talkMessage, $request);
    }

    /**
     * Delete talkMessage
     */
    #[Route('/{id}', name: 'talkmessage_delete', methods: ['POST'])]
    public function delete(TalkMessage $talkMessage, Request $request): Response
    {
        return $this->deleteAction($talkMessage, $request);
    }

    /**
     * Show talkMessage details
     */
    #[Route('/{id}', name: 'talkmessage_show', methods: ['GET'])]
    public function show(TalkMessage $talkMessage): Response
    {
        return $this->showAction($talkMessage);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(TalkMessage $talkMessage): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($talkMessage);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new TalkMessageCreatedEvent($talkMessage));
    // }
    //
    // protected function beforeDelete(TalkMessage $talkMessage): void
    // {
    //     // Check for dependencies
    //     // if ($talkMessage->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete talkMessage with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
