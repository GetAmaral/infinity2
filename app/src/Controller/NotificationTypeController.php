<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\NotificationTypeControllerGenerated;
use App\Entity\NotificationType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * NotificationType Controller
 *
 * This controller handles all notificationType operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see NotificationTypeControllerGenerated for available lifecycle hooks
 */
#[Route('/notificationtype')]
final class NotificationTypeController extends NotificationTypeControllerGenerated
{
    /**
     * List all notificationTypes
     */
    #[Route('', name: 'notificationtype_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching notificationTypes
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'notificationtype_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new notificationType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'notificationtype_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing notificationType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'notificationtype_edit', methods: ['GET', 'POST'])]
    public function edit(NotificationType $notificationType, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($notificationType, $request);
        }

        return $this->editFormAction($notificationType, $request);
    }

    /**
     * Delete notificationType
     */
    #[Route('/{id}', name: 'notificationtype_delete', methods: ['POST'])]
    public function delete(NotificationType $notificationType, Request $request): Response
    {
        return $this->deleteAction($notificationType, $request);
    }

    /**
     * Show notificationType details
     */
    #[Route('/{id}', name: 'notificationtype_show', methods: ['GET'])]
    public function show(NotificationType $notificationType): Response
    {
        return $this->showAction($notificationType);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(NotificationType $notificationType): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($notificationType);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new NotificationTypeCreatedEvent($notificationType));
    // }
    //
    // protected function beforeDelete(NotificationType $notificationType): void
    // {
    //     // Check for dependencies
    //     // if ($notificationType->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete notificationType with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
