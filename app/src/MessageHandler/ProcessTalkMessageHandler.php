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
        $this->logger->info('HANDLER INVOKE START', ['message_id' => $command->getTalkMessageId()]);

        $message = $this->talkMessageRepository->find($command->getTalkMessageId());

        if (!$message) {
            $this->logger->error('TalkMessage not found', [
                'message_id' => $command->getTalkMessageId(),
            ]);
            return;
        }

        $this->logger->info('Message found', ['message_id' => $message->getId()->toRfc4122()]);

        $talk = $message->getTalk();

        $this->logger->info('Talk retrieved', ['talk_id' => $talk->getId()->toRfc4122()]);

        // Check if talk is paused
        if ($talk->isPaused()) {
            $this->logger->info('Talk is paused, skipping processing', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            return;
        }

        $this->logger->info('Talk is not paused');

        // Check if this is an inbound message (from contact)
        if ($message->getDirection() !== 'inbound') {
            $this->logger->debug('Message is not inbound, skipping', [
                'message_id' => $message->getId()->toRfc4122(),
            ]);
            return;
        }

        $this->logger->info('Message is inbound, checking TreeFlow');

        // Check if Talk has Agent with TreeFlow
        try {
            $treeFlow = $talk->getTreeFlow();
            $this->logger->info('TreeFlow check completed', [
                'has_treeflow' => $treeFlow !== null,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('ERROR getting TreeFlow', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }

        if (!$treeFlow) {
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

        $this->logger->info('ProcessMessage started', [
            'message_id' => $message->getId()->toRfc4122(),
            'talk_id' => $talk->getId()->toRfc4122(),
        ]);

        try {
            $currentStep = $this->talkFlowService->getCurrentStep($talk);
        } catch (\Throwable $e) {
            $this->logger->error('ERROR in getCurrentStep', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            throw $e;
        }

        if (!$currentStep) {
            $this->logger->info('No current step - flow is complete, continuing in conversational mode', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);

            // Flow is complete, but still generate conversational responses
            $this->messageBus->dispatch(
                new GenerateAgentResponseCommand(
                    $talk->getId()->toRfc4122(),
                    $message->getBody()
                )
            );
            return;
        }

        $this->logger->info('Processing inbound message with structured flow', [
            'message_id' => $message->getId()->toRfc4122(),
            'talk_id' => $talk->getId()->toRfc4122(),
        ]);

        // Get action definitions from TreeFlow (not runtime answers from talkFlow)
        $actionDefinitions = $this->talkFlowService->getCurrentStepActionDefinitions($talk);

        $this->logger->info('Action definitions retrieved', [
            'action_count' => count($actionDefinitions),
            'action_slugs' => array_keys($actionDefinitions),
        ]);

        // Extract answers from message using AI
        $extractedAnswers = $this->openAIService->extractAnswers($message, $actionDefinitions);

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

        // Check if step is now complete - this will trigger response generation
        $traceId = uniqid('eval_', true);
        $this->logger->info('DISPATCH TRACE: Dispatching EvaluateStepCompletionCommand', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'trace_id' => $traceId,
            'source' => 'ProcessTalkMessageHandler',
        ]);

        $this->messageBus->dispatch(
            new EvaluateStepCompletionCommand($talk->getId()->toRfc4122())
        );

        // NOTE: GenerateAgentResponseCommand will be dispatched by EvaluateStepCompletionHandler
        // after determining the current step (whether we moved to next step or stayed on current)
        // This prevents duplicate responses
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
