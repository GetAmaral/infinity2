<?php

declare(strict_types=1);

namespace App\Service\OpenAI;

use App\Entity\Agent;
use App\Entity\Talk;
use App\Entity\TalkMessage;
use App\Entity\TreeFlow;
use OpenAI;
use Psr\Log\LoggerInterface;

class OpenAIService
{
    private readonly OpenAI\Client $client;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $maxTokens,
        private readonly float $temperature,
        private readonly LoggerInterface $logger
    ) {
        $this->client = OpenAI::client($this->apiKey);
    }

    /**
     * Generate agent response based on conversation context
     *
     * @param Talk $talk Current conversation
     * @param Agent $agent SDR agent
     * @param array $currentStep Current TreeFlow step data
     * @param array $messageHistory Recent message history
     * @return string Generated response
     */
    public function generateAgentResponse(
        Talk $talk,
        Agent $agent,
        array $currentStep,
        array $messageHistory
    ): string {
        $systemPrompt = $this->buildSystemPrompt($agent, $currentStep);
        $messages = $this->buildMessageContext($messageHistory, $currentStep);

        try {
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ...$messages,
                ],
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
            ]);

            $content = $response->choices[0]->message->content;

            $this->logger->info('OpenAI response generated', [
                'talk_id' => $talk->getId()->toRfc4122(),
                'tokens_used' => $response->usage->totalTokens,
            ]);

            return $content;
        } catch (\Exception $e) {
            $this->logger->error('OpenAI API error', [
                'error' => $e->getMessage(),
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);

            throw new \RuntimeException('Failed to generate agent response: ' . $e->getMessage());
        }
    }

    /**
     * Extract structured answers from lead's message
     *
     * @param TalkMessage $message Lead's message
     * @param array $currentStep Current step with actions
     * @return array Map of action slug => extracted answer
     */
    public function extractAnswers(TalkMessage $message, array $currentStep): array
    {
        $actions = $currentStep['actions'] ?? [];

        if (empty($actions)) {
            return [];
        }

        // Build function schema for structured extraction
        $functionSchema = $this->buildExtractionSchema($actions);

        try {
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert at extracting information from sales conversations. Extract answers to the specified questions from the lead\'s message.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Lead's message: " . $message->getBody(),
                    ],
                ],
                'functions' => [$functionSchema],
                'function_call' => ['name' => 'extract_answers'],
                'temperature' => 0.3, // Lower temperature for extraction
            ]);

            $functionCall = $response->choices[0]->message->functionCall ?? null;

            if (!$functionCall) {
                $this->logger->warning('No function call in extraction response', [
                    'message_id' => $message->getId()->toRfc4122(),
                ]);
                return [];
            }

            $extracted = json_decode($functionCall->arguments, true);

            $this->logger->info('Answers extracted', [
                'message_id' => $message->getId()->toRfc4122(),
                'extracted_count' => count($extracted['answers'] ?? []),
            ]);

            return $extracted['answers'] ?? [];
        } catch (\Exception $e) {
            $this->logger->error('Answer extraction failed', [
                'error' => $e->getMessage(),
                'message_id' => $message->getId()->toRfc4122(),
            ]);

            return [];
        }
    }

    /**
     * Evaluate if an output condition is satisfied
     *
     * @param string $condition Output condition text
     * @param array $answers Collected answers so far
     * @param string $leadMessage Latest lead message
     * @return bool True if condition is satisfied
     */
    public function evaluateCondition(
        string $condition,
        array $answers,
        string $leadMessage
    ): bool {
        $prompt = $this->buildConditionEvaluationPrompt($condition, $answers, $leadMessage);

        try {
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a sales qualification expert. Evaluate if the given condition is satisfied based on the conversation data. Respond with only "YES" or "NO".',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => 10,
                'temperature' => 0.1, // Very low for binary decision
            ]);

            $result = trim(strtoupper($response->choices[0]->message->content));
            $satisfied = in_array($result, ['YES', 'TRUE', '1']);

            $this->logger->info('Condition evaluated', [
                'condition' => substr($condition, 0, 100),
                'result' => $satisfied,
            ]);

            return $satisfied;
        } catch (\Exception $e) {
            $this->logger->error('Condition evaluation failed', [
                'error' => $e->getMessage(),
                'condition' => $condition,
            ]);

            // Default to false on error - safe fallback
            return false;
        }
    }

    /**
     * Determine if the agent should escalate to a human
     *
     * @param Talk $talk Current conversation
     * @param string $leadMessage Latest lead message
     * @return array ['shouldEscalate' => bool, 'reason' => string]
     */
    public function shouldEscalate(Talk $talk, string $leadMessage): array
    {
        try {
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a sales conversation analyzer. Determine if the lead\'s message requires human intervention. Reasons to escalate: complex technical questions, pricing negotiations, complaints, urgent requests, or confusion.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Lead's message: " . $leadMessage,
                    ],
                ],
                'functions' => [[
                    'name' => 'escalation_decision',
                    'description' => 'Decide if conversation should be escalated to human',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'shouldEscalate' => [
                                'type' => 'boolean',
                                'description' => 'True if human intervention is needed',
                            ],
                            'reason' => [
                                'type' => 'string',
                                'description' => 'Reason for escalation decision',
                            ],
                            'urgency' => [
                                'type' => 'string',
                                'enum' => ['low', 'medium', 'high'],
                                'description' => 'Urgency level',
                            ],
                        ],
                        'required' => ['shouldEscalate', 'reason', 'urgency'],
                    ],
                ]],
                'function_call' => ['name' => 'escalation_decision'],
            ]);

            $functionCall = $response->choices[0]->message->functionCall;
            $decision = json_decode($functionCall->arguments, true);

            $this->logger->info('Escalation decision made', [
                'talk_id' => $talk->getId()->toRfc4122(),
                'should_escalate' => $decision['shouldEscalate'],
                'reason' => $decision['reason'],
            ]);

            return $decision;
        } catch (\Exception $e) {
            $this->logger->error('Escalation decision failed', [
                'error' => $e->getMessage(),
            ]);

            // Conservative: escalate on error
            return [
                'shouldEscalate' => true,
                'reason' => 'System error occurred',
                'urgency' => 'high',
            ];
        }
    }

    /**
     * Build system prompt for agent
     */
    private function buildSystemPrompt(Agent $agent, array $currentStep): string
    {
        $agentPrompt = $agent->getPrompt() ?? 'You are a professional SDR agent.';
        $stepObjective = $currentStep['objective'] ?? '';

        $actions = [];
        foreach ($currentStep['actions'] ?? [] as $slug => $actionData) {
            $actions[] = "- {$actionData['prompt']}";
        }
        $actionsText = implode("\n", $actions);

        return <<<PROMPT
{$agentPrompt}

CURRENT CONVERSATION STAGE:
{$stepObjective}

YOUR OBJECTIVES FOR THIS STAGE:
{$actionsText}

GUIDELINES:
- Be conversational and natural
- Ask one or two questions at a time, don't overwhelm
- Listen actively and acknowledge what the lead shares
- Build rapport while gathering information
- Be helpful and consultative, not pushy
- If the lead asks questions, answer them before continuing with your objectives
- Keep responses concise (2-4 sentences typically)
PROMPT;
    }

    /**
     * Build message context for API call
     */
    private function buildMessageContext(array $messageHistory, array $currentStep): array
    {
        $messages = [];

        foreach ($messageHistory as $msg) {
            $role = $msg['direction'] === 'inbound' ? 'user' : 'assistant';
            $messages[] = [
                'role' => $role,
                'content' => $msg['body'],
            ];
        }

        return $messages;
    }

    /**
     * Build function schema for answer extraction
     */
    private function buildExtractionSchema(array $actions): array
    {
        $properties = [];

        foreach ($actions as $slug => $actionData) {
            $properties[$slug] = [
                'type' => 'string',
                'description' => "Answer to: {$actionData['prompt']}",
            ];
        }

        return [
            'name' => 'extract_answers',
            'description' => 'Extract answers to sales qualification questions',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'answers' => [
                        'type' => 'object',
                        'properties' => $properties,
                        'description' => 'Extracted answers mapped to question slugs',
                    ],
                ],
            ],
        ];
    }

    /**
     * Build prompt for condition evaluation
     */
    private function buildConditionEvaluationPrompt(
        string $condition,
        array $answers,
        string $leadMessage
    ): string {
        $answersText = json_encode($answers, JSON_PRETTY_PRINT);

        return <<<PROMPT
CONDITION TO EVALUATE:
{$condition}

COLLECTED ANSWERS:
{$answersText}

LATEST LEAD MESSAGE:
{$leadMessage}

Based on the above information, is the condition satisfied? Answer YES or NO.
PROMPT;
    }
}
