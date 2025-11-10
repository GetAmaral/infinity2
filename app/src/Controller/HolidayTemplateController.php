<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\HolidayTemplateControllerGenerated;
use App\Entity\HolidayTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * HolidayTemplate Controller
 *
 * This controller handles all holidayTemplate operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see HolidayTemplateControllerGenerated for available lifecycle hooks
 */
#[Route('/holidaytemplate')]
final class HolidayTemplateController extends HolidayTemplateControllerGenerated
{
    /**
     * List all holidayTemplates
     */
    #[Route('', name: 'holidaytemplate_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching holidayTemplates
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'holidaytemplate_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new holidayTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'holidaytemplate_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing holidayTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'holidaytemplate_edit', methods: ['GET', 'POST'])]
    public function edit(HolidayTemplate $holidayTemplate, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($holidayTemplate, $request);
        }

        return $this->editFormAction($holidayTemplate, $request);
    }

    /**
     * Delete holidayTemplate
     */
    #[Route('/{id}', name: 'holidaytemplate_delete', methods: ['POST'])]
    public function delete(HolidayTemplate $holidayTemplate, Request $request): Response
    {
        return $this->deleteAction($holidayTemplate, $request);
    }

    /**
     * Show holidayTemplate details
     */
    #[Route('/{id}', name: 'holidaytemplate_show', methods: ['GET'])]
    public function show(HolidayTemplate $holidayTemplate): Response
    {
        return $this->showAction($holidayTemplate);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(HolidayTemplate $holidayTemplate): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($holidayTemplate);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new HolidayTemplateCreatedEvent($holidayTemplate));
    // }
    //
    // protected function beforeDelete(HolidayTemplate $holidayTemplate): void
    // {
    //     // Check for dependencies
    //     // if ($holidayTemplate->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete holidayTemplate with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
