<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Talk;
use App\Entity\TreeFlow;
use App\Repository\TalkRepository;
use App\Repository\TreeFlowRepository;
use App\Service\TalkFlow\TalkFlowService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/talks', name: 'api_talk_flow_')]
#[IsGranted('ROLE_USER')]
class TalkFlowController extends AbstractController
{
    public function __construct(
        private readonly TalkRepository $talkRepository,
        private readonly TreeFlowRepository $treeFlowRepository,
        private readonly TalkFlowService $talkFlowService
    ) {
    }

    /**
     * Initialize TalkFlow for a Talk from Agent's TreeFlow template
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
     */
    #[Route('/{id}/flow/initialize', name: 'initialize', methods: ['POST'])]
    public function initialize(Talk $talk): JsonResponse
    {
        // Check if Talk has an assigned Agent with TreeFlow
        $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow

        if (!$treeFlow) {
            return new JsonResponse([
                'error' => 'Talk has no assigned Agent with TreeFlow. Please assign an Agent with a TreeFlow first.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->talkFlowService->initializeTalkFlow($talk);

        // Get agent info for response
        $agent = $talk->getAgents()->first();

        return new JsonResponse([
            'success' => true,
            'talkFlow' => $talk->getTalkFlow(),
            'agent' => [
                'id' => $agent->getId()->toRfc4122(),
                'name' => $agent->getName(),
            ],
            'treeFlow' => [
                'id' => $treeFlow->getId()->toRfc4122(),
                'name' => $treeFlow->getName(),
            ],
        ]);
    }

    /**
     * Get current TalkFlow state
     */
    #[Route('/{id}/flow', name: 'get_state', methods: ['GET'])]
    public function getState(Talk $talk): JsonResponse
    {
        if (!$talk->getTalkFlow()) {
            return new JsonResponse([
                'error' => 'Talk has no TalkFlow',
            ], Response::HTTP_NOT_FOUND);
        }

        $currentStep = $this->talkFlowService->getCurrentStep($talk);
        $nextAction = $this->talkFlowService->getNextAction($talk);
        $progress = $this->talkFlowService->getProgress($talk);
        $isComplete = $this->talkFlowService->isFlowComplete($talk);

        return new JsonResponse([
            'talkFlow' => $talk->getTalkFlow(),
            'currentStep' => $currentStep,
            'nextAction' => $nextAction,
            'progress' => $progress,
            'isComplete' => $isComplete,
            'paused' => $talk->isPaused(),
            'pausedAt' => $talk->getPausedAt()?->format('c'),
            'pausedReason' => $talk->getPausedReason(),
        ]);
    }

    /**
     * Get conversation progress
     */
    #[Route('/{id}/flow/progress', name: 'progress', methods: ['GET'])]
    public function getProgress(Talk $talk): JsonResponse
    {
        $progress = $this->talkFlowService->getProgress($talk);
        $isComplete = $this->talkFlowService->isFlowComplete($talk);

        return new JsonResponse([
            'progress' => $progress,
            'isComplete' => $isComplete,
            'currentStepSlug' => $this->talkFlowService->getCurrentStepSlug($talk),
        ]);
    }

    /**
     * Get all collected answers
     */
    #[Route('/{id}/flow/answers', name: 'answers', methods: ['GET'])]
    public function getAnswers(Talk $talk): JsonResponse
    {
        $answers = $this->talkFlowService->getAllAnswers($talk);

        return new JsonResponse([
            'answers' => $answers,
        ]);
    }
}
