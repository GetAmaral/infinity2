<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\TreeFlow;
use App\Form\TreeFlowFormType;
use App\Repository\TreeFlowRepository;
use App\Security\Voter\TreeFlowVoter;
use App\Service\ListPreferencesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends BaseApiController<TreeFlow>
 */
#[Route('/treeflow')]
final class TreeFlowController extends BaseApiController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TreeFlowRepository $repository,
        private readonly ListPreferencesService $listPreferencesService,
    ) {}

    #[Route('', name: 'treeflow_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::LIST);

        // Get saved view preference
        $preferences = $this->listPreferencesService->getEntityPreferences('treeflows');
        $savedView = $preferences['view'] ?? 'grid';

        return $this->render('treeflow/index.html.twig', [
            // Generic entity list variables for base template
            'entities' => [], // Empty - JS will load via API
            'entity_name' => 'treeflow',
            'entity_name_plural' => 'treeflows',
            'page_icon' => 'bi bi-diagram-3',
            'default_view' => $savedView,
            'enable_search' => true,
            'enable_filters' => true,
            'enable_create_button' => true,

            // Backward compatibility
            'treeflows' => [],
        ]);
    }

    #[Route('/new', name: 'treeflow_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::CREATE);

        $treeFlow = new TreeFlow();

        // Auto-set organization from current user
        $user = $this->getUser();
        if ($user && $user->getOrganization()) {
            $treeFlow->setOrganization($user->getOrganization());
        }
        $treeFlow->setVersion(1); // Initial version

        $form = $this->createForm(TreeFlowFormType::class, $treeFlow, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($treeFlow);
            $this->entityManager->flush();

            $this->addFlash('success', 'treeflow.flash.created_successfully');

            return $this->redirectToRefererOrRoute($request, 'treeflow_index');
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/_form_modal.html.twig', [
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => false,
            ]);
        }

        return $this->render('treeflow/new.html.twig', [
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'treeflow_show', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function show(TreeFlow $treeFlow): Response
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::VIEW, $treeFlow);

        return $this->render('treeflow/show.html.twig', [
            'treeflow' => $treeFlow,
        ]);
    }

    #[Route('/{id}/edit', name: 'treeflow_edit', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET', 'POST'])]
    public function edit(Request $request, TreeFlow $treeFlow): Response
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $form = $this->createForm(TreeFlowFormType::class, $treeFlow, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Version auto-increments via PreUpdate callback
            $this->entityManager->flush();

            $this->addFlash('success', 'treeflow.flash.updated_successfully');

            return $this->redirectToRefererOrRoute($request, 'treeflow_index');
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/_form_modal.html.twig', [
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => true,
            ]);
        }

        return $this->render('treeflow/edit.html.twig', [
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'treeflow_delete', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST'])]
    public function delete(Request $request, TreeFlow $treeFlow): Response
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::DELETE, $treeFlow);

        $treeFlowId = $treeFlow->getId()?->toString();
        $treeFlowName = $treeFlow->getName();

        if ($this->isCsrfTokenValid('delete-treeflow-' . $treeFlowId, $request->request->get('_token'))) {
            $this->entityManager->remove($treeFlow);
            $this->entityManager->flush();

            $this->addFlash('success', 'treeflow.flash.deleted_successfully');
        } else {
            $this->addFlash('error', 'treeflow.flash.invalid_csrf_token');
        }

        return $this->redirectToRoute('treeflow_index');
    }

    #[Route('/api/search', name: 'treeflow_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::LIST);

        // Use parent class implementation (BaseApiController)
        // All logic delegated to TreeFlowRepository via BaseRepository
        return $this->apiSearchAction($request);
    }

    /**
     * Get repository for BaseApiController
     */
    protected function getRepository(): TreeFlowRepository
    {
        return $this->repository;
    }

    /**
     * Get entity plural name for JSON response
     */
    protected function getEntityPluralName(): string
    {
        return 'treeflows';
    }

    /**
     * Transform TreeFlow entity to array for JSON API response (Phase 1 - Basic)
     */
    protected function entityToArray(object $entity): array
    {
        assert($entity instanceof TreeFlow);

        return [
            'id' => $entity->getId()?->toString(),
            'name' => $entity->getName(),
            'version' => $entity->getVersion(),
            'active' => $entity->isActive(),
            'organizationId' => $entity->getOrganization()->getId()?->toString(),
            'organizationName' => $entity->getOrganization()->getName(),
            'stepsCount' => $entity->getSteps()->count(),
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'createdAtFormatted' => $entity->getCreatedAt()->format('M d, Y'),
            'updatedAtFormatted' => $entity->getUpdatedAt()->format('M d, Y'),
            'createdByName' => $entity->getCreatedBy()?->getName(),
        ];
    }
}
