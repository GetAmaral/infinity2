<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Pipeline;
use App\Security\Voter\DealVoter;
use App\Security\Voter\PipelineVoter;
use App\Service\Pipeline\PipelineViewService;
use App\Service\AI\DealScoringService;
use App\Repository\DealRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/pipeline/view')]
class PipelineViewController extends AbstractController
{
    public function __construct(
        private PipelineViewService $viewService,
        private DealScoringService $scoringService,
        private DealRepository $dealRepo
    ) {}

    /**
     * Get kanban board data
     */
    #[Route('/{id}', name: 'api_pipeline_kanban', methods: ['GET'])]
    public function kanban(Pipeline $pipeline, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(PipelineVoter::VIEW, $pipeline);

        $filters = [
            'owner' => $request->query->get('owner'),
            'minAmount' => $request->query->get('minAmount'),
            'maxAmount' => $request->query->get('maxAmount'),
            'tags' => $request->query->all('tags'),
            'aiScore' => $request->query->get('aiScore'),
        ];

        $data = $this->viewService->getKanbanData($pipeline, $filters);

        return $this->json($data);
    }

    /**
     * Trigger AI scoring for a deal
     */
    #[Route('/deal/{id}/score', name: 'api_deal_score', methods: ['POST'])]
    public function scoreDeal(string $id): JsonResponse
    {
        $deal = $this->dealRepo->find($id);

        if (!$deal) {
            return $this->json(['error' => 'Deal not found'], 404);
        }

        $this->denyAccessUnlessGranted(DealVoter::VIEW, $deal);

        try {
            $analysis = $this->scoringService->analyzeDeal($deal);

            return $this->json([
                'success' => true,
                'analysis' => $analysis,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to analyze deal: ' . $e->getMessage()
            ], 500);
        }
    }
}
