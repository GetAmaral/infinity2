<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\TalkTypeTemplateControllerGenerated;
use App\Entity\TalkTypeTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * TalkTypeTemplate Controller
 *
 * This controller handles all talkTypeTemplate operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see TalkTypeTemplateControllerGenerated for available lifecycle hooks
 */
#[Route('/talktypetemplate')]
final class TalkTypeTemplateController extends TalkTypeTemplateControllerGenerated
{
    /**
     * List all talkTypeTemplates
     */
    #[Route('', name: 'talktypetemplate_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching talkTypeTemplates
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'talktypetemplate_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new talkTypeTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'talktypetemplate_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing talkTypeTemplate
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'talktypetemplate_edit', methods: ['GET', 'POST'])]
    public function edit(TalkTypeTemplate $talkTypeTemplate, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($talkTypeTemplate, $request);
        }

        return $this->editFormAction($talkTypeTemplate, $request);
    }

    /**
     * Delete talkTypeTemplate
     */
    #[Route('/{id}', name: 'talktypetemplate_delete', methods: ['POST'])]
    public function delete(TalkTypeTemplate $talkTypeTemplate, Request $request): Response
    {
        return $this->deleteAction($talkTypeTemplate, $request);
    }

    /**
     * Show talkTypeTemplate details
     */
    #[Route('/{id}', name: 'talktypetemplate_show', methods: ['GET'])]
    public function show(TalkTypeTemplate $talkTypeTemplate): Response
    {
        return $this->showAction($talkTypeTemplate);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(TalkTypeTemplate $talkTypeTemplate): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($talkTypeTemplate);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new TalkTypeTemplateCreatedEvent($talkTypeTemplate));
    // }
    //
    // protected function beforeDelete(TalkTypeTemplate $talkTypeTemplate): void
    // {
    //     // Check for dependencies
    //     // if ($talkTypeTemplate->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete talkTypeTemplate with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
