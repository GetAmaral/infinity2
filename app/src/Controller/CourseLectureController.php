<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CourseLectureControllerGenerated;
use App\Entity\CourseLecture;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CourseLecture Controller
 *
 * This controller handles all courseLecture operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CourseLectureControllerGenerated for available lifecycle hooks
 */
#[Route('/courselecture')]
final class CourseLectureController extends CourseLectureControllerGenerated
{
    /**
     * List all courseLectures
     */
    #[Route('', name: 'courselecture_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching courseLectures
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'courselecture_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new courseLecture
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'courselecture_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing courseLecture
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'courselecture_edit', methods: ['GET', 'POST'])]
    public function edit(CourseLecture $courseLecture, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($courseLecture, $request);
        }

        return $this->editFormAction($courseLecture, $request);
    }

    /**
     * Delete courseLecture
     */
    #[Route('/{id}', name: 'courselecture_delete', methods: ['POST'])]
    public function delete(CourseLecture $courseLecture, Request $request): Response
    {
        return $this->deleteAction($courseLecture, $request);
    }

    /**
     * Show courseLecture details
     */
    #[Route('/{id}', name: 'courselecture_show', methods: ['GET'])]
    public function show(CourseLecture $courseLecture): Response
    {
        return $this->showAction($courseLecture);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(CourseLecture $courseLecture): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($courseLecture);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new CourseLectureCreatedEvent($courseLecture));
    // }
    //
    // protected function beforeDelete(CourseLecture $courseLecture): void
    // {
    //     // Check for dependencies
    //     // if ($courseLecture->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete courseLecture with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
