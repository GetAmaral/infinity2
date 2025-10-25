<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\ReminderControllerGenerated;
use App\Entity\Reminder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Reminder Controller
 *
 * This controller handles all reminder operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see ReminderControllerGenerated for available lifecycle hooks
 */
#[Route('/reminder')]
final class ReminderController extends ReminderControllerGenerated
{
    /**
     * List all reminders
     */
    #[Route('', name: 'reminder_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching reminders
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'reminder_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new reminder
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'reminder_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing reminder
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'reminder_edit', methods: ['GET', 'POST'])]
    public function edit(Reminder $reminder, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($reminder, $request);
        }

        return $this->editFormAction($reminder, $request);
    }

    /**
     * Delete reminder
     */
    #[Route('/{id}', name: 'reminder_delete', methods: ['POST'])]
    public function delete(Reminder $reminder, Request $request): Response
    {
        return $this->deleteAction($reminder, $request);
    }

    /**
     * Show reminder details
     */
    #[Route('/{id}', name: 'reminder_show', methods: ['GET'])]
    public function show(Reminder $reminder): Response
    {
        return $this->showAction($reminder);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Reminder $reminder): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($reminder);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new ReminderCreatedEvent($reminder));
    // }
    //
    // protected function beforeDelete(Reminder $reminder): void
    // {
    //     // Check for dependencies
    //     // if ($reminder->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete reminder with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
