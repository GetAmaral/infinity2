<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\StudentCourseControllerGenerated;
use App\Entity\StudentCourse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * StudentCourse Controller
 *
 * This controller handles all studentCourse operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see StudentCourseControllerGenerated for available lifecycle hooks
 */
#[Route('/studentcourse')]
final class StudentCourseController extends StudentCourseControllerGenerated
{
    /**
     * List all studentCourses
     */
    #[Route('', name: 'studentcourse_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching studentCourses
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'studentcourse_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new studentCourse
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'studentcourse_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing studentCourse
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'studentcourse_edit', methods: ['GET', 'POST'])]
    public function edit(StudentCourse $studentCourse, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($studentCourse, $request);
        }

        return $this->editFormAction($studentCourse, $request);
    }

    /**
     * Delete studentCourse
     */
    #[Route('/{id}', name: 'studentcourse_delete', methods: ['POST'])]
    public function delete(StudentCourse $studentCourse, Request $request): Response
    {
        return $this->deleteAction($studentCourse, $request);
    }

    /**
     * Show studentCourse details
     */
    #[Route('/{id}', name: 'studentcourse_show', methods: ['GET'])]
    public function show(StudentCourse $studentCourse): Response
    {
        return $this->showAction($studentCourse);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(StudentCourse $studentCourse): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($studentCourse);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new StudentCourseCreatedEvent($studentCourse));
    // }
    //
    // protected function beforeDelete(StudentCourse $studentCourse): void
    // {
    //     // Check for dependencies
    //     // if ($studentCourse->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete studentCourse with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
