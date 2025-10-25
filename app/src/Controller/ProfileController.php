<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\ProfileControllerGenerated;
use App\Entity\Profile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Profile Controller
 *
 * This controller handles all profile operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see ProfileControllerGenerated for available lifecycle hooks
 */
#[Route('/profile')]
final class ProfileController extends ProfileControllerGenerated
{
    /**
     * List all profiles
     */
    #[Route('', name: 'profile_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching profiles
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'profile_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new profile
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'profile_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing profile
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'profile_edit', methods: ['GET', 'POST'])]
    public function edit(Profile $profile, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($profile, $request);
        }

        return $this->editFormAction($profile, $request);
    }

    /**
     * Delete profile
     */
    #[Route('/{id}', name: 'profile_delete', methods: ['POST'])]
    public function delete(Profile $profile, Request $request): Response
    {
        return $this->deleteAction($profile, $request);
    }

    /**
     * Show profile details
     */
    #[Route('/{id}', name: 'profile_show', methods: ['GET'])]
    public function show(Profile $profile): Response
    {
        return $this->showAction($profile);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Profile $profile): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($profile);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new ProfileCreatedEvent($profile));
    // }
    //
    // protected function beforeDelete(Profile $profile): void
    // {
    //     // Check for dependencies
    //     // if ($profile->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete profile with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
