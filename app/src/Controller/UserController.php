<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\StudentCourse;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use App\Repository\OrganizationRepository;
use App\Repository\CourseRepository;
use App\Repository\StudentCourseRepository;
use App\Service\ListPreferencesService;
use App\MultiTenant\TenantContext;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @extends BaseApiController<User>
 */
#[Route('/user')]
final class UserController extends BaseApiController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $repository,
        private readonly OrganizationRepository $organizationRepository,
        private readonly CourseRepository $courseRepository,
        private readonly StudentCourseRepository $studentCourseRepository,
        private readonly ListPreferencesService $listPreferencesService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly TenantContext $tenantContext
    ) {}

    #[Route('', name: 'user_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::LIST);

        // Get saved view preference from list preferences
        $preferences = $this->listPreferencesService->getEntityPreferences('users');
        $savedView = $preferences['view'] ?? 'grid';

        return $this->render('user/index.html.twig', [
            // Generic entity list variables for base template
            'entities' => [], // Empty - JS will load via API
            'entity_name' => 'user',
            'entity_name_plural' => 'users',
            'page_icon' => 'bi bi-people',
            'default_view' => $savedView, // Use saved preference
            'enable_search' => true,
            'enable_filters' => true,
            'enable_create_button' => true,

            // Backward compatibility: keep old variable name
            'users' => [], // Empty - JS will load via API
        ]);
    }

    #[Route('/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::CREATE);

        $user = new User();

        // Check if there's an active organization context
        // Only show organization field if no context (root access without active organization)
        $hasActiveOrganization = $this->tenantContext->hasTenant();
        $showOrganizationField = !$hasActiveOrganization;

        // Get active organization if available
        $activeOrganization = null;
        if ($hasActiveOrganization) {
            $orgId = $this->tenantContext->getTenantId();
            if ($orgId) {
                $activeOrganization = $this->organizationRepository->find($orgId);
            }
        }

        $form = $this->createForm(UserFormType::class, $user, [
            'is_edit' => false,
            'show_organization_field' => $showOrganizationField,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If organization field is not shown, automatically set from context
            if (!$showOrganizationField && $activeOrganization) {
                $user->setOrganization($activeOrganization);
            }

            // Hash password if provided
            if ($form->has('plainPassword') && $plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'user.flash.created_successfully');

            // Redirect to user show page if student (for course enrollment), otherwise to user list
            if ($user->hasRole('student')) {
                return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
            }

            return $this->redirectToRoute('user_index');
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('user/_form_modal.html.twig', [
                'user' => $user,
                'form' => $form,
                'is_edit' => false,
            ]);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'user_show', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function show(User $user): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::VIEW, $user);

        // Check if user has ROLE_STUDENT
        $isStudent = $user->hasRole('student');

        // Get all courses from user's organization for enrollment management
        $availableCourses = [];
        if ($isStudent && $user->getOrganization()) {
            $availableCourses = $this->courseRepository->createQueryBuilder('c')
                ->where('c.organization = :organization')
                ->setParameter('organization', $user->getOrganization())
                ->orderBy('c.name', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'isStudent' => $isStudent,
            'availableCourses' => $availableCourses,
        ]);
    }

    #[Route('/{id}/edit', name: 'user_edit', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

        // Check if there's an active organization context
        // Only show organization field if no context (root access without active organization)
        $hasActiveOrganization = $this->tenantContext->hasTenant();
        $showOrganizationField = !$hasActiveOrganization;

        // Get active organization if available
        $activeOrganization = null;
        if ($hasActiveOrganization) {
            $orgId = $this->tenantContext->getTenantId();
            if ($orgId) {
                $activeOrganization = $this->organizationRepository->find($orgId);
            }
        }

        $form = $this->createForm(UserFormType::class, $user, [
            'is_edit' => true,
            'show_organization_field' => $showOrganizationField,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If organization field is not shown, ensure organization remains from context
            // (don't change it during edit if context is active)
            if (!$showOrganizationField && $activeOrganization && !$user->getOrganization()) {
                $user->setOrganization($activeOrganization);
            }

            // Hash password if provided
            if ($form->has('plainPassword') && $plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'user.flash.updated_successfully');

            // Redirect back to referer if it's an organization users page
            return $this->redirectToRefererOrRoute($request, 'user_index');
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('user/_form_modal.html.twig', [
                'user' => $user,
                'form' => $form,
                'is_edit' => true,
            ]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'user_delete', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST', 'DELETE'])]
    public function delete(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::DELETE, $user);

        $userId = $user->getId()?->toString();
        $userName = $user->getName();

        if ($this->isCsrfTokenValid('delete', $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'user.flash.deleted_successfully');

            // Return Turbo Stream response for seamless UX
            if ($request->headers->get('Accept') === 'text/vnd.turbo-stream.html') {
                return $this->render('user/_turbo_stream_deleted.html.twig', [
                    'userId' => $userId,
                    'userName' => $userName,
                ]);
            }
        } else {
            $this->addFlash('error', 'common.error.invalid_csrf');
        }

        return $this->redirectToRoute('user_index');
    }

    #[Route('/api/search', name: 'user_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::LIST);

        // Use parent class implementation (BaseApiController)
        // All logic delegated to UserRepository via BaseRepository
        return $this->apiSearchAction($request);
    }

    #[Route('/{userId}/course-enrollment/toggle', name: 'user_course_enrollment_toggle', requirements: ['userId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST'])]
    public function toggleCourseEnrollment(Request $request, string $userId): Response
    {
        $user = $this->repository->find($userId);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

        $data = json_decode($request->getContent(), true);

        if (!isset($data['courseId'])) {
            return $this->json(['success' => false, 'message' => 'Course ID is required'], 400);
        }

        $courseId = $data['courseId'];
        $shouldEnroll = $data['enroll'] ?? false;

        $course = $this->courseRepository->find($courseId);
        if (!$course) {
            return $this->json(['success' => false, 'message' => 'Course not found'], 404);
        }

        // Check if enrollment already exists
        $enrollment = $this->studentCourseRepository->findOneBy([
            'student' => $user,
            'course' => $course
        ]);

        if ($shouldEnroll) {
            // Enroll or reactivate
            if ($enrollment) {
                // Reactivate if inactive
                if (!$enrollment->isActive()) {
                    $enrollment->setActive(true);
                    $this->entityManager->flush();
                    return $this->json([
                        'success' => true,
                        'message' => 'Student enrollment reactivated successfully'
                    ]);
                }
                return $this->json([
                    'success' => true,
                    'message' => 'Student already enrolled'
                ]);
            }

            // Create new enrollment
            $newEnrollment = new StudentCourse();
            $newEnrollment->setStudent($user);
            $newEnrollment->setCourse($course);
            $newEnrollment->setOrganization($course->getOrganization());
            $newEnrollment->setActive(true);

            $this->entityManager->persist($newEnrollment);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Student enrolled successfully'
            ]);
        } else {
            // Unenroll (deactivate)
            if ($enrollment && $enrollment->isActive()) {
                $enrollment->setActive(false);
                $this->entityManager->flush();

                return $this->json([
                    'success' => true,
                    'message' => 'Student unenrolled successfully'
                ]);
            }

            return $this->json([
                'success' => true,
                'message' => 'Student was not enrolled'
            ]);
        }
    }

    /**
     * Get repository for BaseApiController
     */
    protected function getRepository(): UserRepository
    {
        return $this->repository;
    }

    /**
     * Get entity plural name for JSON response
     */
    protected function getEntityPluralName(): string
    {
        return 'users';
    }

    /**
     * Transform User entity to array for JSON API response
     */
    protected function entityToArray(object $entity): array
    {
        assert($entity instanceof User);

        $userId = $entity->getId()?->toString() ?? '';

        // Get role names - getRoles() returns array of strings like ['ROLE_USER', 'ROLE_ADMIN']
        $roles = $entity->getRoles();
        $rolesDisplay = !empty($roles) ? implode(', ', array_map(fn($r) => ucfirst(strtolower(str_replace('ROLE_', '', $r))), $roles)) : 'User';

        // Check if user is currently locked
        $isLocked = $entity->getLockedUntil() && $entity->getLockedUntil() > new \DateTimeImmutable();

        return [
            'id' => $userId,
            'name' => $entity->getName(),
            'email' => $entity->getEmail(),
            'organizationId' => $entity->getOrganization()?->getId()?->toString() ?? '',
            'organizationName' => $entity->getOrganization()?->getName() ?? '',
            'roles' => $rolesDisplay,
            'isVerified' => $entity->isVerified(),
            'isLocked' => $isLocked,
            'lastLoginAt' => $entity->getLastLoginAt()?->format('c'),
            'lastLoginFormatted' => $entity->getLastLoginAt()?->format('M d, Y H:i'),
            'ownedCoursesCount' => $entity->getOwnedCourses()->count(),
            'enrolledCoursesCount' => $entity->getStudentCourses()->count(),
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'createdAtFormatted' => $entity->getCreatedAt()->format('M d, Y'),
            'deleteCsrfToken' => $this->csrfTokenManager->getToken('delete')->getValue(),
        ];
    }
}