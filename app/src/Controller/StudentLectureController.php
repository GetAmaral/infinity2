<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\StudentLectureControllerGenerated;
use App\Entity\StudentLecture;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * StudentLecture Controller
 *
 * This controller handles all studentLecture operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see StudentLectureControllerGenerated for available lifecycle hooks
 */
#[Route('/studentlecture')]
final class StudentLectureController extends StudentLectureControllerGenerated
{
    /**
     * List all studentLectures
     */
    #[Route('', name: 'studentlecture_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching studentLectures
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'studentlecture_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new studentLecture
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'studentlecture_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing studentLecture
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'studentlecture_edit', methods: ['GET', 'POST'])]
    public function edit(StudentLecture $studentLecture, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($studentLecture, $request);
        }

        return $this->editFormAction($studentLecture, $request);
    }

    /**
     * Delete studentLecture
     */
    #[Route('/{id}', name: 'studentlecture_delete', methods: ['POST'])]
    public function delete(StudentLecture $studentLecture, Request $request): Response
    {
        return $this->deleteAction($studentLecture, $request);
    }

    /**
     * Show studentLecture details
     */
    #[Route('/{id}', name: 'studentlecture_show', methods: ['GET'])]
    public function show(StudentLecture $studentLecture): Response
    {
        return $this->showAction($studentLecture);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(StudentLecture $studentLecture): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($studentLecture);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new StudentLectureCreatedEvent($studentLecture));
    // }
    //
    // protected function beforeDelete(StudentLecture $studentLecture): void
    // {
    //     // Check for dependencies
    //     // if ($studentLecture->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete studentLecture with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
