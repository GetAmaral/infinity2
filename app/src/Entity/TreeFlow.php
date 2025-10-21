<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TreeFlowGenerated;
use App\Repository\TreeFlowRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Cache;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * TreeFlow - AI Agent Guidance System
 *
 * A TreeFlow represents a complete workflow for AI agent guidance,
 * containing steps with questions, few-shot examples, and conditional routing.
 */
#[ORM\Entity(repositoryClass: TreeFlowRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Cache(usage: 'NONSTRICT_READ_WRITE', region: 'treeflow_region')]
#[ApiResource(
    routePrefix: '/treeflows',
    normalizationContext: ['groups' => ['treeflow:read']],
    denormalizationContext: ['groups' => ['treeflow:write']],
    operations: [
        new Get(
            uriTemplate: '/{id}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['treeflow:read', 'step:read', 'question:read', 'fewshot:read']]
        ),
        new GetCollection(
            uriTemplate: '',
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            uriTemplate: '',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            uriTemplate: '/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            uriTemplate: '/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
        // Admin endpoint with audit information
        new GetCollection(
            uriTemplate: '/admin/treeflows',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['treeflow:read', 'audit:read']]
        )
    ]
)]
class TreeFlow extends TreeFlowGenerated
{
    public function __construct()
    {
        parent::__construct();
        $this->version = 1; // Start at version 1
    }

    #[ORM\PreUpdate]
    public function incrementVersion(\Doctrine\ORM\Event\PreUpdateEventArgs $event): void
    {
        // Get the changed fields
        $changeSet = $event->getEntityChangeSet();

        // Skip version increment if only canvasViewState changed
        // (AuditSubscriber already prevents updatedAt/updatedBy from being set for canvas-only changes)
        $nonVersionableFields = ['canvasViewState'];

        $meaningfulChanges = array_diff(array_keys($changeSet), $nonVersionableFields);

        // Only increment version if there are meaningful changes
        if (!empty($meaningfulChanges)) {
            $this->version++;
        }
    }

    /**
     * Get the first step in this TreeFlow
     */
    public function getFirstStep(): ?Step
    {
        foreach ($this->steps as $step) {
            if ($step->isFirst()) {
                return $step;
            }
        }
        return null;
    }

    /**
     * Convert the entire TreeFlow structure to JSON array
     *
     * @return array TreeFlow structure with steps, questions, inputs, outputs, and connections
     */
    public function convertToJson(): array
    {
        // Get ordered steps following canvas flow (first step, then connections)
        $orderedSteps = $this->getOrderedSteps();

        $steps = [];
        $order = 1;

        foreach ($orderedSteps as $step) {
            // Build questions array
            $questions = [];
            foreach ($step->getQuestions() as $question) {
                $questions[$question->getSlug()] = [
                    'objective' => $question->getObjective(),
                    'prompt' => $question->getPrompt(),
                    'importance' => $question->getImportance(),
                    'fewShotPositive' => $question->getFewShotPositive() ?? [],
                    'fewShotNegative' => $question->getFewShotNegative() ?? [],
                ];
            }

            // Build inputs array
            $inputs = [];
            foreach ($step->getInputs() as $input) {
                $inputs[$input->getSlug() ?? 'input-' . $input->getId()] = [
                    'type' => $input->getType()->value,
                    'prompt' => $input->getPrompt(),
                ];
            }

            // Build outputs array
            $outputs = [];
            foreach ($step->getOutputs() as $output) {
                $outputData = [
                    'prompt' => $output->getDescription(),
                    'conditional' => $output->getConditional(),
                ];

                // Check if output has a connection
                if ($output->hasConnection()) {
                    $connection = $output->getConnection();
                    $targetInput = $connection->getTargetInput();
                    $targetStep = $targetInput->getStep();

                    $outputData['connectTo'] = [
                        'stepSlug' => $targetStep->getSlug(),
                        'inputSlug' => $targetInput->getSlug() ?? 'input-' . $targetInput->getId(),
                    ];
                }

                $outputs[$output->getSlug() ?? 'output-' . $output->getId()] = $outputData;
            }

            // Build step structure
            $steps[$step->getSlug()] = [
                'order' => $order,
                'objective' => $step->getObjective(),
                'prompt' => $step->getPrompt(),
                'questions' => $questions,
                'inputs' => $inputs,
                'outputs' => $outputs,
            ];

            $order++;
        }

        return [
            $this->slug => [
                'steps' => $steps,
            ]
        ];
    }

