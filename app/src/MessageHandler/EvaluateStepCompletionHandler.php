<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\EvaluateStepCompletionCommand;
use App\Repository\TalkMessageRepository;
use App\Repository\TalkRepository;
use App\Service\TalkFlow\TalkFlowService;
use App\Service\TalkFlow\TreeFlowExecutionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class EvaluateStepCompletionHandler
{
    public function __construct(
        private readonly TalkRepository $talkRepository,
        private readonly TalkMessageRepository $talkMessageRepository,
        private readonly TalkFlowService $talkFlowService,
        private readonly TreeFlowExecutionService $executionService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(EvaluateStepCompletionCommand $command): void
    {
        $talk = $this->talkRepository->find($command->getTalkId());

        if (!$talk) {
            $this->logger->error('Talk not found', [
                'talk_id' => $command->getTalkId(),
            ]);
            return;
        }

        $currentStepSlug = $this->talkFlowService->getCurrentStepSlug($talk);
        if (!$currentStepSlug) {
            return;
        }

        // Check if step is complete
        if (!$this->talkFlowService->isStepComplete($talk, $currentStepSlug)) {
            $this->logger->debug('Step not yet complete', [
                'talk_id' => $talk->getId()->toRfc4122(),
                'step_slug' => $currentStepSlug,
            ]);
            return;
        }

        // Get latest message for context
        $latestMessage = $this->talkMessageRepository->createQueryBuilder('tm')
            ->where('tm.talk = :talk')
            ->andWhere('tm.direction = :direction')
            ->setParameter('talk', $talk)
            ->setParameter('direction', 'inbound')
            ->orderBy('tm.sentAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $messageBody = $latestMessage ? $latestMessage->getBody() : '';

        // Evaluate conditions and get next step
        $result = $this->executionService->evaluateAndSelectNextStep($talk, $messageBody);

        if ($result['nextStepSlug']) {
            // Move to next step
            $this->talkFlowService->completeStep(
                $talk,
                $currentStepSlug,
                $result['outputSlug'],
                $result['nextStepSlug']
            );

            $this->logger->info('Moved to next step', [
                'talk_id' => $talk->getId()->toRfc4122(),
                'from_step' => $currentStepSlug,
                'to_step' => $result['nextStepSlug'],
            ]);
        } else {
            // Flow is complete
            $this->talkFlowService->completeStep(
                $talk,
                $currentStepSlug,
                $result['outputSlug'],
                null
            );

            $this->logger->info('Flow completed', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
        }
    }
}
