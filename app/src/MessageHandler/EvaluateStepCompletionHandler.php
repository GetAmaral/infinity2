<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\EvaluateStepCompletionCommand;
use App\Message\GenerateAgentResponseCommand;
use App\Repository\TalkMessageRepository;
use App\Repository\TalkRepository;
use App\Service\TalkFlow\TalkFlowService;
use App\Service\TalkFlow\TreeFlowExecutionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class EvaluateStepCompletionHandler
{
    public function __construct(
        private readonly TalkRepository $talkRepository,
        private readonly TalkMessageRepository $talkMessageRepository,
        private readonly TalkFlowService $talkFlowService,
        private readonly TreeFlowExecutionService $executionService,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(EvaluateStepCompletionCommand $command): void
    {
        $invokeTraceId = uniqid('eval_invoke_', true);
        $this->logger->info('HANDLER TRACE: EvaluateStepCompletionHandler invoked', [
            'talk_id' => $command->getTalkId(),
            'trace_id' => $invokeTraceId,
        ]);

        $talk = $this->talkRepository->find($command->getTalkId());

        if (!$talk) {
            $this->logger->error('Talk not found', [
                'talk_id' => $command->getTalkId(),
                'trace_id' => $invokeTraceId,
            ]);
            return;
        }

        $currentStepSlug = $this->talkFlowService->getCurrentStepSlug($talk);
        if (!$currentStepSlug) {
            // No current step - still generate response in conversational mode
            $genTraceId = uniqid('gen_', true);
            $this->logger->info('DISPATCH TRACE: Dispatching GenerateAgentResponseCommand', [
                'talk_id' => $talk->getId()->toRfc4122(),
                'trace_id' => $genTraceId,
                'reason' => 'no_current_step',
                'source' => 'EvaluateStepCompletionHandler',
                'invoke_trace' => $invokeTraceId,
            ]);

            $this->messageBus->dispatch(
                new GenerateAgentResponseCommand(
                    $talk->getId()->toRfc4122(),
                    $messageBody ?? ''
                )
            );
            return;
        }

        // Check if step is complete
        if (!$this->talkFlowService->isStepComplete($talk, $currentStepSlug)) {
            $this->logger->debug('Step not yet complete - generating response for current step', [
                'talk_id' => $talk->getId()->toRfc4122(),
                'step_slug' => $currentStepSlug,
            ]);

            // Step not complete yet, but still generate response
            $genTraceId = uniqid('gen_', true);
            $this->logger->info('DISPATCH TRACE: Dispatching GenerateAgentResponseCommand', [
                'talk_id' => $talk->getId()->toRfc4122(),
                'trace_id' => $genTraceId,
                'reason' => 'step_incomplete',
                'source' => 'EvaluateStepCompletionHandler',
                'invoke_trace' => $invokeTraceId,
            ]);

            $this->messageBus->dispatch(
                new GenerateAgentResponseCommand(
                    $talk->getId()->toRfc4122(),
                    $messageBody ?? ''
                )
            );
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

        // Generate agent response after step evaluation/completion
        $genTraceId = uniqid('gen_', true);
        $this->logger->info('DISPATCH TRACE: Dispatching GenerateAgentResponseCommand', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'trace_id' => $genTraceId,
            'reason' => 'step_completed',
            'source' => 'EvaluateStepCompletionHandler',
            'invoke_trace' => $invokeTraceId,
            'next_step' => $result['nextStepSlug'] ?? 'null',
        ]);

        $this->messageBus->dispatch(
            new GenerateAgentResponseCommand(
                $talk->getId()->toRfc4122(),
                $messageBody
            )
        );
    }
}