    /**
     * Convert TreeFlow to TalkFlow template structure
     *
     * Creates an empty template with step metadata and empty fields for:
     * - Question answers (to be filled by Talk processor)
     * - Output selections (which path was taken)
     * - Step completion status and timestamps
     *
     * @return array TalkFlow template structure
     */
    public function convertToTalkFlow(): array
    {
        // Get ordered steps following canvas flow (first step, then connections)
        $orderedSteps = $this->getOrderedSteps();

        $steps = [];
        $order = 1;

        foreach ($orderedSteps as $step) {
            // Build questions array with empty answer fields
            $questions = [];
            foreach ($step->getQuestions() as $question) {
                $questions[$question->getSlug()] = ''; // Empty - to be filled by Talk processor
            }

            // Build outputs array with empty selection fields
            $outputs = [];
            foreach ($step->getOutputs() as $output) {
                $outputs[$output->getSlug() ?? 'output-' . $output->getId()] = ''; // Empty - to be filled with conditional result
            }

            // Build step template structure
            $steps[$step->getSlug()] = [
                'order' => $order,
                'completed' => false,
                'timestamp' => null,
                'selectedOutput' => null, // Will store which output was actually taken
                'questions' => $questions,
                'outputs' => $outputs,
            ];

            $order++;
        }

        // Get first step slug for currentStep initialization
        $firstStep = $this->getFirstStep();
        $currentStepSlug = $firstStep ? $firstStep->getSlug() : null;

        return [
            $this->slug => [
                'currentStep' => $currentStepSlug,
                'steps' => $steps,
            ]
        ];
    }

    /**
     * Get steps ordered by canvas flow (first step, then following connections)
     *
     * @return array<Step> Ordered array of steps
     */
    private function getOrderedSteps(): array
    {
        $orderedSteps = [];
        $visitedSteps = [];

        // Start with the first step
        $currentStep = $this->getFirstStep();

        if (!$currentStep) {
            // No first step defined, return all steps in default order
            return $this->steps->toArray();
        }

        // BFS traversal following connections
        $queue = [$currentStep];

        while (!empty($queue)) {
            $step = array_shift($queue);
            $stepId = $step->getId()->toRfc4122();

            // Skip if already visited
            if (isset($visitedSteps[$stepId])) {
                continue;
            }

            // Mark as visited and add to ordered list
            $visitedSteps[$stepId] = true;
            $orderedSteps[] = $step;

            // Find all connected steps through outputs
            foreach ($step->getOutputs() as $output) {
                if ($output->hasConnection()) {
                    $connection = $output->getConnection();
                    $targetInput = $connection->getTargetInput();
                    $targetStep = $targetInput->getStep();
                    $targetStepId = $targetStep->getId()->toRfc4122();

                    // Add to queue if not yet visited
                    if (!isset($visitedSteps[$targetStepId])) {
                        $queue[] = $targetStep;
                    }
                }
            }
        }

        // Add any unconnected steps at the end
        foreach ($this->steps as $step) {
            $stepId = $step->getId()->toRfc4122();
            if (!isset($visitedSteps[$stepId])) {
                $orderedSteps[] = $step;
            }
        }

        return $orderedSteps;
    }

    public function __toString(): string
    {
        return $this->name . ' v' . $this->version;
    }
}
