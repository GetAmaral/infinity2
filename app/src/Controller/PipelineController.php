<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\PipelineControllerGenerated;
use App\Entity\Pipeline;
use App\Security\Voter\PipelineVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pipeline Controller
 *
 * This controller handles all pipeline operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see PipelineControllerGenerated for available lifecycle hooks
 */
#[Route('/pipeline')]
final class PipelineController extends PipelineControllerGenerated
{
    /**
     * List all pipelines
     */
    #[Route('', name: 'pipeline_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching pipelines
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'pipeline_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new pipeline
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'pipeline_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing pipeline
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'pipeline_edit', methods: ['GET', 'POST'])]
    public function edit(Pipeline $pipeline, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($pipeline, $request);
        }

        return $this->editFormAction($pipeline, $request);
    }

    /**
     * Delete pipeline
     */
    #[Route('/{id}', name: 'pipeline_delete', methods: ['POST'])]
    public function delete(Pipeline $pipeline, Request $request): Response
    {
        return $this->deleteAction($pipeline, $request);
    }

    /**
     * Show pipeline details
     */
    #[Route('/{id}', name: 'pipeline_show', methods: ['GET'])]
    public function show(Pipeline $pipeline): Response
    {
        return $this->showAction($pipeline);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Pipeline $pipeline): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($pipeline);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new PipelineCreatedEvent($pipeline));
    // }
    //
    // protected function beforeDelete(Pipeline $pipeline): void
    // {
    //     // Check for dependencies
    //     // if ($pipeline->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete pipeline with related records');
    //     // }
    // }

    // Add your custom controller methods here

    /**
     * Show kanban board for a pipeline
     */
    #[Route('/{id}/board', name: 'pipeline_board', methods: ['GET'])]
    public function board(Pipeline $pipeline): Response
    {
        // Check permissions
        $this->denyAccessUnlessGranted(PipelineVoter::VIEW, $pipeline);

        // Calculate metrics
        $activeDeals = $pipeline->getDeals()->filter(fn($d) => in_array($d->getDealStatus(), ['open', 'in_progress']));

        $metrics = [
            'totalPipelineValue' => $activeDeals->reduce(fn($sum, $d) => $sum + ($d->getExpectedAmount() ?? 0), 0),
            'activeDealsCount' => $activeDeals->count(),
            'avgCycleTime' => $pipeline->getAvgCycleTime(),
            'forecastedRevenue' => $this->calculateForecast($activeDeals),
        ];

        return $this->render('pipeline/board.html.twig', [
            'pipeline' => $pipeline,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Calculate probability-weighted forecast
     */
    private function calculateForecast($deals): float
    {
        $total = 0;
        foreach ($deals as $deal) {
            $probability = (float)($deal->getWinProbability() ??
                          $deal->getCurrentStage()?->getProbability() ?? 0);
            $amount = (float)($deal->getExpectedAmount() ?? 0);
            $total += $amount * ($probability / 100);
        }
        return $total;
    }
}
