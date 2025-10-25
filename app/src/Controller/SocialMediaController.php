<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\SocialMediaControllerGenerated;
use App\Entity\SocialMedia;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * SocialMedia Controller
 *
 * This controller handles all socialMedia operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see SocialMediaControllerGenerated for available lifecycle hooks
 */
#[Route('/socialmedia')]
final class SocialMediaController extends SocialMediaControllerGenerated
{
    /**
     * List all socialMedias
     */
    #[Route('', name: 'socialmedia_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching socialMedias
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'socialmedia_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new socialMedia
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'socialmedia_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing socialMedia
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'socialmedia_edit', methods: ['GET', 'POST'])]
    public function edit(SocialMedia $socialMedia, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($socialMedia, $request);
        }

        return $this->editFormAction($socialMedia, $request);
    }

    /**
     * Delete socialMedia
     */
    #[Route('/{id}', name: 'socialmedia_delete', methods: ['POST'])]
    public function delete(SocialMedia $socialMedia, Request $request): Response
    {
        return $this->deleteAction($socialMedia, $request);
    }

    /**
     * Show socialMedia details
     */
    #[Route('/{id}', name: 'socialmedia_show', methods: ['GET'])]
    public function show(SocialMedia $socialMedia): Response
    {
        return $this->showAction($socialMedia);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(SocialMedia $socialMedia): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($socialMedia);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new SocialMediaCreatedEvent($socialMedia));
    // }
    //
    // protected function beforeDelete(SocialMedia $socialMedia): void
    // {
    //     // Check for dependencies
    //     // if ($socialMedia->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete socialMedia with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
