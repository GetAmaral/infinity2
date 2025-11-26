<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\AnalyzeDealCommand;
use App\Repository\DealRepository;
use App\Service\AI\DealScoringService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AnalyzeDealHandler
{
    public function __construct(
        private DealRepository $dealRepo,
        private DealScoringService $scoringService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(AnalyzeDealCommand $command): void
    {
        $deal = $this->dealRepo->find($command->getDealId());

        if (!$deal) {
            $this->logger->warning('Deal not found for AI analysis', [
                'deal_id' => $command->getDealId(),
            ]);
            return;
        }

        try {
            $this->scoringService->analyzeDeal($deal);

            $this->logger->info('Deal analyzed by AI', [
                'deal_id' => $command->getDealId(),
                'score' => $deal->getAiScore(),
                'win_probability' => $deal->getWinProbability(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to analyze deal', [
                'deal_id' => $command->getDealId(),
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }
}
