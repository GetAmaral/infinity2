<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\TagControllerGenerated;
use App\Entity\Tag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Tag Controller
 *
 * This controller handles all tag operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see TagControllerGenerated for available lifecycle hooks
 */
#[Route('/tag')]
final class TagController extends TagControllerGenerated
{
    /**
     * List all tags
     */
    #[Route('', name: 'tag_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching tags
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'tag_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new tag
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'tag_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing tag
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'tag_edit', methods: ['GET', 'POST'])]
    public function edit(Tag $tag, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($tag, $request);
        }

        return $this->editFormAction($tag, $request);
    }

    /**
     * Delete tag
     */
    #[Route('/{id}', name: 'tag_delete', methods: ['POST'])]
    public function delete(Tag $tag, Request $request): Response
    {
        return $this->deleteAction($tag, $request);
    }

    /**
     * Show tag details
     */
    #[Route('/{id}', name: 'tag_show', methods: ['GET'])]
    public function show(Tag $tag): Response
    {
        return $this->showAction($tag);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Tag $tag): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($tag);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new TagCreatedEvent($tag));
    // }
    //
    // protected function beforeDelete(Tag $tag): void
    // {
    //     // Check for dependencies
    //     // if ($tag->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete tag with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
