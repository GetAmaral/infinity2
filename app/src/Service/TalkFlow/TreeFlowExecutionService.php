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
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
     *
     * @return array ['outputSlug' => string|null, 'nextStepSlug' => string|null]
     */
    public function evaluateAndSelectNextStep(Talk $talk, string $latestMessage): array
    {
        $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow
        if (!$treeFlow) {
            throw new \RuntimeException('Talk does not have a TreeFlow (no Agent assigned or Agent has no TreeFlow)');
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
