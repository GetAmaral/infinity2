<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StepFewShot;
use App\Entity\StepQuestion;
use App\Entity\Step;
use App\Entity\StepInput;
use App\Entity\StepOutput;
use App\Entity\TreeFlow;
use App\Form\TreeFlowFormType;
use App\Repository\TreeFlowRepository;
use App\Security\Voter\TreeFlowVoter;
use App\Service\ListPreferencesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        private readonly TranslatorInterface $translator,
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

            // Enhanced flash message with parameters (Phase 6)
            $this->addFlash('success', $this->translator->trans('treeflow.flash.created_successfully', [
                '%name%' => $treeFlow->getName(),
                '%version%' => $treeFlow->getVersion(),
            ], 'treeflow'));

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
    public function show(Request $request, string $id): Response
    {
        // Performance Optimization (Phase 5): Use eager loading to prevent N+1 queries
        // Single optimized query that loads TreeFlow with all nested relations
        $treeFlow = $this->repository->createQueryBuilder('t')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->leftJoin('t.steps', 's')
            ->leftJoin('s.questions', 'q')
            ->leftJoin('q.examples', 'e')
            ->leftJoin('s.outputs', 'o')
            ->leftJoin('s.inputs', 'i')
            ->addSelect('s', 'q', 'e', 'o', 'i')
            ->orderBy('s.viewOrder', 'ASC')
            ->addOrderBy('q.viewOrder', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();

        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::VIEW, $treeFlow);

        // Return JSON for AJAX requests (canvas refresh)
        if ($request->isXmlHttpRequest() && $request->headers->get('Accept') === 'application/json') {
            $stepsData = [];
            foreach ($treeFlow->getSteps() as $step) {
                $questionsData = [];
                foreach ($step->getQuestions() as $question) {
                    $questionsData[] = [
                        'id' => $question->getId()?->toString(),
                        'questionText' => $question->getName(),
                        'text' => $question->getName(),
                        'viewOrder' => $question->getViewOrder(),
                    ];
                }

                $inputsData = [];
                foreach ($step->getInputs() as $input) {
                    $inputsData[] = [
                        'id' => $input->getId()?->toString(),
                        'name' => $input->getName(),
                        'type' => $input->getType()?->value,
                    ];
                }

                $outputsData = [];
                // Sort outputs by ID for consistent ordering
                $outputs = $step->getOutputs()->toArray();
                usort($outputs, function($a, $b) {
                    return $a->getId()?->toString() <=> $b->getId()?->toString();
                });
                foreach ($outputs as $output) {
                    $outputsData[] = [
                        'id' => $output->getId()?->toString(),
                        'name' => $output->getName(),
                        'goToStep' => $output->getDestinationStep()?->getId()?->toString(),
                    ];
                }

                $stepsData[] = [
                    'id' => $step->getId()?->toString(),
                    'name' => $step->getName(),
                    'first' => $step->isFirst(),
                    'positionX' => $step->getPositionX(),
                    'positionY' => $step->getPositionY(),
                    'questions' => $questionsData,
                    'inputs' => $inputsData,
                    'outputs' => $outputsData,
                ];
            }

            return $this->json([
                'success' => true,
                'steps' => $stepsData,
            ]);
        }

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

            // Enhanced flash message with parameters (Phase 6)
            $this->addFlash('success', $this->translator->trans('treeflow.flash.updated_successfully', [
                '%name%' => $treeFlow->getName(),
                '%version%' => $treeFlow->getVersion(),
            ], 'treeflow'));

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

            // Enhanced flash message with parameters (Phase 6)
            $this->addFlash('success', $this->translator->trans('treeflow.flash.deleted_successfully', [
                '%name%' => $treeFlowName,
            ], 'treeflow'));
        } else {
            $this->addFlash('error', 'common.error.invalid_csrf');
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
     * Transform TreeFlow entity to array for JSON API response (Phase 5 - Deep Nested)
     */
    protected function entityToArray(object $entity): array
    {
        assert($entity instanceof TreeFlow);

        return [
            'id' => $entity->getId()?->toString(),
            'name' => $entity->getName(),
            'slug' => $entity->getSlug(),
            'version' => $entity->getVersion(),
            'active' => $entity->isActive(),
            'organizationId' => $entity->getOrganization()->getId()?->toString(),
            'organizationName' => $entity->getOrganization()->getName(),
            'stepsCount' => $entity->getSteps()->count(),
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'createdAtFormatted' => $entity->getCreatedAt()->format('M d, Y'),
            'updatedAtFormatted' => $entity->getUpdatedAt()->format('M d, Y'),
            'createdByName' => $entity->getCreatedBy()?->getName(),

            // DEEP NESTED DATA (Phase 5)
            'steps' => array_map(function(Step $step) {
                return [
                    'id' => $step->getId()?->toString(),
                    'name' => $step->getName(),
                    'slug' => $step->getSlug(),
                    'first' => $step->isFirst(),
                    'objective' => $step->getObjective(),
                    'prompt' => $step->getPrompt(),

                    // Questions with FewShots
                    'questions' => array_map(function(StepQuestion $q) {
                        return [
                            'id' => $q->getId()?->toString(),
                            'name' => $q->getName(),
                            'slug' => $q->getSlug(),
                            'prompt' => $q->getPrompt(),
                            'objective' => $q->getObjective(),
                            'importance' => $q->getImportance(),
                            'viewOrder' => $q->getViewOrder(),

                            // FewShot Examples
                            'examples' => array_map(function(StepFewShot $ex) {
                                return [
                                    'id' => $ex->getId()?->toString(),
                                    'type' => $ex->getType()->value,
                                    'name' => $ex->getName(),
                                    'slug' => $ex->getSlug(),
                                    'prompt' => $ex->getPrompt(),
                                    'description' => $ex->getDescription(),
                                ];
                            }, $q->getFewShotExamples()->toArray()),
                        ];
                    }, $step->getQuestions()->toArray()),

                    // Outputs
                    'outputs' => array_map(function(StepOutput $out) {
                        return [
                            'id' => $out->getId()?->toString(),
                            'name' => $out->getName(),
                            'slug' => $out->getSlug(),
                            'description' => $out->getDescription(),
                            'conditional' => $out->getConditional(),
                            'destinationStepId' => $out->getDestinationStep()?->getId()?->toString(),
                            'destinationStepName' => $out->getDestinationStep()?->getName(),
                        ];
                    }, $step->getOutputs()->toArray()),

                    // Inputs
                    'inputs' => array_map(function(StepInput $in) {
                        return [
                            'id' => $in->getId()?->toString(),
                            'name' => $in->getName(),
                            'slug' => $in->getSlug(),
                            'type' => $in->getType()->value,
                            'sourceStepId' => $in->getSourceStep()?->getId()?->toString(),
                            'sourceStepName' => $in->getSourceStep()?->getName(),
                            'prompt' => $in->getPrompt(),
                        ];
                    }, $step->getInputs()->toArray()),
                ];
            }, $entity->getSteps()->toArray()),
        ];
    }
}
