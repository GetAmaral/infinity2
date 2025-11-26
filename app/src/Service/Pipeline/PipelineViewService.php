<?php

declare(strict_types=1);

namespace App\Service\Pipeline;

use App\Entity\Pipeline;
use App\Repository\DealRepository;

class PipelineViewService
{
    public function __construct(
        private DealRepository $dealRepo
    ) {}

    /**
     * Get full kanban board data for a pipeline
     */
    public function getKanbanData(Pipeline $pipeline, array $filters = []): array
    {
        $stages = $pipeline->getStages();
        $kanbanData = [];

        foreach ($stages as $stage) {
            $deals = $this->dealRepo->findByStageWithFilters($stage, $filters);

            $kanbanData[] = [
                'stage' => [
                    'id' => $stage->getId()->toRfc4122(),
                    'name' => $stage->getName(),
                    'color' => $stage->getColor(),
                    'probability' => $stage->getProbability(),
                    'displayOrder' => $stage->getDisplayOrder(),
                ],
                'metrics' => [
                    'totalValue' => $this->calculateStageValue($deals),
                    'dealCount' => count($deals),
                    'avgDaysInStage' => $this->calculateAvgDays($deals),
                ],
                'deals' => array_map(fn($deal) => $this->formatDealCard($deal), $deals),
            ];
        }

        return [
            'pipeline' => [
                'id' => $pipeline->getId()->toRfc4122(),
                'name' => $pipeline->getName(),
                'color' => $pipeline->getColor(),
                'icon' => $pipeline->getIcon(),
            ],
            'stages' => $kanbanData,
            'overallMetrics' => $this->calculateOverallMetrics($pipeline),
        ];
    }

    /**
     * Format deal for card display
     */
    private function formatDealCard($deal): array
    {
        return [
            'id' => $deal->getId()->toRfc4122(),
            'name' => $deal->getName(),
            'company' => [
                'id' => $deal->getCompany()?->getId()->toRfc4122(),
                'name' => $deal->getCompany()?->getName(),
            ],
            'amount' => $deal->getExpectedAmount(),
            'currency' => $deal->getCurrency() ?? 'USD',
            'owner' => [
                'name' => $deal->getOwner()?->getFullName(),
                'avatar' => $deal->getOwner()?->getProfilePictureUrl(),
            ],
            'team' => $deal->getTeam()->map(fn($u) => [
                'name' => $u->getFullName(),
                'avatar' => $u->getProfilePictureUrl(),
            ])->toArray(),
            'primaryContact' => [
                'name' => $deal->getPrimaryContact()?->getName(),
                'title' => $deal->getPrimaryContact()?->getTitle(),
            ],
            'indicators' => [
                'isHot' => $deal->isHotDeal(),
                'isAtRisk' => $deal->isAtRisk(),
                'isRotten' => $deal->isRotten(),
                'hasPendingTasks' => $deal->getTasks()->filter(fn($t) => $t->getCompletionPercentage() < 100)->count() > 0,
            ],
            'ai' => [
                'score' => $deal->getAiScore(),
                'winProbability' => $deal->getWinProbability(),
                'topRecommendation' => $deal->getRecommendedActions()[0] ?? null,
            ],
            'lastActivity' => $deal->getLastActivityDate()?->format('c'),
            'daysInStage' => $deal->getDaysInCurrentStage(),
            'tags' => $deal->getTags()->map(fn($t) => [
                'name' => $t->getName(),
                'color' => $t->getColor() ?? '#6c757d',
            ])->toArray(),
        ];
    }

    /**
     * Calculate total value in stage
     */
    private function calculateStageValue(array $deals): float
    {
        return array_reduce($deals, function($sum, $deal) {
            return $sum + (float)($deal->getExpectedAmount() ?? 0);
        }, 0.0);
    }

    /**
     * Calculate average days in stage
     */
    private function calculateAvgDays(array $deals): float
    {
        if (empty($deals)) {
            return 0;
        }

        $total = array_reduce($deals, function($sum, $deal) {
            return $sum + ($deal->getDaysInCurrentStage() ?? 0);
        }, 0.0);

        return round($total / count($deals), 1);
    }

    /**
     * Calculate overall pipeline metrics
     */
    private function calculateOverallMetrics(Pipeline $pipeline): array
    {
        return [
            'totalPipelineValue' => $pipeline->getTotalPipelineValue(),
            'activeDealsCount' => $pipeline->getActiveDealsCount(),
            'avgDealSize' => $pipeline->getAvgDealSize(),
            'winRate' => $pipeline->getWinRate(),
            'avgCycleTime' => $pipeline->getAvgCycleTime(),
            'forecastedRevenue' => $this->calculateForecast($pipeline),
        ];
    }

    /**
     * Calculate probability-weighted forecast
     */
    private function calculateForecast(Pipeline $pipeline): float
    {
        $total = 0;
        foreach ($pipeline->getDeals() as $deal) {
            if ($deal->getDealStatus() === 'active') {
                $probability = (float)($deal->getWinProbability() ??
                              $deal->getCurrentStage()?->getProbability() ?? 0);
                $amount = (float)($deal->getExpectedAmount() ?? 0);
                $total += $amount * ($probability / 100);
            }
        }
        return $total;
    }
}
