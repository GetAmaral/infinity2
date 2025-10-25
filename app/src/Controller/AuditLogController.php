<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\AuditLogControllerGenerated;
use App\Entity\AuditLog;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * AuditLog Controller
 *
 * This controller handles all auditLog operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see AuditLogControllerGenerated for available lifecycle hooks
 */
#[Route('/auditlog')]
final class AuditLogController extends AuditLogControllerGenerated
{
    /**
     * List all auditLogs
     */
    #[Route('', name: 'auditlog_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching auditLogs
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'auditlog_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new auditLog
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'auditlog_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing auditLog
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'auditlog_edit', methods: ['GET', 'POST'])]
    public function edit(AuditLog $auditLog, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($auditLog, $request);
        }

        return $this->editFormAction($auditLog, $request);
    }

    /**
     * Delete auditLog
     */
    #[Route('/{id}', name: 'auditlog_delete', methods: ['POST'])]
    public function delete(AuditLog $auditLog, Request $request): Response
    {
        return $this->deleteAction($auditLog, $request);
    }

    /**
     * Show auditLog details
     */
    #[Route('/{id}', name: 'auditlog_show', methods: ['GET'])]
    public function show(AuditLog $auditLog): Response
    {
        return $this->showAction($auditLog);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(AuditLog $auditLog): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($auditLog);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new AuditLogCreatedEvent($auditLog));
    // }
    //
    // protected function beforeDelete(AuditLog $auditLog): void
    // {
    //     // Check for dependencies
    //     // if ($auditLog->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete auditLog with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
