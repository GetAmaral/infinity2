<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\NotificationControllerGenerated;
use App\Entity\Notification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Notification Controller
 *
 * This controller handles all notification operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see NotificationControllerGenerated for available lifecycle hooks
 */
#[Route('/notification')]
final class NotificationController extends NotificationControllerGenerated
{
    /**
     * List all notificatia
     */
    #[Route('', name: 'notification_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching notificatia
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'notification_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new notification
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'notification_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing notification
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'notification_edit', methods: ['GET', 'POST'])]
    public function edit(Notification $notification, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($notification, $request);
        }

        return $this->editFormAction($notification, $request);
    }

    /**
     * Delete notification
     */
    #[Route('/{id}', name: 'notification_delete', methods: ['POST'])]
    public function delete(Notification $notification, Request $request): Response
    {
        return $this->deleteAction($notification, $request);
    }

    /**
     * Show notification details
     */
    #[Route('/{id}', name: 'notification_show', methods: ['GET'])]
    public function show(Notification $notification): Response
    {
        return $this->showAction($notification);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Notification $notification): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($notification);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new NotificationCreatedEvent($notification));
    // }
    //
    // protected function beforeDelete(Notification $notification): void
    // {
    //     // Check for dependencies
    //     // if ($notification->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete notification with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
