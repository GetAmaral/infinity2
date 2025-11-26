<?php

declare(strict_types=1);

namespace App\Service\TalkFlow;

use App\Entity\Talk;
use App\Entity\TreeFlow;
use App\Repository\StepOutputRepository;
use App\Service\Pipeline\DealMovementService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TalkFlowService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly StepOutputRepository $stepOutputRepository,
        private readonly DealMovementService $dealMovementService
    ) {
    }

    /**
     * Initialize talkFlow from TreeFlow template
     * TreeFlow is accessed via Agent: talk->getAgents()->first()->getTreeFlow()
     */
    public function initializeTalkFlow(Talk $talk): void
    {
        // Get TreeFlow from assigned Agent
        $treeFlow = $talk->getTreeFlow();

        if (!$treeFlow) {
            throw new \RuntimeException('Talk has no assigned Agent with TreeFlow');
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
            'tree_flow_id' => $treeFlow->getId()->toRfc4122(),
        ]);
    }

    /**
     * Get current step data from talkFlow
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
     */
    public function getCurrentStep(Talk $talk): ?array
    {
        // DEBUG: Log at entry
        $this->logger->info('getCurrentStep called', [
            'talk_id' => $talk->getId()->toRfc4122(),
        ]);

        $talkFlow = $talk->getTalkFlow();

        // DEBUG: Log talkFlow
        $this->logger->info('TalkFlow retrieved', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'talkFlow_is_null' => $talkFlow === null,
            'talkFlow_type' => gettype($talkFlow),
            'talkFlow_value' => is_array($talkFlow) ? 'array[' . implode(',', array_keys($talkFlow)) . ']' : (is_string($talkFlow) ? substr($talkFlow, 0, 100) : gettype($talkFlow)),
        ]);

        if (!$talkFlow) {
            $this->logger->info('TalkFlow is null, returning null');
            return null;
        }

        $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow
        if (!$treeFlow) {
            $this->logger->info('TreeFlow is null, returning null');
            return null;
        }

        $slug = $treeFlow->getSlug();

        // DEBUG: Log the slug and structure
        $this->logger->info('Slug and structure check', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'slug' => $slug,
            'slug_type' => gettype($slug),
            'slug_is_string' => is_string($slug),
            'talkFlow_has_slug_key' => is_array($talkFlow) && isset($talkFlow[$slug]),
        ]);

        if (!is_string($slug)) {
            $this->logger->error('Slug is not a string!', [
                'slug_type' => gettype($slug),
                'slug_value' => $slug,
            ]);
            return null;
        }

        if (!is_array($talkFlow)) {
            $this->logger->error('TalkFlow is not an array!', [
                'talkFlow_type' => gettype($talkFlow),
            ]);
            return null;
        }

        $currentStepSlug = $talkFlow[$slug]['currentStep'] ?? null;

        if (!$currentStepSlug) {
            return null;
        }

        return $talkFlow[$slug]['steps'][$currentStepSlug] ?? null;
    }

    /**
     * Get current step slug
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
     */
    public function getCurrentStepSlug(Talk $talk): ?string
    {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow

        if (!$talkFlow || !$treeFlow) {
            return null;
        }

        $slug = $treeFlow->getSlug();
        return $talkFlow[$slug]['currentStep'] ?? null;
    }

    /**
     * Record an action answer
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
     */
    public function recordActionAnswer(
        Talk $talk,
        string $stepSlug,
        string $actionSlug,
        string $answer
    ): void {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow

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
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
     */
    public function isStepComplete(Talk $talk, string $stepSlug): bool
    {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow

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
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
     */
    public function completeStep(
        Talk $talk,
        string $stepSlug,
        ?string $selectedOutputSlug,
        ?string $nextStepSlug
    ): void {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow

        if (!$talkFlow || !$treeFlow) {
            throw new \RuntimeException('Talk does not have a talkFlow');
        }

        $slug = $treeFlow->getSlug();

        // Mark current step as completed
        $talkFlow[$slug]['steps'][$stepSlug]['completed'] = true;
        $talkFlow[$slug]['steps'][$stepSlug]['timestamp'] = (new \DateTimeImmutable())->format('c');

        // Only set selectedOutput if there was an output
        if ($selectedOutputSlug !== null) {
            $talkFlow[$slug]['steps'][$stepSlug]['selectedOutput'] = $selectedOutputSlug;

            // Handle pipeline stage movement if output has one
            $this->handlePipelineStageMovement($talk, $selectedOutputSlug);
        }

        // Move to next step (null means flow is complete)
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
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
     */
    public function getAllAnswers(Talk $talk): array
    {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow

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
     * Get action definitions for current step
     * Returns action definitions from TreeFlow's jsonStructure (not runtime answers)
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
     */
    public function getCurrentStepActionDefinitions(Talk $talk): array
    {
        $treeFlow = $talk->getTreeFlow();
        if (!$treeFlow) {
            return [];
        }

        $stepSlug = $this->getCurrentStepSlug($talk);
        if (!$stepSlug) {
            return [];
        }

        $slug = $treeFlow->getSlug();
        $jsonStructure = $treeFlow->getJsonStructure();

        return $jsonStructure[$slug]['steps'][$stepSlug]['actions'] ?? [];
    }

    /**
     * Get next unanswered action in current step
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
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
                $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow
                if (!$treeFlow) {
                    return null;
                }

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
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
     */
    public function getProgress(Talk $talk): float
    {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow

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
     * TreeFlow is accessed via Agent: talk->getTreeFlow()
     */
    public function isFlowComplete(Talk $talk): bool
    {
        $talkFlow = $talk->getTalkFlow();
        $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow

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

    /**
     * Handle automatic deal movement when output with pipeline stage is selected
     */
    private function handlePipelineStageMovement(Talk $talk, string $outputSlug): void
    {
        // Check if talk has an associated deal
        $deal = $talk->getDeal();
        if (!$deal) {
            return;
        }

        // Find the StepOutput by slug
        $stepOutput = $this->stepOutputRepository->findOneBy(['slug' => $outputSlug]);
        if (!$stepOutput) {
            $this->logger->warning('StepOutput not found by slug', [
                'output_slug' => $outputSlug,
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            return;
        }

        // Check if output has a pipeline stage
        $pipelineStage = $stepOutput->getPipelineStage();
        if (!$pipelineStage) {
            return;
        }

        try {
            // Move deal to the pipeline stage
            $this->dealMovementService->moveDealToStage($deal, $pipelineStage);

            $this->logger->info('Deal automatically moved to pipeline stage via TalkFlow output', [
                'talk_id' => $talk->getId()->toRfc4122(),
                'deal_id' => $deal->getId()->toRfc4122(),
                'output_slug' => $outputSlug,
                'pipeline_stage' => $pipelineStage->getName(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to move deal to pipeline stage', [
                'talk_id' => $talk->getId()->toRfc4122(),
                'deal_id' => $deal->getId()->toRfc4122(),
                'output_slug' => $outputSlug,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
