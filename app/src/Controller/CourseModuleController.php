<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CourseModuleControllerGenerated;
use App\Entity\Course;
use App\Entity\CourseModule;
use App\Security\Voter\CourseVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CourseModule Controller
 *
 * This controller handles all courseModule operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CourseModuleControllerGenerated for available lifecycle hooks
 */
#[Route('/coursemodule')]
final class CourseModuleController extends CourseModuleControllerGenerated
{
    // ====================================
    // COURSE-SPECIFIC MODULE ROUTES
    // ====================================

    /**
     * Create new module for a specific course
     */
    #[Route('/course/{courseId}/new', name: 'course_module_new', methods: ['GET', 'POST'])]
    public function newForCourse(
        #[MapEntity(id: 'courseId')] Course $course,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        if ($request->isMethod('POST')) {
            // Handle form submission directly (don't call parent createAction)
            $courseModule = new CourseModule();
            $courseModule->setCourse($course);

            $this->initializeNewEntity($courseModule);

            $form = $this->createForm(\App\Form\CourseModuleType::class, $courseModule);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->beforeCreate($courseModule);
                    $this->entityManager->persist($courseModule);
                    $this->entityManager->flush();
                    $this->afterCreate($courseModule);

                    // For now, just redirect - we'll implement proper Turbo Streams later
                    // Turbo will handle this smoothly without full browser reload
                    $this->addFlash('success', $this->translator->trans(
                        'coursemodule.flash.created_successfully',
                        ['%name%' => (string) $courseModule],
                        'coursemodule'
                    ));

                    return $this->redirectToRoute('course_show', [
                        'id' => $course->getId()
                    ], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->translator->trans(
                        'coursemodule.flash.create_failed',
                        ['%error%' => $e->getMessage()],
                        'coursemodule'
                    ));
                }
            }

            // Re-render with errors
            return $this->render('coursemodule/_form_modal.html.twig', [
                'form' => $form,
                'courseModule' => $courseModule,
                'is_edit' => false,
                'form_action' => $this->generateUrl('course_module_new', [
                    'courseId' => $course->getId()
                ]),
            ], new Response('', $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
        }

        // Render new form with course-specific action URL
        $courseModule = new CourseModule();
        $courseModule->setCourse($course);

        $this->initializeNewEntity($courseModule);

        $form = $this->createForm(\App\Form\CourseModuleType::class, $courseModule);

        return $this->render('coursemodule/_form_modal.html.twig', [
            'form' => $form,
            'courseModule' => $courseModule,
            'is_edit' => false,
            'form_action' => $this->generateUrl('course_module_new', [
                'courseId' => $course->getId()
            ]),
        ]);
    }

    /**
     * Edit module from course context
     */
    #[Route('/course/{courseId}/module/{moduleId}/edit', name: 'course_module_edit', methods: ['GET', 'POST'])]
    public function editFromCourse(
        #[MapEntity(id: 'courseId')] Course $course,
        #[MapEntity(id: 'moduleId')] CourseModule $courseModule,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        // Verify module belongs to this course
        if ($courseModule->getCourse()->getId()->toString() !== $course->getId()->toString()) {
            throw $this->createNotFoundException('Module not found in this course');
        }

        if ($request->isMethod('POST')) {
            // Handle form submission directly (don't call parent updateAction)
            $form = $this->createForm(\App\Form\CourseModuleType::class, $courseModule);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->beforeUpdate($courseModule);
                    $this->entityManager->flush();
                    $this->afterUpdate($courseModule);

                    $this->addFlash('success', $this->translator->trans(
                        'coursemodule.flash.updated_successfully',
                        ['%name%' => (string) $courseModule],
                        'coursemodule'
                    ));

                    // Return 303 See Other to refresh via Turbo without full reload
                    return $this->redirectToRoute('course_show', [
                        'id' => $course->getId()
                    ], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->translator->trans(
                        'coursemodule.flash.update_failed',
                        ['%error%' => $e->getMessage()],
                        'coursemodule'
                    ));
                }
            }

            // Re-render with errors
            return $this->render('coursemodule/_form_modal.html.twig', [
                'form' => $form,
                'courseModule' => $courseModule,
                'is_edit' => true,
                'form_action' => $this->generateUrl('course_module_edit', [
                    'courseId' => $course->getId(),
                    'moduleId' => $courseModule->getId()
                ]),
            ], new Response('', $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
        }

        // Render edit form with course-specific action URL
        $form = $this->createForm(\App\Form\CourseModuleType::class, $courseModule);

        return $this->render('coursemodule/_form_modal.html.twig', [
            'form' => $form,
            'courseModule' => $courseModule,
            'is_edit' => true,
            'form_action' => $this->generateUrl('course_module_edit', [
                'courseId' => $course->getId(),
                'moduleId' => $courseModule->getId()
            ]),
        ]);
    }

    /**
     * Delete module from course context
     */
    #[Route('/course/{courseId}/module/{moduleId}/delete', name: 'course_module_delete', methods: ['POST'])]
    public function deleteFromCourse(
        #[MapEntity(id: 'courseId')] Course $course,
        #[MapEntity(id: 'moduleId')] CourseModule $courseModule,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        // Verify module belongs to this course
        if ($courseModule->getCourse()->getId()->toString() !== $course->getId()->toString()) {
            throw $this->createNotFoundException('Module not found in this course');
        }

        $response = $this->deleteAction($courseModule, $request);

        // If success redirect, refresh page via Turbo
        if ($response->isRedirect()) {
            return new Response(
                '<turbo-stream action="refresh"></turbo-stream>',
                Response::HTTP_OK,
                ['Content-Type' => 'text/vnd.turbo-stream.html']
            );
        }

        return $response;
    }

    // ====================================
    // STANDARD ROUTES
    // ====================================

    /**
     * List all courseModules
     */
    #[Route('', name: 'coursemodule_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching courseModules
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'coursemodule_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new courseModule
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'coursemodule_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing courseModule
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'coursemodule_edit', methods: ['GET', 'POST'])]
    public function edit(CourseModule $courseModule, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($courseModule, $request);
        }

        return $this->editFormAction($courseModule, $request);
    }

    /**
     * Delete courseModule
     */
    #[Route('/{id}', name: 'coursemodule_delete', methods: ['POST'])]
    public function delete(CourseModule $courseModule, Request $request): Response
    {
        return $this->deleteAction($courseModule, $request);
    }

    /**
     * Show courseModule details
     */
    #[Route('/{id}', name: 'coursemodule_show', methods: ['GET'])]
    public function show(CourseModule $courseModule): Response
    {
        return $this->showAction($courseModule);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    /**
     * Initialize new entity with course relationship from request context
     */
    protected function initializeNewEntity(CourseModule $courseModule): void
    {
        // Get course from request attributes (set in newForCourse method)
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request && $request->attributes->has('_course')) {
            $course = $request->attributes->get('_course');
            $courseModule->setCourse($course);
        }

        // Note: CourseModule doesn't have organization field - it inherits from Course
    }
}
