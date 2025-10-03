<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CourseModule;
use App\Form\CourseModuleFormType;
use App\Repository\CourseRepository;
use App\Repository\CourseModuleRepository;
use App\Security\Voter\CourseVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/course/{courseId}/module', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
final class CourseModuleController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CourseRepository $courseRepository,
        private readonly CourseModuleRepository $moduleRepository,
    ) {}

    #[Route('/new', name: 'course_module_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $courseId): Response
    {
        $course = $this->courseRepository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        $module = new CourseModule();
        $module->setCourse($course);

        // Set default view order to be last
        $existingModules = $this->moduleRepository->createQueryBuilder('cm')
            ->where('cm.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult();
        $module->setViewOrder(count($existingModules) + 1);

        $form = $this->createForm(CourseModuleFormType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($module);
            $this->entityManager->flush();

            $this->addFlash('success', 'course.module.flash.created_successfully');

            return $this->redirectToRoute('course_show', ['id' => $courseId]);
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('course/_module_form_modal.html.twig', [
                'course' => $course,
                'module' => $module,
                'form' => $form,
                'is_edit' => false,
            ]);
        }

        return $this->render('course/module_new.html.twig', [
            'course' => $course,
            'module' => $module,
            'form' => $form,
        ]);
    }

    #[Route('/{moduleId}/edit', name: 'course_module_edit', requirements: ['moduleId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET', 'POST'])]
    public function edit(Request $request, string $courseId, string $moduleId): Response
    {
        $course = $this->courseRepository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        $module = $this->moduleRepository->find($moduleId);
        if (!$module || $module->getCourse()->getId()->toString() !== $courseId) {
            throw $this->createNotFoundException('Module not found');
        }

        $form = $this->createForm(CourseModuleFormType::class, $module, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'course.module.flash.updated_successfully');

            return $this->redirectToRoute('course_show', ['id' => $courseId]);
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('course/_module_form_modal.html.twig', [
                'course' => $course,
                'module' => $module,
                'form' => $form,
                'is_edit' => true,
            ]);
        }

        return $this->render('course/module_edit.html.twig', [
            'course' => $course,
            'module' => $module,
            'form' => $form,
        ]);
    }

    #[Route('/{moduleId}/delete', name: 'course_module_delete', requirements: ['moduleId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST', 'DELETE'])]
    public function delete(Request $request, string $courseId, string $moduleId): Response
    {
        $course = $this->courseRepository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        $module = $this->moduleRepository->find($moduleId);
        if (!$module || $module->getCourse()->getId()->toString() !== $courseId) {
            throw $this->createNotFoundException('Module not found');
        }

        if ($this->isCsrfTokenValid('delete-module-' . $moduleId, $request->request->get('_token'))) {
            $this->entityManager->remove($module);
            $this->entityManager->flush();

            $this->addFlash('success', 'course.module.flash.deleted_successfully');
        } else {
            $this->addFlash('error', 'course.module.flash.invalid_csrf_token');
        }

        return $this->redirectToRoute('course_show', ['id' => $courseId]);
    }

    #[Route('/reorder', name: 'course_modules_reorder', methods: ['POST'])]
    public function reorder(Request $request, string $courseId): Response
    {
        $course = $this->courseRepository->find($courseId);
        if (!$course) {
            return $this->json(['success' => false, 'message' => 'Course not found'], 404);
        }

        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        $data = json_decode($request->getContent(), true);

        if (!isset($data['modules']) || !is_array($data['modules'])) {
            return $this->json(['success' => false, 'message' => 'Invalid request data'], 400);
        }

        try {
            foreach ($data['modules'] as $moduleData) {
                if (!isset($moduleData['id']) || !isset($moduleData['viewOrder'])) {
                    continue;
                }

                $module = $this->moduleRepository->find($moduleData['id']);

                // Verify module belongs to this course
                if (!$module || $module->getCourse()->getId()->toString() !== $course->getId()->toString()) {
                    continue;
                }

                $module->setViewOrder((int)$moduleData['viewOrder']);
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Module order updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to update module order: ' . $e->getMessage()
            ], 500);
        }
    }
}
