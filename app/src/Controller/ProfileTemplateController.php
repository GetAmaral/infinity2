<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\ProfileTemplateControllerGenerated;
use App\Entity\ProfileTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ProfileTemplate Controller
 *
 * This controller handles all profileTemplate operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see ProfileTemplateControllerGenerated for available lifecycle hooks
 */
#[Route('/profiletemplate')]
final class ProfileTemplateController extends ProfileTemplateControllerGenerated
{
    /**
     * List all profileTemplates
     */
    #[Route('', name: 'profiletemplate_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching profileTemplates
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'profiletemplate_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new profileTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'profiletemplate_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing profileTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'profiletemplate_edit', methods: ['GET', 'POST'])]
    public function edit(ProfileTemplate $profileTemplate, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($profileTemplate, $request);
        }

        return $this->editFormAction($profileTemplate, $request);
    }

    /**
     * Delete profileTemplate
     */
    #[Route('/{id}', name: 'profiletemplate_delete', methods: ['POST'])]
    public function delete(ProfileTemplate $profileTemplate, Request $request): Response
    {
        return $this->deleteAction($profileTemplate, $request);
    }

    /**
     * Show profileTemplate details
     */
    #[Route('/{id}', name: 'profiletemplate_show', methods: ['GET'])]
    public function show(ProfileTemplate $profileTemplate): Response
    {
        return $this->showAction($profileTemplate);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(ProfileTemplate $profileTemplate): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($profileTemplate);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new ProfileTemplateCreatedEvent($profileTemplate));
    // }
    //
    // protected function beforeDelete(ProfileTemplate $profileTemplate): void
    // {
    //     // Check for dependencies
    //     // if ($profileTemplate->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete profileTemplate with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
