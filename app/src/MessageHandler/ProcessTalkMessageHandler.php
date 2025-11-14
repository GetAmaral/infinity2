<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\TalkMessage;
use App\Message\EvaluateStepCompletionCommand;
use App\Message\GenerateAgentResponseCommand;
use App\Message\ProcessTalkMessageCommand;
use App\Repository\TalkMessageRepository;
use App\Service\OpenAI\OpenAIService;
use App\Service\TalkFlow\TalkFlowService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ProcessTalkMessageHandler
{
    public function __construct(
        private readonly TalkMessageRepository $talkMessageRepository,
        private readonly TalkFlowService $talkFlowService,
        private readonly OpenAIService $openAIService,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ProcessTalkMessageCommand $command): void
    {
        $message = $this->talkMessageRepository->find($command->getTalkMessageId());

        if (!$message) {
            $this->logger->error('TalkMessage not found', [
                'message_id' => $command->getTalkMessageId(),
            ]);
            return;
        }

        $talk = $message->getTalk();

        // Check if talk is paused
        if ($talk->isPaused()) {
            $this->logger->info('Talk is paused, skipping processing', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            return;
        }

        // Check if this is an inbound message (from contact)
        if ($message->getDirection() !== 'inbound') {
            $this->logger->debug('Message is not inbound, skipping', [
                'message_id' => $message->getId()->toRfc4122(),
            ]);
            return;
        }

        // Check if Talk has Agent with TreeFlow
        if (!$talk->getTreeFlow()) { // Uses Talk->Agent->TreeFlow
            $this->logger->info('Talk has no Agent with TreeFlow, skipping', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            return;
        }

        try {
            $this->processMessage($message);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process talk message', [
                'error' => $e->getMessage(),
                'message_id' => $message->getId()->toRfc4122(),
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);

            // Check if should escalate
            $escalation = $this->openAIService->shouldEscalate($talk, $message->getBody());
            if ($escalation['shouldEscalate']) {
                $this->escalateToHuman($talk, $escalation['reason']);
            }

            throw $e;
        }
    }

    private function processMessage(TalkMessage $message): void
    {
        $talk = $message->getTalk();
        $currentStep = $this->talkFlowService->getCurrentStep($talk);

        if (!$currentStep) {
            $this->logger->warning('No current step in talkFlow', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            return;
        }

        $this->logger->info('Processing inbound message', [
            'message_id' => $message->getId()->toRfc4122(),
            'talk_id' => $talk->getId()->toRfc4122(),
        ]);

        // Extract answers from message using AI
        $extractedAnswers = $this->openAIService->extractAnswers($message, $currentStep);

        // Record extracted answers
        $currentStepSlug = $this->talkFlowService->getCurrentStepSlug($talk);
        foreach ($extractedAnswers as $actionSlug => $answer) {
            if (!empty($answer)) {
                $this->talkFlowService->recordActionAnswer(
                    $talk,
                    $currentStepSlug,
                    $actionSlug,
                    $answer
                );
            }
        }

        // Check if step is now complete
        $this->messageBus->dispatch(
            new EvaluateStepCompletionCommand($talk->getId()->toRfc4122())
        );

        // Generate agent response
        $this->messageBus->dispatch(
            new GenerateAgentResponseCommand(
                $talk->getId()->toRfc4122(),
                $message->getBody()
            )
        );
    }

    private function escalateToHuman(\App\Entity\Talk $talk, string $reason): void
    {
        $talk->setPaused(true);
        $talk->setPausedAt(new \DateTimeImmutable());
        $talk->setPausedReason("Escalated to human: {$reason}");

        $this->entityManager->flush();

        $this->logger->warning('Talk escalated to human', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'reason' => $reason,
        ]);

        // TODO: Send notification to assigned user
    }
}
