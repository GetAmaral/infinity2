<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\AttachmentControllerGenerated;
use App\Entity\Attachment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Attachment Controller
 *
 * This controller handles all attachment operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see AttachmentControllerGenerated for available lifecycle hooks
 */
#[Route('/attachment')]
final class AttachmentController extends AttachmentControllerGenerated
{
    /**
     * List all attachments
     */
    #[Route('', name: 'attachment_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching attachments
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'attachment_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new attachment
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'attachment_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing attachment
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'attachment_edit', methods: ['GET', 'POST'])]
    public function edit(Attachment $attachment, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($attachment, $request);
        }

        return $this->editFormAction($attachment, $request);
    }

    /**
     * Delete attachment
     */
    #[Route('/{id}', name: 'attachment_delete', methods: ['POST'])]
    public function delete(Attachment $attachment, Request $request): Response
    {
        return $this->deleteAction($attachment, $request);
    }

    /**
     * Show attachment details
     */
    #[Route('/{id}', name: 'attachment_show', methods: ['GET'])]
    public function show(Attachment $attachment): Response
    {
        return $this->showAction($attachment);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Attachment $attachment): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($attachment);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new AttachmentCreatedEvent($attachment));
    // }
    //
    // protected function beforeDelete(Attachment $attachment): void
    // {
    //     // Check for dependencies
    //     // if ($attachment->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete attachment with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
