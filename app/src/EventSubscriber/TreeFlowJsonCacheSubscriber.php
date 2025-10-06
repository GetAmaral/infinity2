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
            // Regenerate JSON for all affected TreeFlows
            foreach ($this->affectedTreeFlows as $treeFlow) {
                // Refresh the entity to get the latest state with all relations
                $entityManager->refresh($treeFlow);

                // Generate fresh JSON structure
                $jsonStructure = $treeFlow->convertToJson();

                // Generate fresh TalkFlow template
                $talkFlow = $treeFlow->convertToTalkFlow();

                // Update both cached structures
                $treeFlow->setJsonStructure($jsonStructure);
                $treeFlow->setTalkFlow($talkFlow);
            }

            // Clear the affected list before flush to prevent re-triggering
            $this->affectedTreeFlows = [];

            // Persist the JSON updates
            $entityManager->flush();
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

        // Add to affected list (using UUID as key to avoid duplicates)
        if ($treeFlow !== null) {
            $uuid = $treeFlow->getId()->toRfc4122();
            $this->affectedTreeFlows[$uuid] = $treeFlow;
        }
    }
}
