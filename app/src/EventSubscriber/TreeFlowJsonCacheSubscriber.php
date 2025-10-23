<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\TreeFlow;
use App\Entity\Step;
use App\Entity\StepQuestion;
use App\Entity\StepInput;
use App\Entity\StepOutput;
use App\Entity\StepConnection;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * TreeFlowJsonCacheSubscriber
 *
 * Automatically regenerates the cached JSON structure for TreeFlows when:
 * - TreeFlow itself is updated
 * - Any Step is added/updated/removed
 * - Any StepQuestion is added/updated/removed
 * - Any StepInput is added/updated/removed
 * - Any StepOutput is added/updated/removed
 * - Any StepConnection is added/updated/removed
 *
 * Uses postFlush to regenerate after all changes are committed.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
class TreeFlowJsonCacheSubscriber
{
    /**
     * @var array<string, TreeFlow> TreeFlows that need JSON regeneration (indexed by UUID)
     */
    private array $affectedTreeFlows = [];

    /**
     * @var bool Flag to prevent infinite loops during JSON regeneration
     */
    private bool $isRegenerating = false;

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->trackAffectedTreeFlow($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        // Skip if this is just a jsonStructure or talkFlow update (to prevent infinite loops)
        if ($entity instanceof TreeFlow) {
            $changeSet = $args->getEntityChangeSet();
            $changedFields = array_keys($changeSet);
            $cacheFields = ['jsonStructure', 'talkFlow'];

            // Skip if only cache fields changed
            $onlyCacheFieldsChanged = !empty(array_intersect($changedFields, $cacheFields))
                && empty(array_diff($changedFields, $cacheFields));

            if ($onlyCacheFieldsChanged) {
                return;
            }

            // Skip if only canvasViewState changed (canvas changes don't require JSON regeneration)
            if ($args->hasChangedField('canvasViewState') && count($changeSet) === 1) {
                return;
            }
        }

        $this->trackAffectedTreeFlow($entity);
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->trackAffectedTreeFlow($args->getObject());
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        // Skip if we're already regenerating (prevent infinite loops)
        if ($this->isRegenerating || empty($this->affectedTreeFlows)) {
            return;
        }

        $this->isRegenerating = true;
        $entityManager = $args->getObjectManager();

        try {
            error_log("[TreeFlowJsonCacheSubscriber] postFlush - Regenerating JSON for " . count($this->affectedTreeFlows) . " TreeFlows");

            // Regenerate JSON for all affected TreeFlows
            foreach ($this->affectedTreeFlows as $treeFlow) {
                error_log("[TreeFlowJsonCacheSubscriber] BEFORE refresh - TreeFlow: {$treeFlow->getId()}");

                // Skip if entity has been removed (deleted)
                if (!$entityManager->contains($treeFlow)) {
                    error_log("[TreeFlowJsonCacheSubscriber] Entity is not managed (likely deleted), skipping");
                    continue;
                }

                // Refresh the entity to get the latest state with all relations
                $entityManager->refresh($treeFlow);

                error_log("[TreeFlowJsonCacheSubscriber] AFTER refresh, BEFORE convertToJson");

                // Generate fresh JSON structure
                $jsonStructure = $treeFlow->convertToJson();

                // Generate fresh TalkFlow template
                $talkFlow = $treeFlow->convertToTalkFlow();

                // Update both cached structures
                $treeFlow->setJsonStructure($jsonStructure);
                $treeFlow->setTalkFlow($talkFlow);

                error_log("[TreeFlowJsonCacheSubscriber] JSON generated, BEFORE second flush");
            }

            // Clear the affected list before flush to prevent re-triggering
            $this->affectedTreeFlows = [];

            error_log("[TreeFlowJsonCacheSubscriber] About to call second flush()");

            // Persist the JSON updates
            $entityManager->flush();

            error_log("[TreeFlowJsonCacheSubscriber] Second flush() completed");
        } finally {
            $this->isRegenerating = false;
        }
    }

    /**
     * Track TreeFlow that needs JSON regeneration
     */
    private function trackAffectedTreeFlow(object $entity): void
    {
        // Skip if we're already regenerating
        if ($this->isRegenerating) {
            return;
        }

        $treeFlow = null;

        try {
            // Determine which TreeFlow is affected
            if ($entity instanceof TreeFlow) {
                $treeFlow = $entity;
            } elseif ($entity instanceof Step) {
                $treeFlow = $entity->getTreeFlow();
            } elseif ($entity instanceof StepQuestion) {
                $treeFlow = $entity->getStep()->getTreeFlow();
            } elseif ($entity instanceof StepInput) {
                $treeFlow = $entity->getStep()->getTreeFlow();
            } elseif ($entity instanceof StepOutput) {
                $treeFlow = $entity->getStep()->getTreeFlow();
            } elseif ($entity instanceof StepConnection) {
                // Connection affects the source output's TreeFlow
                $treeFlow = $entity->getSourceOutput()->getStep()->getTreeFlow();
            }
        } catch (\Error $e) {
            // Property not initialized yet (happens during API POST before relationships are set)
            // This is normal - the JSON will be regenerated on postFlush after the entity is fully persisted
            return;
        }

        // Add to affected list (using UUID as key to avoid duplicates)
        if ($treeFlow !== null) {
            // Check if ID exists (during prePersist, ID is null)
            $id = $treeFlow->getId();
            if ($id !== null) {
                $uuid = $id->toRfc4122();
                $this->affectedTreeFlows[$uuid] = $treeFlow;
            } else {
                // Use object hash for entities without ID yet (during prePersist)
                $this->affectedTreeFlows[spl_object_hash($treeFlow)] = $treeFlow;
            }
        }
    }
}
