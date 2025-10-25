<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\SocialMediaTypeControllerGenerated;
use App\Entity\SocialMediaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * SocialMediaType Controller
 *
 * This controller handles all socialMediaType operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see SocialMediaTypeControllerGenerated for available lifecycle hooks
 */
#[Route('/socialmediatype')]
final class SocialMediaTypeController extends SocialMediaTypeControllerGenerated
{
    /**
     * List all socialMediaTypes
     */
    #[Route('', name: 'socialmediatype_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching socialMediaTypes
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'socialmediatype_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new socialMediaType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'socialmediatype_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing socialMediaType
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'socialmediatype_edit', methods: ['GET', 'POST'])]
    public function edit(SocialMediaType $socialMediaType, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($socialMediaType, $request);
        }

        return $this->editFormAction($socialMediaType, $request);
    }

    /**
     * Delete socialMediaType
     */
    #[Route('/{id}', name: 'socialmediatype_delete', methods: ['POST'])]
    public function delete(SocialMediaType $socialMediaType, Request $request): Response
    {
        return $this->deleteAction($socialMediaType, $request);
    }

    /**
     * Show socialMediaType details
     */
    #[Route('/{id}', name: 'socialmediatype_show', methods: ['GET'])]
    public function show(SocialMediaType $socialMediaType): Response
    {
        return $this->showAction($socialMediaType);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(SocialMediaType $socialMediaType): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($socialMediaType);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new SocialMediaTypeCreatedEvent($socialMediaType));
    // }
    //
    // protected function beforeDelete(SocialMediaType $socialMediaType): void
    // {
    //     // Check for dependencies
    //     // if ($socialMediaType->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete socialMediaType with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
