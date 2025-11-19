<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Agent;
use App\Entity\TalkMessage;
use App\Message\GenerateAgentResponseCommand;
use App\Repository\AgentRepository;
use App\Repository\TalkMessageRepository;
use App\Repository\TalkRepository;
use App\Service\OpenAI\OpenAIService;
use App\Service\TalkFlow\TalkFlowService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class GenerateAgentResponseHandler
{
    public function __construct(
        private readonly TalkRepository $talkRepository,
        private readonly TalkMessageRepository $talkMessageRepository,
        private readonly AgentRepository $agentRepository,
        private readonly TalkFlowService $talkFlowService,
        private readonly OpenAIService $openAIService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(GenerateAgentResponseCommand $command): void
    {
        $invokeTraceId = uniqid('gen_invoke_', true);
        $this->logger->info('HANDLER TRACE: GenerateAgentResponseHandler invoked', [
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

        // Check if talk is paused
        if ($talk->isPaused()) {
            $this->logger->info('Talk is paused, skipping response generation', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            return;
        }

        // REMOVED: Auto-closing talk when flow completes
        // We want Sarah to continue the conversation even after the structured flow ends
        // The talk should only be closed manually by a user or when explicitly requested
        //
        // if ($this->talkFlowService->isFlowComplete($talk)) {
        //     $this->logger->info('Flow is complete, no more responses needed');
        //     $talk->setStatus(2); // completed
        //     $talk->setClosedAt(new \DateTimeImmutable());
        //     $this->entityManager->flush();
        //     return;
        // }

        try {
            $this->generateResponse($talk, $command->getContextMessage(), $invokeTraceId);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate agent response', [
                'error' => $e->getMessage(),
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            throw $e;
        }
    }

    private function generateResponse(\App\Entity\Talk $talk, ?string $contextMessage, string $traceId): void
    {
        // Get assigned agent or use first available agent
        $agent = $this->getAssignedAgent($talk);
        if (!$agent) {
            $this->logger->error('No agent available for talk', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            return;
        }

        // Get current step from talkFlow (runtime data)
        $currentStep = $this->talkFlowService->getCurrentStep($talk);
        $actionDefinitions = [];

        if (!$currentStep) {
            $this->logger->info('No current step - using conversational mode', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            // Flow is complete - use empty step and actions for pure conversational mode
            $currentStep = [
                'objective' => 'Continue the conversation naturally and help the customer',
                'actions' => [],
            ];
            $actionDefinitions = [];
        } else {
            // Get action definitions from TreeFlow (not runtime answers)
            $actionDefinitions = $this->talkFlowService->getCurrentStepActionDefinitions($talk);
        }

        // Get recent message history (last 10 messages)
        $messages = $this->talkMessageRepository->createQueryBuilder('tm')
            ->where('tm.talk = :talk')
            ->setParameter('talk', $talk)
            ->orderBy('tm.sentAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $messageHistory = array_reverse(array_map(function (TalkMessage $msg) {
            return [
                'direction' => $msg->getDirection(),
                'body' => $msg->getBody(),
                'sentAt' => $msg->getSentAt()->format('c'),
            ];
        }, $messages));

        // Generate response using OpenAI
        $responseText = $this->openAIService->generateAgentResponse(
            $talk,
            $agent,
            $currentStep,
            $actionDefinitions,
            $messageHistory
        );

        // Create outbound message
        $responseMessage = new TalkMessage();
        $responseMessage->setTalk($talk);
        $responseMessage->setOrganization($talk->getOrganization());
        $responseMessage->setFromAgent($agent);
        $responseMessage->setBody($responseText);
        $responseMessage->setDirection('outbound');
        $responseMessage->setMessageType('text');
        $responseMessage->setSentAt(new \DateTimeImmutable());

        $this->logger->info('MESSAGE TRACE: Creating outbound message', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'trace_id' => $traceId,
            'message_preview' => substr($responseText, 0, 50),
        ]);

        $this->entityManager->persist($responseMessage);

        $this->logger->info('MESSAGE TRACE: Before flush', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'trace_id' => $traceId,
            'message_id' => $responseMessage->getId()->toRfc4122(),
        ]);

        // Update talk metadata
        $talk->setDateLastMessage(new \DateTimeImmutable());
        $talk->setMessageCount($talk->getMessageCount() + 1);

        $this->entityManager->flush();

        $this->logger->info('MESSAGE TRACE: After flush - Agent response generated and sent', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'message_id' => $responseMessage->getId()->toRfc4122(),
            'agent_id' => $agent->getId()->toRfc4122(),
            'trace_id' => $traceId,
        ]);
    }

    private function getAssignedAgent(\App\Entity\Talk $talk): ?Agent
    {
        // Try to get agents assigned to this talk
        $agents = $talk->getAgents();
        if (!$agents->isEmpty()) {
            return $agents->first();
        }

        // Otherwise, get first available agent from organization
        $agent = $this->agentRepository->createQueryBuilder('a')
            ->where('a.organization = :org')
            ->andWhere('a.active = true')
            ->andWhere('a.available = true')
            ->setParameter('org', $talk->getOrganization())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Assign agent to talk
        if ($agent) {
            $talk->addAgent($agent);
            $this->entityManager->flush();
        }

        return $agent;
    }
}
