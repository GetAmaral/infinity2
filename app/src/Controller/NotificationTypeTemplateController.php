<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\NotificationTypeTemplateControllerGenerated;
use App\Entity\NotificationTypeTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * NotificationTypeTemplate Controller
 *
 * This controller handles all notificationTypeTemplate operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see NotificationTypeTemplateControllerGenerated for available lifecycle hooks
 */
#[Route('/notificationtypetemplate')]
final class NotificationTypeTemplateController extends NotificationTypeTemplateControllerGenerated
{
    /**
     * List all notificationTypeTemplates
     */
    #[Route('', name: 'notificationtypetemplate_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching notificationTypeTemplates
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'notificationtypetemplate_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new notificationTypeTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'notificationtypetemplate_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing notificationTypeTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'notificationtypetemplate_edit', methods: ['GET', 'POST'])]
    public function edit(NotificationTypeTemplate $notificationTypeTemplate, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($notificationTypeTemplate, $request);
        }

        return $this->editFormAction($notificationTypeTemplate, $request);
    }

    /**
     * Delete notificationTypeTemplate
     */
    #[Route('/{id}', name: 'notificationtypetemplate_delete', methods: ['POST'])]
    public function delete(NotificationTypeTemplate $notificationTypeTemplate, Request $request): Response
    {
        return $this->deleteAction($notificationTypeTemplate, $request);
    }

    /**
     * Show notificationTypeTemplate details
     */
    #[Route('/{id}', name: 'notificationtypetemplate_show', methods: ['GET'])]
    public function show(NotificationTypeTemplate $notificationTypeTemplate): Response
    {
        return $this->showAction($notificationTypeTemplate);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(NotificationTypeTemplate $notificationTypeTemplate): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($notificationTypeTemplate);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new NotificationTypeTemplateCreatedEvent($notificationTypeTemplate));
    // }
    //
    // protected function beforeDelete(NotificationTypeTemplate $notificationTypeTemplate): void
    // {
    //     // Check for dependencies
    //     // if ($notificationTypeTemplate->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete notificationTypeTemplate with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
