<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CalendarExternalLinkControllerGenerated;
use App\Entity\CalendarExternalLink;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CalendarExternalLink Controller
 *
 * This controller handles all calendarExternalLink operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CalendarExternalLinkControllerGenerated for available lifecycle hooks
 */
#[Route('/calendarexternallink')]
final class CalendarExternalLinkController extends CalendarExternalLinkControllerGenerated
{
    /**
     * List all calendarExternalLinks
     */
    #[Route('', name: 'calendarexternallink_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching calendarExternalLinks
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'calendarexternallink_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new calendarExternalLink
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'calendarexternallink_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing calendarExternalLink
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'calendarexternallink_edit', methods: ['GET', 'POST'])]
    public function edit(CalendarExternalLink $calendarExternalLink, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($calendarExternalLink, $request);
        }

        return $this->editFormAction($calendarExternalLink, $request);
    }

    /**
     * Delete calendarExternalLink
     */
    #[Route('/{id}', name: 'calendarexternallink_delete', methods: ['POST'])]
    public function delete(CalendarExternalLink $calendarExternalLink, Request $request): Response
    {
        return $this->deleteAction($calendarExternalLink, $request);
    }

    /**
     * Show calendarExternalLink details
     */
    #[Route('/{id}', name: 'calendarexternallink_show', methods: ['GET'])]
    public function show(CalendarExternalLink $calendarExternalLink): Response
    {
        return $this->showAction($calendarExternalLink);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(CalendarExternalLink $calendarExternalLink): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($calendarExternalLink);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new CalendarExternalLinkCreatedEvent($calendarExternalLink));
    // }
    //
    // protected function beforeDelete(CalendarExternalLink $calendarExternalLink): void
    // {
    //     // Check for dependencies
    //     // if ($calendarExternalLink->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete calendarExternalLink with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
