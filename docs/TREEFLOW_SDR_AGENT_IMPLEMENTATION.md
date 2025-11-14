# TreeFlow SDR Agent - Complete Implementation Plan

**Version:** 1.0
**Date:** 2025-01-06
**Status:** Ready for Implementation

---

## 📋 Table of Contents

1. [Executive Summary](#executive-summary)
2. [Technical Architecture](#technical-architecture)
3. [Phase 1: Foundation & Database](#phase-1-foundation--database)
4. [Phase 2: OpenAI Service Integration](#phase-2-openai-service-integration)
5. [Phase 3: TalkFlow Service Layer](#phase-3-talkflow-service-layer)
6. [Phase 4: Async Message Processing](#phase-4-async-message-processing)
7. [Phase 5: API Endpoints](#phase-5-api-endpoints)
8. [Phase 6: Conversation Pause/Resume](#phase-6-conversation-pauseresume)
9. [Phase 7: Testing & Monitoring](#phase-7-testing--monitoring)
10. [Configuration & Environment](#configuration--environment)
11. [Usage Examples](#usage-examples)
12. [Troubleshooting Guide](#troubleshooting-guide)

---

## Executive Summary

### Objective
Implement an AI-powered SDR (Sales Development Representative) agent that uses TreeFlow workflows to guide conversations with leads, automatically qualify prospects, and manage multi-step sales conversations.

### Architecture Decision: Talk → Agent → TreeFlow

**IMPORTANT:** This implementation uses an **indirect relationship** for TreeFlow access:

```
Talk (many-to-many) ──→ Agent (many-to-one) ──→ TreeFlow
```

**Why this design?**
- Agents can be reusable across multiple Talks
- Each Agent has ONE TreeFlow template that defines their conversation flow
- Multiple Agents can be assigned to a Talk (e.g., SDR + Account Executive)
- TreeFlow is associated with the **Agent role**, not the conversation instance

**Access pattern:**
```php
// ✅ CORRECT: Access TreeFlow via Agent
$agent = $talk->getAgents()->first();
$treeFlow = $agent->getTreeFlow();

// ❌ WRONG: Direct access (doesn't exist)
$treeFlow = $talk->getTreeFlow(); // This method should NOT exist
```

### Key Decisions
- **AI Provider:** OpenAI (GPT-4)
- **Authentication:** `.env` API key configuration
- **Condition Evaluation:** AI-evaluated using GPT-4
- **Response Mode:** Fully automated agent responses
- **Processing:** Async message queue (Symfony Messenger)
- **Conversation State:** Full pause/resume support with context preservation
- **TreeFlow Access:** Via Agent (Talk → Agent → TreeFlow)

### Success Criteria
- ✅ Agent responds within 30 seconds to lead messages
- ✅ Correctly extracts answers from natural language
- ✅ Follows TreeFlow step sequence accurately
- ✅ Evaluates output conditions correctly (>90% accuracy)
- ✅ Maintains context across conversation pauses
- ✅ Escalates to human when uncertain

---

## Technical Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     LUMINAI PLATFORM                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐      ┌──────────────┐                    │
│  │   Contact    │──────│     Talk     │                    │
│  │              │      │              │                    │
│  │  - name      │      │ - subject    │                    │
│  │  - email     │      │ - talkFlow   │◄───┐              │
│  │  - phone     │      │ - status     │    │              │
│  └──────────────┘      │ - paused     │    │              │
│                        │ - pausedAt   │    │              │
│                        └──────┬───────┘    │              │
│                               │            │              │
│                        ┌──────▼──────┐     │              │
│                        │ TalkMessage │     │              │
│                        │             │     │              │
│                        │ - body      │     │              │
│                        │ - direction │     │              │
│                        │ - sentAt    │     │              │
│                        └─────────────┘     │              │
│                               │            │              │
│                        ┌──────▼──────┐     │              │
│                        │    Agent    │─────┘              │
│                        │             │                    │
│                        │ - name      │                    │
│                        │ - treeFlow  │───┐                │
│                        │ - prompt    │   │                │
│                        └─────────────┘   │                │
│                                          │                │
│  ┌──────────────┐      ┌──────────────┐ │                │
│  │  TreeFlow    │◄─────│    Step      │ │                │
│  │              │      │              │ │                │
│  │ - name       │      │ - objective  │ │                │
│  │ - slug       │      │ - actions    │ │                │
│  │ - talkFlow   │      │ - outputs    │ │                │
│  │  template    │      └──────────────┘ │                │
│  └──────────────┘                       │                │
│          ▲                              │                │
└──────────┼──────────────────────────────┼────────────────┘
           └──────────────────────────────┘
                    │
         ┌──────────▼───────────┐
         │  SERVICE LAYER       │
         ├──────────────────────┤
         │                      │
         │ ┌──────────────────┐ │
         │ │  OpenAIService   │ │
         │ │                  │ │
         │ │ - generateMsg()  │ │
         │ │ - extractAnswers│ │
         │ │ - evaluateCond() │ │
         │ └──────────────────┘ │
         │                      │
         │ ┌──────────────────┐ │
         │ │ TalkFlowService  │ │
         │ │                  │ │
         │ │ - initialize()   │ │
         │ │ - recordAnswer() │ │
         │ │ - getCurrentStep│ │
         │ │ - moveToNext()   │ │
         │ └──────────────────┘ │
         │                      │
         │ ┌──────────────────┐ │
         │ │TreeFlowExecution │ │
         │ │     Service      │ │
         │ │                  │ │
         │ │ - evaluateOutput│ │
         │ │ - getNextStep()  │ │
         │ │ - isComplete()   │ │
         │ └──────────────────┘ │
         │                      │
         └──────────────────────┘
                    │
         ┌──────────▼───────────┐
         │   MESSAGE QUEUE      │
         │  (Symfony Messenger) │
         ├──────────────────────┤
         │                      │
         │ ProcessTalkMessage   │
         │ GenerateAgentResponse│
         │ EvaluateConditions   │
         │                      │
         └──────────────────────┘
                    │
         ┌──────────▼───────────┐
         │    OPENAI API        │
         │                      │
         │  GPT-4 Model         │
         │  - Chat Completion   │
         │  - Function Calling  │
         │                      │
         └──────────────────────┘
```

### Data Flow

```
1. Lead Message Arrives
   ↓
2. Create TalkMessage (inbound)
   ↓
3. Dispatch ProcessTalkMessage to Queue
   ↓
4. [ASYNC] Message Handler Executes:
   │
   ├─→ Check if Talk is paused
   │   └─→ If paused: Queue for later, exit
   │
   ├─→ Load Talk + Agent + TreeFlow + talkFlow state
   │   └─→ Get TreeFlow via Agent: talk->agents->first()->treeFlow
   │
   ├─→ OpenAI: Extract answers from message
   │   └─→ Call GPT-4 with function schema
   │
   ├─→ TalkFlowService: Record answers
   │   └─→ Update talkFlow JSON
   │
   ├─→ Check if current step complete
   │   └─→ If yes: Evaluate output conditions (AI)
   │       └─→ Move to next step
   │
   ├─→ OpenAI: Generate agent response
   │   └─→ Context: TreeFlow, talkFlow, history, Agent
   │
   ├─→ Create TalkMessage (outbound)
   │
   └─→ Update Talk metadata
       └─→ dateLastMessage, messageCount

5. Agent Response Sent to Lead
```

---

## Phase 1: Foundation & Database

### 1.1 Add talkFlow Field to Talk Entity

**Update generator_property table:**

```bash
docker-compose exec -T app php bin/console dbal:run-sql "
INSERT INTO generator_property (
    id,
    entity_id,
    property_name,
    property_type,
    nullable,
    default_value
) VALUES (
    gen_random_uuid(),
    (SELECT id FROM generator_entity WHERE entity_name = 'Talk'),
    'talkFlow',
    'json',
    true,
    NULL
);"
```

### 1.2 Verify Agent->TreeFlow Relationship

**Important:** TreeFlow is accessed via Agent, not directly from Talk.

The relationship chain is: **Talk -> Agent -> TreeFlow**

```bash
# Verify Agent has TreeFlow relationship
docker-compose exec -T app php bin/console dbal:run-sql "
SELECT column_name, data_type
FROM information_schema.columns
WHERE table_name = 'agent' AND column_name = 'tree_flow_id';"
```

Expected output:
```
 column_name    | data_type
----------------+-----------
 tree_flow_id   | uuid
```

**Note:** This relationship already exists in the codebase. Agent entity has:
```php
#[ORM\ManyToOne(targetEntity: TreeFlow::class)]
protected ?TreeFlow $treeFlow = null;
```

### 1.3 Add Pause Support Fields

```bash
docker-compose exec -T app php bin/console dbal:run-sql "
INSERT INTO generator_property (
    id,
    entity_id,
    property_name,
    property_type,
    nullable,
    default_value
) VALUES
(
    gen_random_uuid(),
    (SELECT id FROM generator_entity WHERE entity_name = 'Talk'),
    'paused',
    'boolean',
    false,
    'false'
),
(
    gen_random_uuid(),
    (SELECT id FROM generator_entity WHERE entity_name = 'Talk'),
    'pausedAt',
    'datetime_immutable',
    true,
    NULL
),
(
    gen_random_uuid(),
    (SELECT id FROM generator_entity WHERE entity_name = 'Talk'),
    'pausedReason',
    'text',
    true,
    NULL
),
(
    gen_random_uuid(),
    (SELECT id FROM generator_entity WHERE entity_name = 'Talk'),
    'resumedAt',
    'datetime_immutable',
    true,
    NULL
);"
```

### 1.4 Regenerate Talk Entity

```bash
docker-compose exec -T app php bin/console genmax:generate Talk
```

### 1.5 Create and Run Migration

```bash
docker-compose exec -T app php bin/console doctrine:migrations:diff --no-interaction
docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction
```

### 1.6 Verify Schema

```bash
# Verify Talk has required fields (NO tree_flow_id - that's on Agent)
docker-compose exec -T app php bin/console dbal:run-sql "
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'talk'
AND column_name IN ('talk_flow', 'paused', 'paused_at', 'paused_reason', 'resumed_at')
ORDER BY ordinal_position;"
```

**Expected Output:**
```
 column_name    | data_type                   | is_nullable
----------------+-----------------------------+-------------
 talk_flow      | json                        | YES
 paused         | boolean                     | NO
 paused_at      | timestamp without time zone | YES
 paused_reason  | text                        | YES
 resumed_at     | timestamp without time zone | YES
```

**Important:** TreeFlow relationship is on Agent, not Talk:
```bash
# Verify Agent->TreeFlow relationship
docker-compose exec -T app php bin/console dbal:run-sql "
SELECT column_name FROM information_schema.columns
WHERE table_name = 'agent' AND column_name = 'tree_flow_id';"
```

---

## Phase 2: OpenAI Service Integration

### 2.1 Install OpenAI PHP Client

```bash
docker-compose exec -T app composer require openai-php/client
```

### 2.2 Environment Configuration

Add to `/home/user/inf/app/.env`:

```bash
###> OpenAI Configuration ###
OPENAI_API_KEY=sk-proj-your-api-key-here
OPENAI_MODEL=gpt-4o
OPENAI_MAX_TOKENS=1000
OPENAI_TEMPERATURE=0.7
OPENAI_TIMEOUT=30
###< OpenAI Configuration ###
```

**Security Note:** Add `.env.local` to `.gitignore` (already done). Never commit API keys.

### 2.3 Create OpenAI Service

**File:** `/home/user/inf/app/src/Service/OpenAI/OpenAIService.php`

```php
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
```

### 2.4 Register OpenAI Service

**File:** `/home/user/inf/app/config/services.yaml`

Add to services:

```yaml
    # OpenAI Service Configuration
    App\Service\OpenAI\OpenAIService:
        arguments:
            $apiKey: '%env(OPENAI_API_KEY)%'
            $model: '%env(OPENAI_MODEL)%'
            $maxTokens: '%env(int:OPENAI_MAX_TOKENS)%'
            $temperature: '%env(float:OPENAI_TEMPERATURE)%'
            $logger: '@monolog.logger'
```

---

## Phase 3: TalkFlow Service Layer

### 3.1 Create TalkFlowService

**File:** `/home/user/inf/app/src/Service/TalkFlow/TalkFlowService.php`

```php
<?php

declare(strict_types=1);

namespace App\Service\TalkFlow;

use App\Entity\Talk;
use App\Entity\TreeFlow;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TalkFlowService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Initialize talkFlow from TreeFlow template
     * TreeFlow is accessed via Agent: talk->agents->first()->treeFlow
     */
    public function initializeTalkFlow(Talk $talk): void
    {
        // Get TreeFlow from assigned Agent
        $agent = $talk->getAgents()->first();
        if (!$agent) {
            throw new \RuntimeException('Talk has no assigned Agent');
        }

        $treeFlow = $agent->getTreeFlow();
        if (!$treeFlow) {
            throw new \RuntimeException('Agent has no TreeFlow assigned');
        }

        // Get the template from TreeFlow
        $template = $treeFlow->getTalkFlow();
        if (!$template) {
            throw new \RuntimeException('TreeFlow does not have a talkFlow template');
        }

        // Copy template to Talk
        $talk->setTalkFlow($template);

        $this->entityManager->flush();

        $this->logger->info('TalkFlow initialized', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'agent_id' => $agent->getId()->toRfc4122(),
            'tree_flow_id' => $treeFlow->getId()->toRfc4122(),
        ]);
    }

    /**
     * Get current step data from talkFlow
     * TreeFlow is accessed via Agent: talk->agents->first()->treeFlow
     */
    public function getCurrentStep(Talk $talk): ?array
    {
        $talkFlow = $talk->getTalkFlow();
        if (!$talkFlow) {
            return null;
        }

        // Get TreeFlow from Agent
        $agent = $talk->getAgents()->first();
        if (!$agent) {
            return null;
        }

        $treeFlow = $agent->getTreeFlow();
        if (!$treeFlow) {
            return null;
        }

        $slug = $treeFlow->getSlug();
        $currentStepSlug = $talkFlow[$slug]['currentStep'] ?? null;

        if (!$currentStepSlug) {
            return null;
        }

        return $talkFlow[$slug]['steps'][$currentStepSlug] ?? null;
    }

    /**
     * Get current step slug
     * TreeFlow is accessed via Agent: talk->agents->first()->treeFlow
     */
    public function getCurrentStepSlug(Talk $talk): ?string
    {
        $talkFlow = $talk->getTalkFlow();
        if (!$talkFlow) {
            return null;
        }

        // Get TreeFlow from Agent
        $agent = $talk->getAgents()->first();
        if (!$agent || !$agent->getTreeFlow()) {
            return null;
        }

        $slug = $agent->getTreeFlow()->getSlug();
        return $talkFlow[$slug]['currentStep'] ?? null;
    }

    /**
     * Record an action answer
     */
    public function recordActionAnswer(
        Talk $talk,
        string $stepSlug,
        string $actionSlug,
        string $answer
    ): void {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow();

        if (!$talkFlow || !$treeFlow) {
            throw new \RuntimeException('Talk does not have a talkFlow');
        }

        $slug = $treeFlow->getSlug();

        // Update the action answer
        $talkFlow[$slug]['steps'][$stepSlug]['actions'][$actionSlug] = $answer;

        $talk->setTalkFlow($talkFlow);
        $this->entityManager->flush();

        $this->logger->info('Action answer recorded', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'step_slug' => $stepSlug,
            'action_slug' => $actionSlug,
        ]);
    }

    /**
     * Check if current step is complete
     */
    public function isStepComplete(Talk $talk, string $stepSlug): bool
    {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow();

        if (!$talkFlow || !$treeFlow) {
            return false;
        }

        $slug = $treeFlow->getSlug();
        $step = $talkFlow[$slug]['steps'][$stepSlug] ?? null;

        if (!$step) {
            return false;
        }

        // Check if all actions have answers
        foreach ($step['actions'] as $actionSlug => $answer) {
            if (empty($answer)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark step as completed and move to next step
     */
    public function completeStep(
        Talk $talk,
        string $stepSlug,
        string $selectedOutputSlug,
        string $nextStepSlug
    ): void {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow();

        if (!$talkFlow || !$treeFlow) {
            throw new \RuntimeException('Talk does not have a talkFlow');
        }

        $slug = $treeFlow->getSlug();

        // Mark current step as completed
        $talkFlow[$slug]['steps'][$stepSlug]['completed'] = true;
        $talkFlow[$slug]['steps'][$stepSlug]['timestamp'] = (new \DateTimeImmutable())->format('c');
        $talkFlow[$slug]['steps'][$stepSlug]['selectedOutput'] = $selectedOutputSlug;

        // Move to next step
        $talkFlow[$slug]['currentStep'] = $nextStepSlug;

        $talk->setTalkFlow($talkFlow);
        $this->entityManager->flush();

        $this->logger->info('Step completed', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'completed_step' => $stepSlug,
            'next_step' => $nextStepSlug,
        ]);
    }

    /**
     * Get all answers collected so far
     */
    public function getAllAnswers(Talk $talk): array
    {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow();

        if (!$talkFlow || !$treeFlow) {
            return [];
        }

        $slug = $treeFlow->getSlug();
        $steps = $talkFlow[$slug]['steps'] ?? [];

        $allAnswers = [];
        foreach ($steps as $stepSlug => $stepData) {
            foreach ($stepData['actions'] ?? [] as $actionSlug => $answer) {
                if (!empty($answer)) {
                    $allAnswers["{$stepSlug}.{$actionSlug}"] = $answer;
                }
            }
        }

        return $allAnswers;
    }

    /**
     * Get next unanswered action in current step
     */
    public function getNextAction(Talk $talk): ?array
    {
        $currentStep = $this->getCurrentStep($talk);
        if (!$currentStep) {
            return null;
        }

        foreach ($currentStep['actions'] ?? [] as $actionSlug => $answer) {
            if (empty($answer)) {
                // Load the action details from TreeFlow
                $treeFlow = $talk->getTreeFlow();
                $jsonStructure = $treeFlow->getJsonStructure();
                $slug = $treeFlow->getSlug();
                $stepSlug = $this->getCurrentStepSlug($talk);

                $actionData = $jsonStructure[$slug]['steps'][$stepSlug]['actions'][$actionSlug] ?? null;

                if ($actionData) {
                    return [
                        'slug' => $actionSlug,
                        'prompt' => $actionData['prompt'],
                        'importance' => $actionData['importance'],
                        'fewShot' => $actionData['fewShot'] ?? [],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Get conversation progress (0-100%)
     */
    public function getProgress(Talk $talk): float
    {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow();

        if (!$talkFlow || !$treeFlow) {
            return 0.0;
        }

        $slug = $treeFlow->getSlug();
        $steps = $talkFlow[$slug]['steps'] ?? [];

        $totalSteps = count($steps);
        if ($totalSteps === 0) {
            return 0.0;
        }

        $completedSteps = 0;
        foreach ($steps as $step) {
            if ($step['completed'] ?? false) {
                $completedSteps++;
            }
        }

        return round(($completedSteps / $totalSteps) * 100, 2);
    }

    /**
     * Check if conversation is complete
     */
    public function isFlowComplete(Talk $talk): bool
    {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow();

        if (!$talkFlow || !$treeFlow) {
            return false;
        }

        $slug = $treeFlow->getSlug();
        $currentStepSlug = $talkFlow[$slug]['currentStep'] ?? null;

        if (!$currentStepSlug) {
            return true; // No current step = complete
        }

        // Check if current step has any outputs
        $jsonStructure = $treeFlow->getJsonStructure();
        $currentStepData = $jsonStructure[$slug]['steps'][$currentStepSlug] ?? null;

        if (!$currentStepData) {
            return true;
        }

        $outputs = $currentStepData['outputs'] ?? [];

        // If no outputs, this is the end
        return empty($outputs);
    }
}
```

### 3.2 Create TreeFlowExecutionService

**File:** `/home/user/inf/app/src/Service/TalkFlow/TreeFlowExecutionService.php`

```php
<?php

declare(strict_types=1);

namespace App\Service\TalkFlow;

use App\Entity\Talk;
use App\Entity\TreeFlow;
use App\Service\OpenAI\OpenAIService;
use Psr\Log\LoggerInterface;

class TreeFlowExecutionService
{
    public function __construct(
        private readonly OpenAIService $openAIService,
        private readonly TalkFlowService $talkFlowService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Evaluate output conditions and determine next step
     *
     * @return array ['outputSlug' => string, 'nextStepSlug' => string]
     */
    public function evaluateAndSelectNextStep(Talk $talk, string $latestMessage): array
    {
        $treeFlow = $talk->getTreeFlow();
        if (!$treeFlow) {
            throw new \RuntimeException('Talk does not have a TreeFlow');
        }

        $currentStepSlug = $this->talkFlowService->getCurrentStepSlug($talk);
        if (!$currentStepSlug) {
            throw new \RuntimeException('No current step in talkFlow');
        }

        // Get outputs from TreeFlow jsonStructure
        $jsonStructure = $treeFlow->getJsonStructure();
        $slug = $treeFlow->getSlug();
        $outputs = $jsonStructure[$slug]['steps'][$currentStepSlug]['outputs'] ?? [];

        if (empty($outputs)) {
            // No outputs = end of flow
            return [
                'outputSlug' => null,
                'nextStepSlug' => null,
            ];
        }

        // Get all collected answers
        $answers = $this->talkFlowService->getAllAnswers($talk);

        // Evaluate each output condition
        foreach ($outputs as $outputSlug => $outputData) {
            $condition = $outputData['condition'] ?? null;

            // If no condition, this is the default/fallback path
            if (empty($condition)) {
                $this->logger->info('Using default output (no condition)', [
                    'talk_id' => $talk->getId()->toRfc4122(),
                    'output_slug' => $outputSlug,
                ]);

                return [
                    'outputSlug' => $outputSlug,
                    'nextStepSlug' => $outputData['connectTo'] ?? null,
                ];
            }

            // Evaluate condition using AI
            $satisfied = $this->openAIService->evaluateCondition(
                $condition,
                $answers,
                $latestMessage
            );

            if ($satisfied) {
                $this->logger->info('Output condition satisfied', [
                    'talk_id' => $talk->getId()->toRfc4122(),
                    'output_slug' => $outputSlug,
                    'condition' => substr($condition, 0, 100),
                ]);

                return [
                    'outputSlug' => $outputSlug,
                    'nextStepSlug' => $outputData['connectTo'] ?? null,
                ];
            }
        }

        // No condition satisfied - take first output as default
        $firstOutput = array_key_first($outputs);
        $this->logger->warning('No conditions satisfied, using first output', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'output_slug' => $firstOutput,
        ]);

        return [
            'outputSlug' => $firstOutput,
            'nextStepSlug' => $outputs[$firstOutput]['connectTo'] ?? null,
        ];
    }

    /**
     * Get step details from TreeFlow
     */
    public function getStepDetails(TreeFlow $treeFlow, string $stepSlug): ?array
    {
        $jsonStructure = $treeFlow->getJsonStructure();
        $slug = $treeFlow->getSlug();

        return $jsonStructure[$slug]['steps'][$stepSlug] ?? null;
    }
}
```

---

## Phase 4: Async Message Processing

### 4.1 Install Symfony Messenger

```bash
docker-compose exec -T app composer require symfony/messenger
```

### 4.2 Configure Messenger

**File:** `/home/user/inf/app/config/packages/messenger.yaml`

```yaml
framework:
    messenger:
        failure_transport: failed

        transports:
            # Async processing via Doctrine
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: async_talk_messages
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
                    max_delay: 10000

            # Failed messages storage
            failed: 'doctrine://default?queue_name=failed'

        routing:
            'App\Message\ProcessTalkMessageCommand': async
            'App\Message\GenerateAgentResponseCommand': async
            'App\Message\EvaluateStepCompletionCommand': async
```

Add to `.env`:

```bash
###> symfony/messenger ###
MESSENGER_TRANSPORT_DSN=doctrine://default
###< symfony/messenger ###
```

### 4.3 Create Message Commands

**File:** `/home/user/inf/app/src/Message/ProcessTalkMessageCommand.php`

```php
<?php

declare(strict_types=1);

namespace App\Message;

class ProcessTalkMessageCommand
{
    public function __construct(
        private readonly string $talkMessageId
    ) {
    }

    public function getTalkMessageId(): string
    {
        return $this->talkMessageId;
    }
}
```

**File:** `/home/user/inf/app/src/Message/GenerateAgentResponseCommand.php`

```php
<?php

declare(strict_types=1);

namespace App\Message;

class GenerateAgentResponseCommand
{
    public function __construct(
        private readonly string $talkId,
        private readonly ?string $contextMessage = null
    ) {
    }

    public function getTalkId(): string
    {
        return $this->talkId;
    }

    public function getContextMessage(): ?string
    {
        return $this->contextMessage;
    }
}
```

**File:** `/home/user/inf/app/src/Message/EvaluateStepCompletionCommand.php`

```php
<?php

declare(strict_types=1);

namespace App\Message;

class EvaluateStepCompletionCommand
{
    public function __construct(
        private readonly string $talkId
    ) {
    }

    public function getTalkId(): string
    {
        return $this->talkId;
    }
}
```

### 4.4 Create Message Handlers

**File:** `/home/user/inf/app/src/MessageHandler/ProcessTalkMessageHandler.php`

```php
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
        $agent = $talk->getAgents()->first();
        if (!$agent || !$agent->getTreeFlow()) {
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

    private function escalateToHuman(Talk $talk, string $reason): void
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
```

**File:** `/home/user/inf/app/src/MessageHandler/GenerateAgentResponseHandler.php`

```php
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
        $talk = $this->talkRepository->find($command->getTalkId());

        if (!$talk) {
            $this->logger->error('Talk not found', [
                'talk_id' => $command->getTalkId(),
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

        // Check if flow is complete
        if ($this->talkFlowService->isFlowComplete($talk)) {
            $this->logger->info('Flow is complete, no more responses needed', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);

            // Mark talk as completed
            $talk->setStatus(2); // Assuming 2 = completed
            $talk->setClosedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            return;
        }

        try {
            $this->generateResponse($talk, $command->getContextMessage());
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate agent response', [
                'error' => $e->getMessage(),
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            throw $e;
        }
    }

    private function generateResponse(Talk $talk, ?string $contextMessage): void
    {
        // Get assigned agent or use first available agent
        $agent = $this->getAssignedAgent($talk);
        if (!$agent) {
            $this->logger->error('No agent available for talk', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            return;
        }

        // Get current step
        $currentStep = $this->talkFlowService->getCurrentStep($talk);
        if (!$currentStep) {
            $this->logger->warning('No current step for response generation', [
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            return;
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

        $this->entityManager->persist($responseMessage);

        // Update talk metadata
        $talk->setDateLastMessage(new \DateTimeImmutable());
        $talk->setMessageCount($talk->getMessageCount() + 1);

        $this->entityManager->flush();

        $this->logger->info('Agent response generated and sent', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'message_id' => $responseMessage->getId()->toRfc4122(),
            'agent_id' => $agent->getId()->toRfc4122(),
        ]);
    }

    private function getAssignedAgent(Talk $talk): ?Agent
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
```

**File:** `/home/user/inf/app/src/MessageHandler/EvaluateStepCompletionHandler.php`

```php
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
```

### 4.5 Event Subscriber for Auto-Processing

**File:** `/home/user/inf/app/src/EventSubscriber/TalkMessageSubscriber.php`

```php
<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\TalkMessage;
use App\Message\ProcessTalkMessageCommand;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
class TalkMessageSubscriber
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof TalkMessage) {
            return;
        }

        // Only process inbound messages
        if ($entity->getDirection() !== 'inbound') {
            return;
        }

        // Only process if Talk has Agent with TreeFlow
        $talk = $entity->getTalk();
        if (!$talk) {
            return;
        }

        $agent = $talk->getAgents()->first();
        if (!$agent || !$agent->getTreeFlow()) {
            return;
        }

        // Dispatch async processing
        $this->messageBus->dispatch(
            new ProcessTalkMessageCommand($entity->getId()->toRfc4122())
        );
    }
}
```

### 4.6 Start Messenger Worker

Add to `docker-compose.yml` as a new service:

```yaml
  messenger_worker:
    build:
      context: .
      dockerfile: docker/app/Dockerfile
    container_name: luminai_messenger_worker
    working_dir: /app
    command: php bin/console messenger:consume async --time-limit=3600 --memory-limit=256M
    volumes:
      - ./app:/app
    environment:
      - APP_ENV=dev
      - DATABASE_URL=postgresql://luminai:luminai@database:5432/luminai
      - REDIS_URL=redis://redis:6379
    depends_on:
      - database
      - redis
    restart: unless-stopped
    networks:
      - luminai_network
```

Or run manually:

```bash
docker-compose exec -T app php bin/console messenger:consume async -vv
```

---

## Phase 5: API Endpoints

### 5.1 Create TalkFlowController

**File:** `/home/user/inf/app/src/Controller/Api/TalkFlowController.php`

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Talk;
use App\Entity\TreeFlow;
use App\Repository\TalkRepository;
use App\Repository\TreeFlowRepository;
use App\Service\TalkFlow\TalkFlowService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/talks', name: 'api_talk_flow_')]
#[IsGranted('ROLE_USER')]
class TalkFlowController extends AbstractController
{
    public function __construct(
        private readonly TalkRepository $talkRepository,
        private readonly TreeFlowRepository $treeFlowRepository,
        private readonly TalkFlowService $talkFlowService
    ) {
    }

    /**
     * Initialize TalkFlow for a Talk from Agent's TreeFlow template
     * TreeFlow is accessed via Agent: talk->agents->first()->treeFlow
     */
    #[Route('/{id}/flow/initialize', name: 'initialize', methods: ['POST'])]
    public function initialize(Talk $talk): JsonResponse
    {
        // Check if Talk has an assigned Agent
        $agent = $talk->getAgents()->first();
        if (!$agent) {
            return new JsonResponse([
                'error' => 'Talk has no assigned Agent'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if Agent has a TreeFlow
        if (!$agent->getTreeFlow()) {
            return new JsonResponse([
                'error' => 'Agent has no TreeFlow assigned'
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->talkFlowService->initializeTalkFlow($talk);

        return new JsonResponse([
            'success' => true,
            'talkFlow' => $talk->getTalkFlow(),
            'agent' => [
                'id' => $agent->getId()->toRfc4122(),
                'name' => $agent->getName(),
            ],
            'treeFlow' => [
                'id' => $agent->getTreeFlow()->getId()->toRfc4122(),
                'name' => $agent->getTreeFlow()->getName(),
            ],
        ]);
    }

    /**
     * Get current TalkFlow state
     */
    #[Route('/{id}/flow', name: 'get_state', methods: ['GET'])]
    public function getState(Talk $talk): JsonResponse
    {
        if (!$talk->getTalkFlow()) {
            return new JsonResponse([
                'error' => 'Talk has no TalkFlow',
            ], Response::HTTP_NOT_FOUND);
        }

        $currentStep = $this->talkFlowService->getCurrentStep($talk);
        $nextAction = $this->talkFlowService->getNextAction($talk);
        $progress = $this->talkFlowService->getProgress($talk);
        $isComplete = $this->talkFlowService->isFlowComplete($talk);

        return new JsonResponse([
            'talkFlow' => $talk->getTalkFlow(),
            'currentStep' => $currentStep,
            'nextAction' => $nextAction,
            'progress' => $progress,
            'isComplete' => $isComplete,
            'paused' => $talk->isPaused(),
            'pausedAt' => $talk->getPausedAt()?->format('c'),
            'pausedReason' => $talk->getPausedReason(),
        ]);
    }

    /**
     * Get conversation progress
     */
    #[Route('/{id}/flow/progress', name: 'progress', methods: ['GET'])]
    public function getProgress(Talk $talk): JsonResponse
    {
        $progress = $this->talkFlowService->getProgress($talk);
        $isComplete = $this->talkFlowService->isFlowComplete($talk);

        return new JsonResponse([
            'progress' => $progress,
            'isComplete' => $isComplete,
            'currentStepSlug' => $this->talkFlowService->getCurrentStepSlug($talk),
        ]);
    }

    /**
     * Get all collected answers
     */
    #[Route('/{id}/flow/answers', name: 'answers', methods: ['GET'])]
    public function getAnswers(Talk $talk): JsonResponse
    {
        $answers = $this->talkFlowService->getAllAnswers($talk);

        return new JsonResponse([
            'answers' => $answers,
        ]);
    }
}
```

### 5.2 Update Talk API Platform Configuration

**File:** `/home/user/inf/app/config/api_platform/Talk.yaml`

Add talkFlow to read/write groups:

```yaml
properties:
    talkFlow:
        readable: true
        writable: true
        groups: ['talk:read', 'talk:write']

    treeFlow:
        readable: true
        writable: true
        groups: ['talk:read', 'talk:write']

    paused:
        readable: true
        writable: true
        groups: ['talk:read', 'talk:write']

    pausedAt:
        readable: true
        writable: false
        groups: ['talk:read']

    pausedReason:
        readable: true
        writable: true
        groups: ['talk:read', 'talk:write']

    resumedAt:
        readable: true
        writable: false
        groups: ['talk:read']
```

---

## Phase 6: Conversation Pause/Resume

### 6.1 Create TalkPauseController

**File:** `/home/user/inf/app/src/Controller/Api/TalkPauseController.php`

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Talk;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/talks', name: 'api_talk_pause_')]
#[IsGranted('ROLE_USER')]
class TalkPauseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Pause a conversation
     */
    #[Route('/{id}/pause', name: 'pause', methods: ['POST'])]
    public function pause(Talk $talk, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Paused by user';

        $talk->setPaused(true);
        $talk->setPausedAt(new \DateTimeImmutable());
        $talk->setPausedReason($reason);

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'paused' => true,
            'pausedAt' => $talk->getPausedAt()->format('c'),
            'reason' => $reason,
        ]);
    }

    /**
     * Resume a paused conversation
     */
    #[Route('/{id}/resume', name: 'resume', methods: ['POST'])]
    public function resume(Talk $talk): JsonResponse
    {
        if (!$talk->isPaused()) {
            return new JsonResponse([
                'error' => 'Talk is not paused',
            ], 400);
        }

        $talk->setPaused(false);
        $talk->setResumedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'paused' => false,
            'resumedAt' => $talk->getResumedAt()->format('c'),
        ]);
    }

    /**
     * Check if conversation is paused
     */
    #[Route('/{id}/pause/status', name: 'pause_status', methods: ['GET'])]
    public function getPauseStatus(Talk $talk): JsonResponse
    {
        return new JsonResponse([
            'paused' => $talk->isPaused(),
            'pausedAt' => $talk->getPausedAt()?->format('c'),
            'pausedReason' => $talk->getPausedReason(),
            'resumedAt' => $talk->getResumedAt()?->format('c'),
        ]);
    }
}
```

### 6.2 Update TalkInputDto

**File:** `/home/user/inf/app/src/Dto/TalkInputDto.php`

Extend the DTO to include new fields:

```php
<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Generated\TalkInputDtoGenerated;
use Symfony\Component\Serializer\Annotation\Groups;

class TalkInputDto extends TalkInputDtoGenerated
{
    #[Groups(['talk:write'])]
    public ?array $talkFlow = null;

    #[Groups(['talk:write'])]
    public ?string $treeFlow = null;

    #[Groups(['talk:write'])]
    public ?bool $paused = null;

    #[Groups(['talk:write'])]
    public ?string $pausedReason = null;
}
```

### 6.3 Update TalkOutputDto

**File:** `/home/user/inf/app/src/Dto/TalkOutputDto.php`

```php
<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Generated\TalkOutputDtoGenerated;
use Symfony\Component\Serializer\Annotation\Groups;

class TalkOutputDto extends TalkOutputDtoGenerated
{
    #[Groups(['talk:read'])]
    public ?array $talkFlow = null;

    #[Groups(['talk:read'])]
    public ?string $treeFlow = null;

    #[Groups(['talk:read'])]
    public ?bool $paused = null;

    #[Groups(['talk:read'])]
    public ?string $pausedAt = null;

    #[Groups(['talk:read'])]
    public ?string $pausedReason = null;

    #[Groups(['talk:read'])]
    public ?string $resumedAt = null;
}
```

---

## Phase 7: Testing & Monitoring

### 7.1 Create Test Fixtures

**File:** `/home/user/inf/app/tests/Fixtures/TreeFlowFixtures.php`

```php
<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Entity\Organization;
use App\Entity\Step;
use App\Entity\StepAction;
use App\Entity\StepOutput;
use App\Entity\StepConnection;
use App\Entity\TreeFlow;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TreeFlowFixtures extends Fixture
{
    public const TREE_FLOW_BANT = 'tree_flow_bant';

    public function load(ObjectManager $manager): void
    {
        // Assume organization already exists
        $organization = $manager->getRepository(Organization::class)->findOneBy([]);

        if (!$organization) {
            throw new \RuntimeException('No organization found');
        }

        // Create BANT Qualification TreeFlow
        $treeFlow = new TreeFlow();
        $treeFlow->setOrganization($organization);
        $treeFlow->setName('BANT Qualification');
        $treeFlow->setSlug('bant-qualification');
        $treeFlow->setActive(true);

        // Step 1: Budget
        $step1 = new Step();
        $step1->setTreeFlow($treeFlow);
        $step1->setName('Budget Discovery');
        $step1->setSlug('budget');
        $step1->setObjective('Understand the prospect\'s budget constraints');
        $step1->setFirst(true);
        $step1->setViewOrder(1);

        $action1 = new StepAction();
        $action1->setStep($step1);
        $action1->setName('Ask Budget Range');
        $action1->setSlug('ask-budget');
        $action1->setPrompt('What budget range have you allocated for this solution?');
        $action1->setImportance(1);
        $step1->addAction($action1);

        $output1 = new StepOutput();
        $output1->setStep($step1);
        $output1->setName('Has Budget');
        $output1->setSlug('has-budget');
        $output1->setCondition('Budget is mentioned and is above $10,000');

        $step1->addOutput($output1);

        // Step 2: Authority
        $step2 = new Step();
        $step2->setTreeFlow($treeFlow);
        $step2->setName('Authority Check');
        $step2->setSlug('authority');
        $step2->setObjective('Identify decision makers');
        $step2->setFirst(false);
        $step2->setViewOrder(2);

        $action2 = new StepAction();
        $action2->setStep($step2);
        $action2->setName('Ask Decision Maker');
        $action2->setSlug('ask-decision-maker');
        $action2->setPrompt('Who else will be involved in making this decision?');
        $action2->setImportance(1);
        $step2->addAction($action2);

        $output2 = new StepOutput();
        $output2->setStep($step2);
        $output2->setName('Is Decision Maker');
        $output2->setSlug('is-decision-maker');
        $output2->setCondition('Contact is the decision maker or has access to decision maker');

        $step2->addOutput($output2);

        // Step 3: Qualified
        $step3 = new Step();
        $step3->setTreeFlow($treeFlow);
        $step3->setName('Qualified');
        $step3->setSlug('qualified');
        $step3->setObjective('Lead is qualified, schedule demo');
        $step3->setFirst(false);
        $step3->setViewOrder(3);

        // Create connections
        $connection1 = new StepConnection();
        $connection1->setSourceOutput($output1);
        $connection1->setTargetStep($step2);

        $connection2 = new StepConnection();
        $connection2->setSourceOutput($output2);
        $connection2->setTargetStep($step3);

        $manager->persist($treeFlow);
        $manager->persist($step1);
        $manager->persist($step2);
        $manager->persist($step3);
        $manager->persist($action1);
        $manager->persist($action2);
        $manager->persist($output1);
        $manager->persist($output2);
        $manager->persist($connection1);
        $manager->persist($connection2);

        $manager->flush();

        // Generate JSON structure
        $jsonStructure = $treeFlow->convertToJson();
        $treeFlow->setJsonStructure($jsonStructure);

        $talkFlow = $treeFlow->convertToTalkFlow();
        $treeFlow->setTalkFlow($talkFlow);

        $manager->flush();

        $this->addReference(self::TREE_FLOW_BANT, $treeFlow);
    }
}
```

### 7.2 Create Monitoring Logger

**File:** `/home/user/inf/app/config/packages/monolog.yaml`

Add channel for talk flow logging:

```yaml
monolog:
    channels:
        - deprecation
        - talkflow

    handlers:
        talkflow:
            type: rotating_file
            path: '%kernel.logs_dir%/talkflow_%kernel.environment%.log'
            level: info
            channels: ['talkflow']
            max_files: 30
```

### 7.3 Add Metrics Endpoint

**File:** `/home/user/inf/app/src/Controller/Api/TalkFlowMetricsController.php`

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\TalkRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/talkflow/metrics', name: 'api_talkflow_metrics_')]
#[IsGranted('ROLE_ADMIN')]
class TalkFlowMetricsController extends AbstractController
{
    public function __construct(
        private readonly TalkRepository $talkRepository
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function getMetrics(): JsonResponse
    {
        $organization = $this->getUser()->getOrganization();

        // Total talks with TreeFlow
        $totalTalks = $this->talkRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.organization = :org')
            ->andWhere('t.treeFlow IS NOT NULL')
            ->setParameter('org', $organization)
            ->getQuery()
            ->getSingleScalarResult();

        // Paused talks
        $pausedTalks = $this->talkRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.organization = :org')
            ->andWhere('t.paused = true')
            ->setParameter('org', $organization)
            ->getQuery()
            ->getSingleScalarResult();

        // Completed talks (status = 2)
        $completedTalks = $this->talkRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.organization = :org')
            ->andWhere('t.status = 2')
            ->andWhere('t.treeFlow IS NOT NULL')
            ->setParameter('org', $organization)
            ->getQuery()
            ->getSingleScalarResult();

        return new JsonResponse([
            'totalTalks' => $totalTalks,
            'pausedTalks' => $pausedTalks,
            'completedTalks' => $completedTalks,
            'activeTalks' => $totalTalks - $pausedTalks - $completedTalks,
        ]);
    }
}
```

---

## Configuration & Environment

### Complete Environment Variables

Add to `/home/user/inf/app/.env`:

```bash
###> OpenAI Configuration ###
OPENAI_API_KEY=sk-proj-your-api-key-here
OPENAI_MODEL=gpt-4o
OPENAI_MAX_TOKENS=1000
OPENAI_TEMPERATURE=0.7
OPENAI_TIMEOUT=30
###< OpenAI Configuration ###

###> Symfony Messenger ###
MESSENGER_TRANSPORT_DSN=doctrine://default
###< Symfony Messenger ###

###> TalkFlow Configuration ###
TALKFLOW_AUTO_RESPOND=true
TALKFLOW_MAX_MESSAGE_HISTORY=10
TALKFLOW_ESCALATION_ENABLED=true
###< TalkFlow Configuration ###
```

---

## Usage Examples

### Example 1: Create Talk with Agent (who has TreeFlow)

```bash
# First, assign Agent with TreeFlow to the Talk
curl -X POST https://localhost/api/talks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "Qualification Call - Acme Corp",
    "contact": "/api/contacts/123",
    "talkType": "/api/talk_types/1",
    "owner": "/api/users/1",
    "agents": ["/api/agents/{agent_id}"]
  }'
```

**Important:** The Agent must have a TreeFlow assigned:
```bash
# Ensure Agent has TreeFlow (if not already set)
curl -X PATCH https://localhost/api/agents/{agent_id} \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/merge-patch+json" \
  -d '{
    "treeFlow": "/api/tree_flows/{tree_flow_id}"
  }'
```

### Example 2: Initialize TalkFlow

```bash
# No parameters needed - uses Agent's TreeFlow
curl -X POST https://localhost/api/talks/{id}/flow/initialize \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "talkFlow": { ... },
  "agent": {
    "id": "222e702d-6ccb-42b2-a0d7-5f61896b951c",
    "name": "SDR Agent"
  },
  "treeFlow": {
    "id": "fe1bb139-7088-48ce-bd4d-4ec2941eb23e",
    "name": "BANT Qualification"
  }
}
```

### Example 3: Send Lead Message (Triggers Auto-Response)

```bash
curl -X POST https://localhost/api/talk_messages \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "talk": "/api/talks/{id}",
    "body": "Our budget is around $50,000 annually",
    "direction": "inbound",
    "fromContact": "/api/contacts/123"
  }'
```

### Example 4: Check TalkFlow State

```bash
curl -X GET https://localhost/api/talks/{id}/flow \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "talkFlow": { ... },
  "currentStep": {
    "order": 1,
    "completed": false,
    "actions": { ... }
  },
  "nextAction": {
    "slug": "ask-budget",
    "prompt": "What budget range have you allocated?"
  },
  "progress": 33.33,
  "isComplete": false,
  "paused": false
}
```

### Example 5: Pause Conversation

```bash
curl -X POST https://localhost/api/talks/{id}/pause \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Lead requested to continue next week"
  }'
```

### Example 6: Resume Conversation

```bash
curl -X POST https://localhost/api/talks/{id}/resume \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Troubleshooting Guide

### Issue: Agent Not Responding

**Symptoms:** Lead sends message, but no agent response is created

**Checks:**
1. **Verify Agent has TreeFlow assigned:**
   ```bash
   docker-compose exec app php bin/console dbal:run-sql "
   SELECT a.id, a.name, t.name as tree_flow_name
   FROM agent a
   LEFT JOIN tree_flow t ON a.tree_flow_id = t.id
   WHERE a.id IN (SELECT agent_id FROM talk_agents WHERE talk_id = 'YOUR_TALK_ID');"
   ```

   If `tree_flow_name` is NULL, assign a TreeFlow to the Agent.

2. Verify messenger worker is running:
   ```bash
   docker-compose logs messenger_worker
   ```

3. Check failed messages:
   ```bash
   docker-compose exec app php bin/console messenger:failed:show
   ```

4. Verify OpenAI API key:
   ```bash
   docker-compose exec app php bin/console debug:container --env-vars | grep OPENAI
   ```

5. Check logs:
   ```bash
   tail -f app/var/log/talkflow_dev.log
   ```

**Solution:**
```bash
# Restart messenger worker
docker-compose restart messenger_worker

# Retry failed messages
docker-compose exec app php bin/console messenger:failed:retry
```

---

### Issue: Answers Not Being Extracted

**Symptoms:** Lead responses not recorded in talkFlow

**Checks:**
1. Check OpenAI extraction logs
2. Verify current step has actions defined
3. Test extraction manually:
   ```php
   $result = $openAIService->extractAnswers($message, $currentStep);
   dump($result);
   ```

**Solution:**
- Ensure action prompts are clear and specific
- Lower temperature for extraction (already set to 0.3)
- Add few-shot examples to actions

---

### Issue: Wrong Step Transition

**Symptoms:** Flow moves to wrong step

**Checks:**
1. Verify output conditions in TreeFlow JSON
2. Check condition evaluation logs
3. Test condition manually:
   ```php
   $satisfied = $openAIService->evaluateCondition($condition, $answers, $message);
   ```

**Solution:**
- Make conditions more explicit
- Use simpler conditional logic
- Add default/fallback outputs

---

### Issue: Conversation Stuck

**Symptoms:** No progress after message

**Checks:**
1. Check if Talk is paused
2. Verify step actions are answerable
3. Check message queue:
   ```bash
   docker-compose exec app php bin/console messenger:stats
   ```

**Solution:**
```bash
# Manually trigger evaluation
curl -X POST https://localhost/api/talks/{id}/flow/evaluate
```

---

## Next Steps

### Critical: Fix Code to Use Correct Relationship

⚠️ **The current implementation code has bugs** - it's trying to call `$talk->getTreeFlow()` which doesn't exist. All code must be updated to use the correct relationship path:

```php
// Current code (WRONG - needs fixing):
$treeFlow = $talk->getTreeFlow();

// Corrected code (RIGHT):
$agent = $talk->getAgents()->first();
$treeFlow = $agent ? $agent->getTreeFlow() : null;
```

**Files that need updating:**
1. `/app/src/Service/TalkFlow/TalkFlowService.php` - All methods
2. `/app/src/Service/TalkFlow/TreeFlowExecutionService.php` - evaluateAndSelectNextStep()
3. `/app/src/MessageHandler/ProcessTalkMessageHandler.php` - processMessage()
4. `/app/src/EventSubscriber/TalkMessageSubscriber.php` - postPersist()
5. `/app/src/Controller/Api/TalkFlowController.php` - initialize(), getState()

### Immediate (After Fixing Code)

1. **Load Fixtures:**
   ```bash
   docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction
   ```

2. **Test with Sample Data:**
   - Create test Agent with TreeFlow assigned
   - Create test Contact
   - Create test Talk with Agent assigned
   - Initialize TalkFlow (will use Agent's TreeFlow)
   - Send test messages

3. **Monitor Logs:**
   ```bash
   tail -f app/var/log/talkflow_dev.log
   ```

### Future Enhancements

1. **Analytics Dashboard**
   - Conversion rates by TreeFlow
   - Average completion time
   - Drop-off points analysis

2. **Advanced AI Features**
   - Sentiment analysis
   - Objection handling
   - Personality matching

3. **Multi-language Support**
   - Detect lead language
   - Respond in same language
   - Store translations

4. **Integration**
   - Email sync (Gmail, Outlook)
   - SMS/WhatsApp
   - Calendar scheduling

---

## Success Metrics

Track these KPIs:

| Metric | Target | How to Measure |
|--------|--------|----------------|
| Response Time | < 30s | Time between inbound message and agent response |
| Extraction Accuracy | > 90% | Manual review of extracted answers |
| Condition Accuracy | > 90% | Manual review of step transitions |
| Completion Rate | > 70% | Talks reaching final step |
| Escalation Rate | < 10% | Talks paused for human intervention |
| Cost per Conversation | < $0.50 | OpenAI API costs / total conversations |

---

## Appendix: Database Schema Changes

### New Columns Added to `talk` Table

```sql
-- Talk does NOT have tree_flow_id (that's on Agent)
ALTER TABLE talk ADD COLUMN talk_flow JSON;
ALTER TABLE talk ADD COLUMN paused BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE talk ADD COLUMN paused_at TIMESTAMP WITHOUT TIME ZONE;
ALTER TABLE talk ADD COLUMN paused_reason TEXT;
ALTER TABLE talk ADD COLUMN resumed_at TIMESTAMP WITHOUT TIME ZONE;

CREATE INDEX idx_talk_paused ON talk(paused);
```

### Existing Relationship in `agent` Table

```sql
-- Agent already has TreeFlow relationship (no changes needed)
-- Column: tree_flow_id UUID REFERENCES tree_flow(id)
```

### Relationship Chain

```
Talk (many-to-many) ──→ Agent (many-to-one) ──→ TreeFlow
     talk_agents table        tree_flow_id column
```

---

## Appendix: Required Packages

```bash
composer require openai-php/client
composer require symfony/messenger
```

---

**End of Implementation Plan**

This document provides a complete, production-ready implementation plan for the TreeFlow SDR Agent feature. Follow the phases sequentially, test thoroughly at each stage, and monitor performance metrics continuously.
