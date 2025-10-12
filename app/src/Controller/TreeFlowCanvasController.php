<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Step;
use App\Entity\StepConnection;
use App\Entity\StepInput;
use App\Entity\StepOutput;
use App\Entity\TreeFlow;
use App\Repository\StepInputRepository;
use App\Repository\StepOutputRepository;
use App\Repository\StepRepository;
use App\Security\Voter\TreeFlowVoter;
use App\Service\StepConnectionValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * TreeFlowCanvasController - API endpoints for canvas editor
 *
 * Provides REST API for:
 * - Saving step positions on canvas
 * - Creating visual connections between steps
 * - Deleting connections
 */
#[Route('/treeflow')]
final class TreeFlowCanvasController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StepRepository $stepRepository,
        private readonly StepOutputRepository $outputRepository,
        private readonly StepInputRepository $inputRepository,
        private readonly StepConnectionValidator $validator,
    ) {
    }

    /**
     * List connections for a treeflow
     * GET /treeflow/{id}/connections
     */
    #[Route('/{id}/connections', name: 'treeflow_connections_list', methods: ['GET'])]
    public function listConnections(#[MapEntity(id: 'id')] TreeFlow $treeFlow): JsonResponse
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::VIEW, $treeFlow);

        error_log("[LIST CONNECTIONS] Loading connections for TreeFlow: {$treeFlow->getId()}");

        $connections = $this->entityManager->getRepository(StepConnection::class)
            ->createQueryBuilder('c')
            ->join('c.sourceOutput', 'so')
            ->join('c.targetInput', 'ti')
            ->join('so.step', 'ss')
            ->join('ti.step', 'ts')
            ->where('ss.treeFlow = :treeflow OR ts.treeFlow = :treeflow')
            ->setParameter('treeflow', $treeFlow)
            ->addSelect('so', 'ti', 'ss', 'ts')
            ->getQuery()
            ->getResult();

        error_log("[LIST CONNECTIONS] Found " . count($connections) . " connections");
        foreach ($connections as $connection) {
            error_log("[LIST CONNECTIONS] - {$connection->getId()}: {$connection->getSourceOutput()->getName()} -> {$connection->getTargetInput()->getName()}");
        }

        $connectionsData = [];
        foreach ($connections as $connection) {
            $connectionsData[] = [
                'id' => $connection->getId()?->toString(),
                'sourceOutput' => [
                    'id' => $connection->getSourceOutput()->getId()?->toString(),
                    'name' => $connection->getSourceOutput()->getName(),
                    'stepId' => $connection->getSourceOutput()->getStep()->getId()?->toString(),
                    'stepName' => $connection->getSourceOutput()->getStep()->getName(),
                ],
                'targetInput' => [
                    'id' => $connection->getTargetInput()->getId()?->toString(),
                    'name' => $connection->getTargetInput()->getName(),
                    'stepId' => $connection->getTargetInput()->getStep()->getId()?->toString(),
                    'stepName' => $connection->getTargetInput()->getStep()->getName(),
                    'type' => $connection->getTargetInput()->getType()->value,
                ],
            ];
        }

        return $this->json([
            'success' => true,
            'connections' => $connectionsData,
        ]);
    }

    /**
     * Save canvas view state (zoom, pan, etc.)
     * POST /treeflow/{id}/canvas-state
     */
    #[Route('/{id}/canvas-state', name: 'treeflow_canvas_state', methods: ['POST'])]
    public function saveCanvasState(
        #[MapEntity(id: 'id')] TreeFlow $treeFlow,
        Request $request
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $data = json_decode($request->getContent(), true);

        if (!isset($data['scale']) || !isset($data['offsetX']) || !isset($data['offsetY'])) {
            return $this->json([
                'success' => false,
                'error' => 'Missing required canvas state properties',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate values are within reasonable bounds
        $scale = (float) $data['scale'];
        $offsetX = (float) $data['offsetX'];
        $offsetY = (float) $data['offsetY'];

        if ($scale < 0.1 || $scale > 3) {
            return $this->json([
                'success' => false,
                'error' => 'Scale out of bounds (0.1-3)',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($offsetX < -50000 || $offsetX > 50000 || $offsetY < -50000 || $offsetY > 50000) {
            return $this->json([
                'success' => false,
                'error' => 'Offset out of bounds',
            ], Response::HTTP_BAD_REQUEST);
        }

        $canvasState = [
            'scale' => $scale,
            'offsetX' => $offsetX,
            'offsetY' => $offsetY,
        ];

        $treeFlow->setCanvasViewState($canvasState);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'canvasViewState' => $canvasState,
        ]);
    }

    /**
     * Save step position on canvas
     * POST /treeflow/{id}/step/{stepId}/position
     */
    #[Route('/{id}/step/{stepId}/position', name: 'treeflow_step_position', methods: ['POST'])]
    public function saveStepPosition(
        #[MapEntity(id: 'id')] TreeFlow $treeFlow,
        #[MapEntity(id: 'stepId')] Step $step,
        Request $request
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        // Verify step belongs to treeflow
        if ($step->getTreeFlow()->getId() !== $treeFlow->getId()) {
            return $this->json([
                'success' => false,
                'error' => 'Step does not belong to this TreeFlow',
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['x']) || !isset($data['y'])) {
            return $this->json([
                'success' => false,
                'error' => 'Missing x or y coordinates',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate coordinates are within reasonable bounds
        $x = (int) $data['x'];
        $y = (int) $data['y'];

        if ($x < -10000 || $x > 50000 || $y < -10000 || $y > 50000) {
            return $this->json([
                'success' => false,
                'error' => 'Coordinates out of bounds',
            ], Response::HTTP_BAD_REQUEST);
        }

        $step->setPositionX($x);
        $step->setPositionY($y);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'step' => [
                'id' => $step->getId()?->toString(),
                'positionX' => $step->getPositionX(),
                'positionY' => $step->getPositionY(),
            ],
        ]);
    }

    /**
     * Create connection between output and input
     * POST /treeflow/{id}/connection
     */
    #[Route('/{id}/connection', name: 'treeflow_connection_create', methods: ['POST'])]
    public function createConnection(#[MapEntity(id: 'id')] TreeFlow $treeFlow, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $data = json_decode($request->getContent(), true);

        error_log("[CREATE CONNECTION] Request received - outputId: " . ($data['outputId'] ?? 'null') . ", inputId: " . ($data['inputId'] ?? 'null'));

        if (!isset($data['outputId']) || !isset($data['inputId'])) {
            return $this->json([
                'success' => false,
                'error' => 'Missing outputId or inputId',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Eager load connection to prevent N+1 query in validator
        $output = $this->outputRepository->createQueryBuilder('o')
            ->leftJoin('o.connection', 'c')
            ->addSelect('c')
            ->where('o.id = :id')
            ->setParameter('id', $data['outputId'])
            ->getQuery()
            ->getOneOrNullResult();

        $input = $this->inputRepository->find($data['inputId']);

        if (!$output || !$input) {
            return $this->json([
                'success' => false,
                'error' => 'Output or Input not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Verify both belong to the same treeflow
        if ($output->getStep()->getTreeFlow()->getId() !== $treeFlow->getId() ||
            $input->getStep()->getTreeFlow()->getId() !== $treeFlow->getId()) {
            return $this->json([
                'success' => false,
                'error' => 'Output and Input must belong to the same TreeFlow',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate connection
        $validation = $this->validator->validate($output, $input);
        if (!$validation['valid']) {
            return $this->json([
                'success' => false,
                'error' => $validation['error'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Create connection
        $connection = new StepConnection();
        $connection->setSourceOutput($output);
        $connection->setTargetInput($input);

        // Set organization from user
        $user = $this->getUser();
        if ($user && $user->getOrganization()) {
            $connection->setOrganization($user->getOrganization());
        }

        $this->entityManager->persist($connection);
        $this->entityManager->flush();

        error_log("[CREATE CONNECTION] SUCCESS - Created connection ID: " . $connection->getId()?->toString());

        return $this->json([
            'success' => true,
            'connection' => [
                'id' => $connection->getId()?->toString(),
                'sourceOutput' => [
                    'id' => $output->getId()?->toString(),
                    'name' => $output->getName(),
                    'stepId' => $output->getStep()->getId()?->toString(),
                    'stepName' => $output->getStep()->getName(),
                ],
                'targetInput' => [
                    'id' => $input->getId()?->toString(),
                    'name' => $input->getName(),
                    'stepId' => $input->getStep()->getId()?->toString(),
                    'stepName' => $input->getStep()->getName(),
                    'type' => $input->getType()->value,
                ],
            ],
        ]);
    }

    /**
     * Delete connection
     * DELETE /treeflow/{id}/connection/{connectionId}
     */
    #[Route('/{id}/connection/{connectionId}', name: 'treeflow_connection_delete', methods: ['DELETE'])]
    public function deleteConnection(#[MapEntity(id: 'id')] TreeFlow $treeFlow, string $connectionId): JsonResponse
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $connection = $this->entityManager->getRepository(StepConnection::class)->find($connectionId);

        if (!$connection) {
            error_log("[DELETE] Connection not found: {$connectionId}");
            return $this->json([
                'success' => false,
                'error' => 'Connection not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Verify connection belongs to this treeflow
        if ($connection->getSourceOutput()->getStep()->getTreeFlow()->getId() !== $treeFlow->getId()) {
            error_log("[DELETE] Connection does not belong to treeflow: {$connectionId}");
            return $this->json([
                'success' => false,
                'error' => 'Connection does not belong to this TreeFlow',
            ], Response::HTTP_BAD_REQUEST);
        }

        error_log("[DELETE] BEFORE DELETE - Deleting connection: {$connectionId} ({$connection->getSourceOutput()->getName()} -> {$connection->getTargetInput()->getName()})");

        $this->entityManager->remove($connection);

        error_log("[DELETE] After remove(), before flush()");

        $this->entityManager->flush();

        error_log("[DELETE] AFTER FLUSH - Connection should be deleted from DB: {$connectionId}");

        return $this->json([
            'success' => true,
        ]);
    }

    /**
     * Auto-create default output for a step (continuation lines feature)
     * POST /treeflow/{id}/step/{stepId}/output/auto
     */
    #[Route('/{id}/step/{stepId}/output/auto', name: 'treeflow_output_auto_create', methods: ['POST'])]
    public function autoCreateOutput(
        #[MapEntity(id: 'id')] TreeFlow $treeFlow,
        #[MapEntity(id: 'stepId')] Step $step,
        Request $request,
        TranslatorInterface $translator
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        // Verify step belongs to treeflow
        if ($step->getTreeFlow()->getId() !== $treeFlow->getId()) {
            return $this->json([
                'success' => false,
                'error' => 'Step does not belong to this TreeFlow',
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        // Get translated name from translation key or use default
        $outputName = $translator->trans(
            $data['name'] ?? 'output.default.name',
            [],
            $data['translationDomain'] ?? 'treeflow'
        );

        // Create new output
        $output = new StepOutput();
        $output->setStep($step);
        $output->setName($outputName);
        // Note: No destination step initially, will be set when connection is created

        $this->entityManager->persist($output);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'output' => [
                'id' => $output->getId()?->toString(),
                'name' => $output->getName(),
                'stepId' => $step->getId()?->toString(),
            ],
        ]);
    }

    /**
     * Auto-create input for a step when dragging connection to empty step
     * POST /treeflow/{id}/step/{stepId}/input/auto
     */
    #[Route('/{id}/step/{stepId}/input/auto', name: 'treeflow_input_auto_create', methods: ['POST'])]
    public function autoCreateInput(
        #[MapEntity(id: 'id')] TreeFlow $treeFlow,
        #[MapEntity(id: 'stepId')] Step $step,
        Request $request,
        TranslatorInterface $translator
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        // Verify step belongs to treeflow
        if ($step->getTreeFlow()->getId() !== $treeFlow->getId()) {
            return $this->json([
                'success' => false,
                'error' => 'Step does not belong to this TreeFlow',
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        // Check if we have a translation key (continuation feature) or smart name generation (drag-drop feature)
        if (isset($data['name']) && isset($data['translationDomain'])) {
            // Use translation key (from continuation feature)
            $inputName = $translator->trans(
                $data['name'],
                [],
                $data['translationDomain']
            );
        } else {
            // Generate smart input name (from drag-drop feature)
            $inputName = $this->generateInputName(
                $data['outputName'] ?? null,
                $data['sourceStepName'] ?? null
            );
        }

        // Create new input with type ANY
        $input = new StepInput();
        $input->setStep($step);
        $input->setName($inputName);
        $input->setType(\App\Enum\InputType::ANY);

        // Organization is inherited from Step (no need to set explicitly)

        $this->entityManager->persist($input);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'input' => [
                'id' => $input->getId()?->toString(),
                'name' => $input->getName(),
                'type' => $input->getType()->value,
                'stepId' => $step->getId()?->toString(),
            ],
        ]);
    }

    /**
     * Create step via continuation line (n8n pattern)
     * POST /treeflow/{id}/continuation/create-step
     */
    #[Route('/{id}/continuation/create-step', name: 'treeflow_continuation_create_step', methods: ['POST'])]
    public function createStepViaContinuation(
        #[MapEntity(id: 'id')] TreeFlow $treeFlow,
        Request $request,
        TranslatorInterface $translator
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $data = json_decode($request->getContent(), true);

        if (!isset($data['sourceStepId'])) {
            return $this->json([
                'success' => false,
                'error' => 'Missing sourceStepId',
            ], Response::HTTP_BAD_REQUEST);
        }

        $sourceStep = $this->stepRepository->find($data['sourceStepId']);
        if (!$sourceStep || $sourceStep->getTreeFlow()->getId() !== $treeFlow->getId()) {
            return $this->json([
                'success' => false,
                'error' => 'Source step not found or does not belong to this TreeFlow',
            ], Response::HTTP_NOT_FOUND);
        }

        // Determine source output
        $sourceOutput = null;
        $outputCreated = false;

        if (isset($data['sourceOutputId']) && $data['sourceOutputId']) {
            // + was clicked on existing output
            $sourceOutput = $this->outputRepository->find($data['sourceOutputId']);
            if (!$sourceOutput || $sourceOutput->getStep()->getId() !== $sourceStep->getId()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Source output not found or does not belong to source step',
                ], Response::HTTP_NOT_FOUND);
            }
        } else {
            // + was clicked on step itself - create default output
            $outputName = $translator->trans('output.default.name', [], 'treeflow');
            $sourceOutput = new StepOutput();
            $sourceOutput->setStep($sourceStep);
            $sourceOutput->setName($outputName);
            $this->entityManager->persist($sourceOutput);
            $outputCreated = true;
        }

        // Create new step
        $newStep = new Step();
        $newStep->setTreeFlow($treeFlow);
        $newStep->setName($data['name'] ?? 'New Step');
        $newStep->setObjective($data['objective'] ?? '');

        // Calculate position: 1.5x step width to the right of source step
        $stepWidth = 280; // Standard step width from CSS
        $newStep->setPositionX($sourceStep->getPositionX() + $stepWidth + ($stepWidth / 2));
        $newStep->setPositionY($sourceStep->getPositionY());

        $this->entityManager->persist($newStep);

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

        error_log("[CREATE CONNECTION VIA CONTINUATION] Creating connection: {$sourceOutput->getName()} -> {$newInput->getName()}");

        $this->entityManager->persist($connection);

        // Flush all changes
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

        return $this->json($response);
    }

    /**
     * Generate smart input name based on output information
     */
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
}
