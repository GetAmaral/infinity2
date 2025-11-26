<?php

declare(strict_types=1);

namespace App\Service\Pipeline;

use App\Entity\Deal;
use App\Entity\DealStage;
use App\Entity\PipelineStage;
use App\Message\AnalyzeDealCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;

class DealMovementService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger
    ) {}

    /**
     * Move deal to a new stage
     */
    public function moveDealToStage(Deal $deal, PipelineStage $newStage): void
    {
        $oldStage = $deal->getCurrentStage();

        // Create historical record
        if ($oldStage) {
            $dealStage = new DealStage();
            $dealStage->setDeal($deal);
            $dealStage->setPipelineStage($oldStage);
            $dealStage->setStartedAt($deal->getUpdatedAt());
            $dealStage->setEndedAt(new \DateTimeImmutable());

            $duration = $deal->getUpdatedAt()->diff(new \DateTimeImmutable())->days;
            $dealStage->setDaysInStage($duration);

            $this->em->persist($dealStage);
        }

        // Update deal
        $deal->setCurrentStage($newStage);
        $deal->setDaysInCurrentStage(0);
        $deal->setProbability((string)$newStage->getProbability());
        $deal->setLastActivityDate(new \DateTimeImmutable());

        $this->em->flush();

        $this->logger->info('Deal moved to new stage', [
            'deal_id' => $deal->getId()->toRfc4122(),
            'old_stage' => $oldStage?->getName(),
            'new_stage' => $newStage->getName(),
        ]);

        // Trigger AI re-analysis asynchronously
        $this->messageBus->dispatch(
            new AnalyzeDealCommand($deal->getId()->toRfc4122())
        );
    }

    /**
     * Bulk move deals
     */
    public function bulkMoveDeals(array $deals, PipelineStage $newStage): void
    {
        foreach ($deals as $deal) {
            $this->moveDealToStage($deal, $newStage);
        }
    }
}
