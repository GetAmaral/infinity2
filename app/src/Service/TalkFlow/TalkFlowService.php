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
        $talkFlow = $talk->getTalkFlow();
        if (!$talkFlow) {
            return null;
        }

        $treeFlow = $talk->getTreeFlow(); // Uses Talk->Agent->TreeFlow
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
        string $selectedOutputSlug,
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
}
