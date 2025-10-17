<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Step;
use App\Entity\StepConnection;
use App\Entity\StepInput;
use App\Entity\StepOutput;
use App\Form\StepFormType;
use App\Repository\StepOutputRepository;
use App\Repository\StepRepository;
use App\Repository\TreeFlowRepository;
use App\Security\Voter\TreeFlowVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/treeflow')]
final class StepController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TreeFlowRepository $treeFlowRepository,
        private readonly StepRepository $stepRepository,
        private readonly StepOutputRepository $outputRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/{treeflowId}/step/new', name: 'step_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $treeflowId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $step = new Step();
        $step->setTreeFlow($treeFlow);

        $form = $this->createForm(StepFormType::class, $step, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get continuation parameters
            $sourceStepId = $request->query->get('sourceStepId') ?? $request->request->get('sourceStepId');
            $sourceOutputId = $request->query->get('sourceOutputId') ?? $request->request->get('sourceOutputId');

            $this->entityManager->persist($step);
            $this->entityManager->flush();

            // Handle continuation logic (auto-connection)
            $responseData = null;
            if ($sourceStepId) {
                $responseData = $this->handleContinuationLogic($step, $sourceStepId, $sourceOutputId);
            }

            // Return JSON for AJAX requests
            if ($request->isXmlHttpRequest()) {
                if ($responseData) {
                    return $this->json($responseData);
                }

                return $this->json([
                    'success' => true,
                    'step' => [
                        'id' => $step->getId()?->toString(),
                        'name' => $step->getName(),
                        'objective' => $step->getObjective(),
                        'positionX' => $step->getPositionX(),
                        'positionY' => $step->getPositionY(),
                    ]
                ]);
            }

            $this->addFlash('success', 'step.flash.created_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Return validation errors for AJAX requests
        if ($form->isSubmitted() && !$form->isValid() && $request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'html' => $this->renderView('treeflow/step/_form_modal.html.twig', [
                    'step' => $step,
                    'treeflow' => $treeFlow,
                    'form' => $form,
                    'is_edit' => false,
                ])
            ]);
        }

        // Handle modal/AJAX requests for initial load
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/step/_form_modal.html.twig', [
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => false,
                'sourceStepId' => $request->query->get('sourceStepId'),
                'sourceOutputId' => $request->query->get('sourceOutputId'),
            ]);
        }

        return $this->render('treeflow/step/new.html.twig', [
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/edit', name: 'step_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $treeflowId, string $stepId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            throw $this->createNotFoundException('Step not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $form = $this->createForm(StepFormType::class, $step, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            // Return JSON for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'step' => [
                        'id' => $step->getId()?->toString(),
                        'name' => $step->getName(),
                        'objective' => $step->getObjective(),
                        'positionX' => $step->getPositionX(),
                        'positionY' => $step->getPositionY(),
                    ]
                ]);
            }

            $this->addFlash('success', 'step.flash.updated_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Return validation errors for AJAX requests
        if ($form->isSubmitted() && !$form->isValid() && $request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'html' => $this->renderView('treeflow/step/_form_modal.html.twig', [
                    'step' => $step,
                    'treeflow' => $treeFlow,
                    'form' => $form,
                    'is_edit' => true,
                ])
            ]);
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/step/_form_modal.html.twig', [
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => true,
            ]);
        }

        return $this->render('treeflow/step/edit.html.twig', [
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/delete', name: 'step_delete', methods: ['POST'])]
    public function delete(Request $request, string $treeflowId, string $stepId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            throw $this->createNotFoundException('Step not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $stepId = $step->getId()?->toString();

        if ($this->isCsrfTokenValid('delete-step-' . $stepId, $request->request->get('_token'))) {
            $this->entityManager->remove($step);
            $this->entityManager->flush();

            $this->addFlash('success', 'step.flash.deleted_successfully');
        } else {
            $this->addFlash('error', 'common.error.invalid_csrf');
        }

        return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
    }

    #[Route('/{treeflowId}/step/{stepId}', name: 'step_delete_ajax', methods: ['DELETE'])]
    public function deleteAjax(Request $request, string $treeflowId, string $stepId): JsonResponse
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            return $this->json([
                'success' => false,
                'error' => 'TreeFlow not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            return $this->json([
                'success' => false,
                'error' => 'Step not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        // Check if this is the first step
        if ($step->isFirst()) {
            return $this->json([
                'success' => false,
                'error' => 'Cannot delete the first step'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->remove($step);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Step deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to delete step: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function handleContinuationLogic(Step $newStep, string $sourceStepId, ?string $sourceOutputId): array
    {
        $sourceStep = $this->stepRepository->find($sourceStepId);
        if (!$sourceStep) {
            return [
                'success' => false,
                'error' => 'Source step not found'
            ];
        }

        // Determine source output
        $sourceOutput = null;
        $outputCreated = false;

        if ($sourceOutputId) {
            // Use existing output
            $sourceOutput = $this->outputRepository->find($sourceOutputId);
            if (!$sourceOutput || $sourceOutput->getStep()->getId()->toString() !== $sourceStepId) {
                return [
                    'success' => false,
                    'error' => 'Source output not found or does not belong to source step'
                ];
            }
        } else {
            // Create default output on source step
            $outputName = $this->translator->trans('output.default.name', [], 'treeflow');
            $sourceOutput = new StepOutput();
            $sourceOutput->setStep($sourceStep);
            $sourceOutput->setName($outputName);
            $this->entityManager->persist($sourceOutput);
            $this->entityManager->flush();
            $outputCreated = true;
        }

        // Calculate position based on sibling steps
        $stepWidth = 280;
        $verticalSpacing = $stepWidth / 4; // 70px - same as auto-organize
        $newX = $sourceStep->getPositionX() + $stepWidth + ($stepWidth / 2);

        // Query outputs with connections explicitly to ensure we have fresh data
        $outputsWithConnections = $this->outputRepository->createQueryBuilder('o')
            ->leftJoin('o.connection', 'c')
            ->leftJoin('c.targetInput', 'ti')
            ->leftJoin('ti.step', 's')
            ->addSelect('c', 'ti', 's')
            ->where('o.step = :step')
            ->setParameter('step', $sourceStep)
            ->orderBy('o.id', 'ASC') // Sort by ID for consistency
            ->getQuery()
            ->getResult();

        // Find all existing children of source step (siblings of the new step)
        $siblings = [];
        $currentOutputIndex = null;
        $outputIndex = 0;

        // Get source output ID for comparison
        $sourceOutputId = $sourceOutput->getId()?->toString();

        foreach ($outputsWithConnections as $output) {
            if ($output->getConnection()) {
                $targetStep = $output->getConnection()->getTargetInput()->getStep();
                $siblings[] = [
                    'step' => $targetStep,
                    'outputIndex' => $outputIndex,
                ];
            }

            // Track which output index we're using for the new connection
            if ($sourceOutputId && $output->getId()?->toString() === $sourceOutputId) {
                $currentOutputIndex = $outputIndex;
            }

            $outputIndex++;
        }

        // Sort siblings by output index (to prevent crossing lines)
        usort($siblings, function($a, $b) {
            return $a['outputIndex'] <=> $b['outputIndex'];
        });

        // Calculate Y position: place below all previous siblings
        $newY = $sourceStep->getPositionY();

        if (!empty($siblings) && $currentOutputIndex !== null) {
            // Find siblings that come before this one (lower output index)
            $previousSiblings = array_filter($siblings, function($sibling) use ($currentOutputIndex) {
                return $sibling['outputIndex'] < $currentOutputIndex;
            });

            // Sort previous siblings by output index to get the correct last one
            usort($previousSiblings, function($a, $b) {
                return $a['outputIndex'] <=> $b['outputIndex'];
            });

            if (!empty($previousSiblings)) {
                // Position below the last previous sibling
                $lastSibling = end($previousSiblings);
                $lastStep = $lastSibling['step'];

                // Estimate step height (we don't have actual DOM height in backend)
                // Steps with questions, inputs, outputs can be 300-400px tall
                $estimatedStepHeight = 350; // Conservative estimate for step card height
                $newY = $lastStep->getPositionY() + $estimatedStepHeight + $verticalSpacing;
            }
        } else if ($currentOutputIndex === null) {
            // Fallback: if we couldn't determine the output index, position below all existing siblings
            if (!empty($siblings)) {
                $allSiblingYPositions = array_map(fn($s) => $s['step']->getPositionY(), $siblings);
                $maxY = max($allSiblingYPositions);
                $newY = $maxY + 350 + $verticalSpacing;
            }
        }

        $newStep->setPositionX($newX);
        $newStep->setPositionY($newY);

        // Create default input on new step
        $inputName = $this->generateInputName(
            $sourceOutput->getName(),
            $sourceStep->getName()
        );

        $newInput = new StepInput();
        $newInput->setStep($newStep);
        $newInput->setName($inputName);
        $newInput->setType(\App\Enum\InputType::ANY);

        $this->entityManager->persist($newInput);

        // Create connection
        $connection = new StepConnection();
        $connection->setSourceOutput($sourceOutput);
        $connection->setTargetInput($newInput);

        $user = $this->getUser();
        if ($user && $user->getOrganization()) {
            $connection->setOrganization($user->getOrganization());
        }

        $this->entityManager->persist($connection);
        $this->entityManager->flush();

        // Build response
        $response = [
            'success' => true,
            'step' => [
                'id' => $newStep->getId()?->toString(),
                'name' => $newStep->getName(),
                'objective' => $newStep->getObjective(),
                'positionX' => $newStep->getPositionX(),
                'positionY' => $newStep->getPositionY(),
                'inputs' => [
                    [
                        'id' => $newInput->getId()?->toString(),
                        'name' => $newInput->getName(),
                        'type' => $newInput->getType()->value,
                    ]
                ],
                'outputs' => [],
                'first' => $newStep->isFirst(),
                'questions' => [],
            ],
            'connection' => [
                'id' => $connection->getId()?->toString(),
                'sourceOutput' => [
                    'id' => $sourceOutput->getId()?->toString(),
                    'name' => $sourceOutput->getName(),
                    'stepId' => $sourceStep->getId()?->toString(),
                    'stepName' => $sourceStep->getName(),
                ],
                'targetInput' => [
                    'id' => $newInput->getId()?->toString(),
                    'name' => $newInput->getName(),
                    'stepId' => $newStep->getId()?->toString(),
                    'stepName' => $newStep->getName(),
                    'type' => $newInput->getType()->value,
                ],
            ],
        ];

        // If output was created, include it in response
        if ($outputCreated) {
            $response['outputCreated'] = [
                'id' => $sourceOutput->getId()?->toString(),
                'name' => $sourceOutput->getName(),
                'stepId' => $sourceStep->getId()?->toString(),
            ];
        }

        return $response;
    }

    private function generateInputName(?string $outputName, ?string $sourceStepName): string
    {
        // Priority 1: Use output name if meaningful
        if ($outputName && !in_array(strtolower($outputName), ['output', 'default', 'out', 'result'])) {
            return 'On ' . $outputName;
        }

        // Priority 2: Use source step name
        if ($sourceStepName) {
            return 'From ' . $sourceStepName;
        }

        // Fallback
        return 'New Input';
    }

    #[Route('/{treeflowId}/step/reorder', name: 'step_reorder', methods: ['POST'])]
    public function reorder(Request $request, string $treeflowId): JsonResponse
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            return new JsonResponse([
                'success' => false,
                'message' => 'TreeFlow not found'
            ], 404);
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        // Get JSON data from request
        $data = json_decode($request->getContent(), true);

        if (!isset($data['steps']) || !is_array($data['steps'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid request data'
            ], 400);
        }

        try {
            // Process reorder - for now just validate the step IDs belong to this TreeFlow
            $stepIds = array_column($data['steps'], 'id');
            $treeFlowStepIds = array_map(
                fn($step) => $step->getId()->toString(),
                $treeFlow->getSteps()->toArray()
            );

            foreach ($stepIds as $stepId) {
                if (!in_array($stepId, $treeFlowStepIds)) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Invalid step ID'
                    ], 400);
                }
            }

            // Persist the new order (Enhancement: Step viewOrder field)
            foreach ($data['steps'] as $stepData) {
                $step = $this->stepRepository->find($stepData['id']);
                if ($step) {
                    $step->setViewOrder($stepData['order']);
                }
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Steps reordered successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error reordering steps: ' . $e->getMessage()
            ], 500);
        }
    }
}
