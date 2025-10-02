<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Organization;
use App\Form\OrganizationFormType;
use App\Repository\OrganizationRepository;
use App\Service\ListPreferencesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Voter\OrganizationVoter;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @extends BaseApiController<Organization>
 */
#[Route('/organization')]
final class OrganizationController extends BaseApiController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrganizationRepository $repository,
        private readonly ListPreferencesService $listPreferencesService,
        private readonly SluggerInterface $slugger
    ) {}

    #[Route('', name: 'organization_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(OrganizationVoter::LIST);

        // Get saved view preference from list preferences
        $preferences = $this->listPreferencesService->getEntityPreferences('organizations');
        $savedView = $preferences['view'] ?? 'grid';

        return $this->render('organization/index.html.twig', [
            // Generic entity list variables for base template
            'entities' => [], // Empty - JS will load via API
            'entity_name' => 'organization',
            'entity_name_plural' => 'organizations',
            'page_icon' => 'bi bi-building',
            'default_view' => $savedView, // Use saved preference
            'enable_search' => true,
            'enable_filters' => true,
            'enable_create_button' => true,

            // Backward compatibility: keep old variable name
            'organizations' => [], // Empty - JS will load via API
        ]);
    }

    #[Route('/new', name: 'organization_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(OrganizationVoter::CREATE);

        $organization = new Organization();
        $form = $this->createForm(OrganizationFormType::class, $organization, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle light logo file upload
            $logoFileLight = $form->get('logoFileLight')->getData();
            if ($logoFileLight instanceof UploadedFile) {
                $originalFilename = pathinfo($logoFileLight->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-light-' . uniqid() . '.' . $logoFileLight->guessExtension();

                $logoFileLight->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/logos',
                    $newFilename
                );

                $organization->setLogoPath('/uploads/logos/' . $newFilename);
            }

            // Handle dark logo file upload
            $logoFileDark = $form->get('logoFileDark')->getData();
            if ($logoFileDark instanceof UploadedFile) {
                $originalFilename = pathinfo($logoFileDark->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-dark-' . uniqid() . '.' . $logoFileDark->guessExtension();

                $logoFileDark->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/logos',
                    $newFilename
                );

                $organization->setLogoPathDark('/uploads/logos/' . $newFilename);
            }

            $this->entityManager->persist($organization);
            $this->entityManager->flush();

            $this->addFlash('success', 'organization.flash.created_successfully');

            return $this->redirectToRoute('organization_index', [], Response::HTTP_SEE_OTHER);
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('organization/_form_modal.html.twig', [
                'organization' => $organization,
                'form' => $form,
                'is_edit' => false,
            ]);
        }

        return $this->render('organization/new.html.twig', [
            'organization' => $organization,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'organization_show', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function show(Organization $organization): Response
    {
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $organization);

        return $this->render('organization/show.html.twig', [
            'organization' => $organization,
        ]);
    }

    #[Route('/{id}/edit', name: 'organization_edit', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Organization $organization): Response
    {
        $this->denyAccessUnlessGranted(OrganizationVoter::EDIT, $organization);

        $form = $this->createForm(OrganizationFormType::class, $organization, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle light logo file upload
            $logoFileLight = $form->get('logoFileLight')->getData();
            if ($logoFileLight instanceof UploadedFile) {
                // Delete old light logo if exists
                if ($organization->getLogoPath()) {
                    $oldLogoPath = $this->getParameter('kernel.project_dir') . '/public' . $organization->getLogoPath();
                    if (file_exists($oldLogoPath)) {
                        unlink($oldLogoPath);
                    }
                }

                $originalFilename = pathinfo($logoFileLight->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-light-' . uniqid() . '.' . $logoFileLight->guessExtension();

                $logoFileLight->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/logos',
                    $newFilename
                );

                $organization->setLogoPath('/uploads/logos/' . $newFilename);
            }

            // Handle dark logo file upload
            $logoFileDark = $form->get('logoFileDark')->getData();
            if ($logoFileDark instanceof UploadedFile) {
                // Delete old dark logo if exists
                if ($organization->getLogoPathDark()) {
                    $oldLogoPath = $this->getParameter('kernel.project_dir') . '/public' . $organization->getLogoPathDark();
                    if (file_exists($oldLogoPath)) {
                        unlink($oldLogoPath);
                    }
                }

                $originalFilename = pathinfo($logoFileDark->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-dark-' . uniqid() . '.' . $logoFileDark->guessExtension();

                $logoFileDark->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/logos',
                    $newFilename
                );

                $organization->setLogoPathDark('/uploads/logos/' . $newFilename);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'organization.flash.updated_successfully');

            return $this->redirectToRefererOrRoute($request, 'organization_index');
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('organization/_form_modal.html.twig', [
                'organization' => $organization,
                'form' => $form,
                'is_edit' => true,
            ]);
        }

        return $this->render('organization/edit.html.twig', [
            'organization' => $organization,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'organization_delete', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Organization $organization): Response
    {
        $this->denyAccessUnlessGranted(OrganizationVoter::DELETE, $organization);

        $organizationId = $organization->getId()?->toString();
        $organizationName = $organization->getName();

        // Check if organization has users
        if ($organization->getUsers()->count() > 0) {
            $this->addFlash('error', 'organization.flash.cannot_delete_has_users');

            if ($request->headers->get('Accept') === 'text/vnd.turbo-stream.html') {
                return $this->render('organization/_turbo_stream_error.html.twig', [
                    'message' => 'organization.flash.cannot_delete_has_users',
                ]);
            }

            return $this->redirectToRoute('organization_show', [
                'id' => $organizationId
            ]);
        }

        if ($this->isCsrfTokenValid('delete-organization-' . $organizationId, $request->request->get('_token'))) {
            $this->entityManager->remove($organization);
            $this->entityManager->flush();

            $this->addFlash('success', 'organization.flash.deleted_successfully');

            // Return Turbo Stream response for seamless UX
            if ($request->headers->get('Accept') === 'text/vnd.turbo-stream.html') {
                return $this->render('organization/_turbo_stream_deleted.html.twig', [
                    'organizationId' => $organizationId,
                    'organizationName' => $organizationName,
                ]);
            }
        } else {
            $this->addFlash('error', 'organization.flash.invalid_csrf_token');
        }

        return $this->redirectToRoute('organization_index');
    }

    #[Route('/{id}/users', name: 'organization_users', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function users(Organization $organization): Response
    {
        return $this->render('organization/users.html.twig', [
            'organization' => $organization,
            'users' => $organization->getUsers(),
        ]);
    }

    #[Route('/api/search', name: 'organization_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        $this->denyAccessUnlessGranted(OrganizationVoter::LIST);

        // Use parent class implementation (BaseApiController)
        // All logic delegated to OrganizationRepository via BaseRepository
        return $this->apiSearchAction($request);
    }

    /**
     * Get repository for BaseApiController
     */
    protected function getRepository(): OrganizationRepository
    {
        return $this->repository;
    }

    /**
     * Get entity plural name for JSON response
     */
    protected function getEntityPluralName(): string
    {
        return 'organizations';
    }

    /**
     * Transform Organization entity to array for JSON API response
     */
    protected function entityToArray(object $entity): array
    {
        assert($entity instanceof Organization);

        // Calculate active courses (courses with students enrolled)
        $activeCourseCount = 0;
        foreach ($entity->getCourses() as $course) {
            if ($course->getStudentCourses()->count() > 0) {
                $activeCourseCount++;
            }
        }

        // Get verified users count
        $verifiedUserCount = 0;
        foreach ($entity->getUsers() as $user) {
            if ($user->isVerified()) {
                $verifiedUserCount++;
            }
        }

        return [
            'id' => $entity->getId()?->toString() ?? '',
            'name' => $entity->getName(),
            'slug' => $entity->getSlug(),
            'description' => $entity->getDescription(),
            'logoPath' => $entity->getLogoPath(),
            'logoPathDark' => $entity->getLogoPathDark(),
            'userCount' => $entity->getUsers()->count(),
            'verifiedUserCount' => $verifiedUserCount,
            'courseCount' => $entity->getCourses()->count(),
            'activeCourseCount' => $activeCourseCount,
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'createdAtFormatted' => $entity->getCreatedAt()->format('M d, Y'),
            'updatedAt' => $entity->getUpdatedAt()->format('c'),
            'updatedAtFormatted' => $entity->getUpdatedAt()->format('M d, Y'),
            'createdByName' => $entity->getCreatedBy()?->getName() ?? null,
            'updatedByName' => $entity->getUpdatedBy()?->getName() ?? null,
            'isActive' => $entity->isActive(),
        ];
    }
}