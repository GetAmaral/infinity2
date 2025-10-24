<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Base\BaseApiController;
use App\Entity\Course;
use App\Entity\CourseLecture;
use App\Entity\CourseModule;
use App\Entity\StudentCourse;
use App\Form\CourseFormType;
use App\Form\CourseLectureFormType;
use App\Message\ProcessVideoMessage;
use App\Repository\CourseRepository;
use App\Repository\CourseLectureRepository;
use App\Repository\CourseModuleRepository;
use App\Repository\StudentCourseRepository;
use App\Repository\UserRepository;
use App\Service\ListPreferencesService;
use App\Security\Voter\CourseVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @extends BaseApiController<Course>
 */
#[Route('/course')]
final class CourseController extends BaseApiController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CourseRepository $repository,
        private readonly CourseModuleRepository $moduleRepository,
        private readonly CourseLectureRepository $lectureRepository,
        private readonly StudentCourseRepository $studentCourseRepository,
        private readonly UserRepository $userRepository,
        private readonly ListPreferencesService $listPreferencesService,
        private readonly MessageBusInterface $messageBus,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        #[Autowire(param: 'app.videos.originals_path')]
        private readonly string $videosOriginalsPath
    ) {}

    #[Route('', name: 'course_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(CourseVoter::LIST);

        // Get saved view preference from list preferences
        $preferences = $this->listPreferencesService->getEntityPreferences('courses');
        $savedView = $preferences['view'] ?? 'grid';

        return $this->render('course/index.html.twig', [
            // Generic entity list variables for base template
            'entities' => [], // Empty - JS will load via API
            'entity_name' => 'course',
            'entity_name_plural' => 'courses',
            'page_icon' => 'bi bi-book',
            'default_view' => $savedView, // Use saved preference
            'enable_search' => true,
            'enable_filters' => true,
            'enable_create_button' => true,

            // Backward compatibility: keep old variable name
            'courses' => [], // Empty - JS will load via API
        ]);
    }

    #[Route('/new', name: 'course_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(CourseVoter::CREATE);

        $course = new Course();

        // Set organization and owner from current user
        $user = $this->getUser();
        if ($user && $user->getOrganization()) {
            $course->setOrganization($user->getOrganization());
        }
        if ($user) {
            $course->setOwner($user);
        }

        $form = $this->createForm(CourseFormType::class, $course, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($course);
            $this->entityManager->flush();

            $this->addFlash('success', 'course.flash.created_successfully');

            return $this->redirectToRefererOrRoute($request, 'course_index');
        }

        // Always render modal template (courses are created via modal)
        return $this->render('course/_form_modal.html.twig', [
            'course' => $course,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}', name: 'course_show', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function show(Course $course): Response
    {
        $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);

        // Get modules ordered by viewOrder
        $modules = $this->moduleRepository->createQueryBuilder('cm')
            ->where('cm.course = :course')
            ->setParameter('course', $course)
            ->orderBy('cm.viewOrder', 'ASC')
            ->addOrderBy('cm.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Get ALL users for enrollment (including enrolled ones for Tom Select multi-select)
        $availableUsers = [];
        if ($this->isGranted(CourseVoter::MANAGE_ENROLLMENTS, $course)) {
            // Get all users from the same organization for Tom Select
            $availableUsers = $this->userRepository->createQueryBuilder('u')
                ->where('u.organization = :organization')
                ->setParameter('organization', $course->getOrganization())
                ->orderBy('u.name', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'modules' => $modules,
            'availableUsers' => $availableUsers,
        ]);
    }

    #[Route('/{id}/edit', name: 'course_edit', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course): Response
    {
        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        $form = $this->createForm(CourseFormType::class, $course, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'course.flash.updated_successfully');

            return $this->redirectToRefererOrRoute($request, 'course_index');
        }

        // Always render modal template (courses are edited via modal)
        return $this->render('course/_form_modal.html.twig', [
            'course' => $course,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'course_delete', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Course $course): Response
    {
        $this->denyAccessUnlessGranted(CourseVoter::DELETE, $course);

        $courseId = $course->getId()?->toString();
        $courseName = $course->getName();

        if ($this->isCsrfTokenValid('delete-course-' . $courseId, $request->request->get('_token'))) {
            $this->entityManager->remove($course);
            $this->entityManager->flush();

            // Return JSON for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true, 'message' => 'Course deleted successfully']);
            }

            $this->addFlash('success', 'course.flash.deleted_successfully');

            // Return Turbo Stream response for seamless UX
            if ($request->headers->get('Accept') === 'text/vnd.turbo-stream.html') {
                return $this->render('course/_turbo_stream_deleted.html.twig', [
                    'courseId' => $courseId,
                    'courseName' => $courseName,
                ]);
            }
        } else {
            // Return JSON error for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 400);
            }

            $this->addFlash('error', 'common.error.invalid_csrf');
        }

        return $this->redirectToRoute('course_index');
    }


    #[Route('/{courseId}/module/{moduleId}/lecture/new', name: 'course_module_lecture_new', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}', 'moduleId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET', 'POST'])]
    public function newLecture(Request $request, string $courseId, string $moduleId): Response
    {
        $course = $this->repository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $module = $this->moduleRepository->find($moduleId);
        if (!$module || $module->getCourse()->getId()->toString() !== $courseId) {
            throw $this->createNotFoundException('Module not found');
        }

        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        $lecture = new CourseLecture();
        $lecture->setCourseModule($module);

        // Set default view order to be last in this module
        $existingLectures = $this->lectureRepository->findByModuleOrdered($moduleId);
        $lecture->setViewOrder(count($existingLectures) + 1);

        $form = $this->createForm(CourseLectureFormType::class, $lecture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($lecture);
            $this->entityManager->flush();

            // Dispatch video processing if file was uploaded
            if ($lecture->getVideoFileName()) {
                $originalPath = $this->videosOriginalsPath . '/' . $lecture->getVideoFileName();
                $this->messageBus->dispatch(new ProcessVideoMessage(
                    $lecture->getId()->toString(),
                    $originalPath
                ));

                $this->addFlash('info', 'Video is being processed in background. Duration will be calculated automatically.');
            }

            $this->addFlash('success', 'course.lecture.flash.created_successfully');

            return $this->redirectToRoute('course_show', ['id' => $courseId]);
        }

        return $this->render('course/_lecture_form_modal.html.twig', [
            'course' => $course,
            'module' => $module,
            'lecture' => $lecture,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{courseId}/lecture/{lectureId}/edit', name: 'course_lecture_edit', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}', 'lectureId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET', 'POST'])]
    public function editLecture(Request $request, string $courseId, string $lectureId): Response
    {
        $course = $this->repository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        $lecture = $this->lectureRepository->find($lectureId);
        if (!$lecture || $lecture->getCourseModule()->getCourse()->getId()->toString() !== $courseId) {
            throw $this->createNotFoundException('Lecture not found');
        }

        // Store old video filename before form handling (for cleanup if replaced)
        $oldVideoFileName = $lecture->getVideoFileName();

        $form = $this->createForm(CourseLectureFormType::class, $lecture, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if a new video file was uploaded
            $hasNewVideo = $lecture->getVideoFile() !== null;

            // If new video uploaded, delete old video files completely
            if ($hasNewVideo && $oldVideoFileName && $oldVideoFileName !== $lecture->getVideoFileName()) {
                // Delete old original video file
                $oldOriginalPath = $this->videosOriginalsPath . '/' . $oldVideoFileName;
                if (file_exists($oldOriginalPath)) {
                    unlink($oldOriginalPath);
                }

                // Delete old HLS processed files directory
                $oldHlsDir = $this->videosHlsPath . '/' . $lecture->getId()->toString();
                if (is_dir($oldHlsDir)) {
                    $this->deleteDirectory($oldHlsDir);
                }

                // Reset processing status for new video
                $lecture->setProcessingStatus('pending');
                $lecture->setProcessingPercentage(0);
                $lecture->setProcessingStep(null);
                $lecture->setLengthSeconds(0);
            }

            $this->entityManager->flush();

            // Dispatch video processing if new file was uploaded
            if ($hasNewVideo && $lecture->getVideoFileName()) {
                $originalPath = $this->videosOriginalsPath . '/' . $lecture->getVideoFileName();
                $this->messageBus->dispatch(new ProcessVideoMessage(
                    $lecture->getId()->toString(),
                    $originalPath
                ));

                $this->addFlash('info', 'Video is being processed in background.');
            }

            $this->addFlash('success', 'course.lecture.flash.updated_successfully');

            return $this->redirectToRoute('course_show', ['id' => $courseId]);
        }

        return $this->render('course/_lecture_form_modal.html.twig', [
            'course' => $course,
            'lecture' => $lecture,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{courseId}/lecture/{lectureId}/delete', name: 'course_lecture_delete', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}', 'lectureId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST', 'DELETE'])]
    public function deleteLecture(Request $request, string $courseId, string $lectureId): Response
    {
        $course = $this->repository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        $lecture = $this->lectureRepository->find($lectureId);
        if (!$lecture || $lecture->getCourseModule()->getCourse()->getId()->toString() !== $courseId) {
            throw $this->createNotFoundException('Lecture not found');
        }

        if ($this->isCsrfTokenValid('delete-lecture-' . $lectureId, $request->request->get('_token'))) {
            $this->entityManager->remove($lecture);
            $this->entityManager->flush();

            // Return JSON for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true, 'message' => 'Lecture deleted successfully']);
            }

            $this->addFlash('success', 'course.lecture.flash.deleted_successfully');
        } else {
            // Return JSON error for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 400);
            }

            $this->addFlash('error', 'common.error.invalid_csrf');
        }

        return $this->redirectToRoute('course_show', ['id' => $courseId]);
    }

    #[Route('/{id}/lectures/reorder', name: 'course_lectures_reorder', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST'])]
    public function reorderLectures(Request $request, Course $course): Response
    {
        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        $data = json_decode($request->getContent(), true);

        if (!isset($data['lectures']) || !is_array($data['lectures'])) {
            return $this->json(['success' => false, 'message' => 'Invalid request data'], 400);
        }

        try {
            foreach ($data['lectures'] as $lectureData) {
                if (!isset($lectureData['id']) || !isset($lectureData['viewOrder'])) {
                    continue;
                }

                $lecture = $this->lectureRepository->find($lectureData['id']);

                // Verify lecture belongs to this course
                if (!$lecture || $lecture->getCourseModule()->getCourse()->getId()->toString() !== $course->getId()->toString()) {
                    continue;
                }

                // Update view order
                $lecture->setViewOrder((int)$lectureData['viewOrder']);

                // Handle module change if provided
                if (isset($lectureData['moduleId'])) {
                    $newModule = $this->moduleRepository->find($lectureData['moduleId']);

                    // Verify module belongs to this course
                    if ($newModule && $newModule->getCourse()->getId()->toString() === $course->getId()->toString()) {
                        $oldModule = $lecture->getCourseModule();
                        $lecture->setCourseModule($newModule);

                        // Recalculate module lengths
                        $this->entityManager->flush(); // Flush first to ensure lecture is in new module
                        $oldModule->calculateTotalLengthSeconds();
                        $newModule->calculateTotalLengthSeconds();
                        $course->calculateTotalLengthSeconds();
                    }
                }
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Lecture order updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to update lecture order: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{courseId}/modules/reorder', name: 'course_modules_reorder', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST'])]
    public function reorderModules(Request $request, #[MapEntity(id: 'courseId')] Course $course): Response
    {
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

                // Update view order
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

    #[Route('/{courseId}/lecture/{lectureId}/processing-status', name: 'course_lecture_processing_status', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}', 'lectureId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function getLectureProcessingStatus(string $courseId, string $lectureId): Response
    {
        $lecture = $this->lectureRepository->find($lectureId);

        if (!$lecture || $lecture->getCourseModule()->getCourse()->getId()->toString() !== $courseId) {
            return $this->json(['error' => 'Lecture not found'], 404);
        }

        $this->denyAccessUnlessGranted(CourseVoter::VIEW, $lecture->getCourseModule()->getCourse());

        return $this->json([
            'status' => $lecture->getProcessingStatus(),
            'step' => $lecture->getProcessingStep(),
            'percentage' => $lecture->getProcessingPercentage(),
            'error' => $lecture->getProcessingError(),
            'completed' => $lecture->getProcessingStatus() === 'completed',
            'failed' => $lecture->getProcessingStatus() === 'failed',
        ]);
    }

    #[Route('/api/search', name: 'course_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        $this->denyAccessUnlessGranted(CourseVoter::LIST);

        // Use parent class implementation (BaseApiController)
        // All logic delegated to CourseRepository via BaseRepository
        return $this->apiSearchAction($request);
    }

    /**
     * Get repository for BaseApiController
     */
    protected function getRepository(): CourseRepository
    {
        return $this->repository;
    }

    /**
     * Get entity plural name for JSON response
     */
    protected function getEntityPluralName(): string
    {
        return 'courses';
    }

    /**
     * Transform Course entity to array for JSON API response
     */
    #[Route('/{courseId}/lecture/{lectureId}/watch', name: 'course_lecture_watch', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}', 'lectureId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function watchLecture(string $courseId, string $lectureId): Response
    {
        $course = $this->repository->findWithModulesAndLectures($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $lecture = $this->lectureRepository->find($lectureId);
        if (!$lecture || $lecture->getCourseModule()->getCourse()->getId()->toString() !== $courseId) {
            throw $this->createNotFoundException('Lecture not found');
        }

        // Check if video is processed
        if ($lecture->getProcessingStatus() !== 'completed') {
            $this->addFlash('warning', 'This video is still being processed. Please check back later.');
            return $this->redirectToRoute('course_show', ['id' => $courseId]);
        }

        return $this->render('course/lecture_watch.html.twig', [
            'course' => $course,
            'lecture' => $lecture
        ]);
    }

    #[Route('/{courseId}/enrollment/add', name: 'course_enrollment_add', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST'])]
    public function addEnrollment(Request $request, string $courseId): Response
    {
        $course = $this->repository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $this->denyAccessUnlessGranted(CourseVoter::MANAGE_ENROLLMENTS, $course);

        if ($this->isCsrfTokenValid('enroll-student-' . $courseId, $request->request->get('_token'))) {
            $studentId = $request->request->get('studentId');
            $active = $request->request->get('active') === 'on';

            $student = $this->userRepository->find($studentId);

            if (!$student) {
                $this->addFlash('error', 'course.enrollment.flash.student_not_found');
                return $this->redirectToRoute('course_show', ['id' => $courseId]);
            }

            // Check if student is already enrolled
            $existingEnrollment = $this->studentCourseRepository->findOneBy([
                'course' => $course,
                'student' => $student
            ]);

            if ($existingEnrollment) {
                $this->addFlash('error', 'course.enrollment.flash.already_enrolled');
                return $this->redirectToRoute('course_show', ['id' => $courseId]);
            }

            // Create new enrollment
            $enrollment = new StudentCourse();
            $enrollment->setCourse($course);
            $enrollment->setStudent($student);
            $enrollment->setOrganization($course->getOrganization());
            $enrollment->setActive($active);

            $this->entityManager->persist($enrollment);
            $this->entityManager->flush();

            $this->addFlash('success', 'course.enrollment.flash.enrolled_successfully');
        } else {
            $this->addFlash('error', 'common.error.invalid_csrf');
        }

        return $this->redirectToRoute('course_show', ['id' => $courseId]);
    }

    #[Route('/{courseId}/enrollment/{enrollmentId}/toggle', name: 'course_enrollment_toggle', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}', 'enrollmentId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST'])]
    public function toggleEnrollment(Request $request, string $courseId, string $enrollmentId): Response
    {
        $course = $this->repository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $this->denyAccessUnlessGranted(CourseVoter::MANAGE_ENROLLMENTS, $course);

        $enrollment = $this->studentCourseRepository->find($enrollmentId);
        if (!$enrollment || $enrollment->getCourse()->getId()->toString() !== $courseId) {
            throw $this->createNotFoundException('Enrollment not found');
        }

        if ($this->isCsrfTokenValid('toggle-enrollment-' . $enrollmentId, $request->request->get('_token'))) {
            $enrollment->setActive(!$enrollment->isActive());
            $this->entityManager->flush();

            $status = $enrollment->isActive() ? 'activated' : 'deactivated';
            $this->addFlash('success', "course.enrollment.flash.{$status}_successfully");
        } else {
            $this->addFlash('error', 'common.error.invalid_csrf');
        }

        return $this->redirectToRoute('course_show', ['id' => $courseId]);
    }

    #[Route('/{courseId}/enrollment/{enrollmentId}/remove', name: 'course_enrollment_remove', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}', 'enrollmentId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST'])]
    public function removeEnrollment(Request $request, string $courseId, string $enrollmentId): Response
    {
        $course = $this->repository->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $this->denyAccessUnlessGranted(CourseVoter::MANAGE_ENROLLMENTS, $course);

        $enrollment = $this->studentCourseRepository->find($enrollmentId);
        if (!$enrollment || $enrollment->getCourse()->getId()->toString() !== $courseId) {
            throw $this->createNotFoundException('Enrollment not found');
        }

        if ($this->isCsrfTokenValid('remove-enrollment-' . $enrollmentId, $request->request->get('_token'))) {
            $this->entityManager->remove($enrollment);
            $this->entityManager->flush();

            $this->addFlash('success', 'course.enrollment.flash.removed_successfully');
        } else {
            $this->addFlash('error', 'common.error.invalid_csrf');
        }

        return $this->redirectToRoute('course_show', ['id' => $courseId]);
    }

    #[Route('/{courseId}/enrollment/add-multi', name: 'course_enrollment_add_multi', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST'])]
    public function addMultiEnrollment(Request $request, string $courseId): Response
    {
        $course = $this->repository->find($courseId);
        if (!$course) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        $this->denyAccessUnlessGranted(CourseVoter::MANAGE_ENROLLMENTS, $course);

        $data = json_decode($request->getContent(), true);

        if (!isset($data['studentIds']) || !is_array($data['studentIds'])) {
            return $this->json(['error' => 'Invalid student IDs'], 400);
        }

        $studentIds = $data['studentIds'];
        $active = $data['active'] ?? true;
        $enrolledCount = 0;
        $errors = [];

        foreach ($studentIds as $studentId) {
            $student = $this->userRepository->find($studentId);

            if (!$student) {
                $errors[] = "Student with ID {$studentId} not found";
                continue;
            }

            // Check if already enrolled
            $existingEnrollment = $this->studentCourseRepository->findOneBy([
                'course' => $course,
                'student' => $student
            ]);

            if ($existingEnrollment) {
                // Reactivate if inactive
                if (!$existingEnrollment->isActive()) {
                    $existingEnrollment->setActive(true);
                    $this->entityManager->flush();
                    $enrolledCount++;
                }
                continue;
            }

            // Create new enrollment
            $enrollment = new StudentCourse();
            $enrollment->setCourse($course);
            $enrollment->setStudent($student);
            $enrollment->setOrganization($course->getOrganization());
            $enrollment->setActive($active);

            $this->entityManager->persist($enrollment);
            $enrolledCount++;
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'enrolled' => $enrolledCount,
            'errors' => $errors,
            'message' => "Successfully enrolled {$enrolledCount} student(s)"
        ]);
    }

    #[Route('/{courseId}/enrollment/deactivate-multi', name: 'course_enrollment_deactivate_multi', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST'])]
    public function deactivateMultiEnrollment(Request $request, string $courseId): Response
    {
        $course = $this->repository->find($courseId);
        if (!$course) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        $this->denyAccessUnlessGranted(CourseVoter::MANAGE_ENROLLMENTS, $course);

        $data = json_decode($request->getContent(), true);

        if (!isset($data['studentIds']) || !is_array($data['studentIds'])) {
            return $this->json(['error' => 'Invalid student IDs'], 400);
        }

        $studentIds = $data['studentIds'];
        $deactivatedCount = 0;

        foreach ($studentIds as $studentId) {
            $student = $this->userRepository->find($studentId);

            if (!$student) {
                continue;
            }

            $enrollment = $this->studentCourseRepository->findOneBy([
                'course' => $course,
                'student' => $student
            ]);

            if ($enrollment && $enrollment->isActive()) {
                $enrollment->setActive(false);
                $deactivatedCount++;
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'deactivated' => $deactivatedCount,
            'message' => "Deactivated {$deactivatedCount} enrollment(s)"
        ]);
    }

    protected function entityToArray(object $entity): array
    {
        assert($entity instanceof Course);

        $courseId = $entity->getId()?->toString() ?? '';

        return [
            'id' => $courseId,
            'name' => $entity->getName(),
            'description' => $entity->getDescription() ?? '',
            'active' => $entity->isActive(),
            'releaseDate' => $entity->getReleaseDate()?->format('c'),
            'releaseDateFormatted' => $entity->getReleaseDate()?->format('M d, Y') ?? null,
            'totalLengthSeconds' => $entity->getTotalLengthSeconds(),
            'totalLengthFormatted' => $entity->getTotalLengthFormatted(),
            'organizationId' => $entity->getOrganization()->getId()?->toString() ?? '',
            'organizationName' => $entity->getOrganization()->getName() ?? '',
            'ownerId' => $entity->getOwner()->getId()?->toString() ?? '',
            'ownerName' => $entity->getOwner()->getName() ?? '',
            'modulesCount' => $entity->getModules()->count(),
            'enrolledStudentsCount' => $entity->getStudentCourses()->count(),
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'createdAtFormatted' => $entity->getCreatedAt()->format('M d, Y'),
            'deleteCsrfToken' => $this->csrfTokenManager->getToken('delete-course-' . $courseId)->getValue(),
        ];
    }

    /**
     * Recursively delete a directory and all its contents
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}
