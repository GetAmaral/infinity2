<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Step;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * FirstStepSubscriber - Ensures only one Step per TreeFlow has first=true
 *
 * When a Step is set to first=true, this subscriber automatically:
 * 1. Sets all other Steps in the same TreeFlow to first=false
 * 2. Ensures data integrity for the TreeFlow workflow
 *
 * This maintains the constraint that each TreeFlow has exactly one entry point.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class FirstStepSubscriber
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Step) {
            return;
        }

        if ($entity->isFirst()) {
            $this->unsetOtherFirstSteps($entity, $args);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Step) {
            return;
        }

        // Check if 'first' field was changed to true
        if ($args->hasChangedField('first') && $entity->isFirst()) {
            $this->unsetOtherFirstSteps($entity, $args);
        }
    }

    /**
     * Set all other steps in the same TreeFlow to first=false
     */
    private function unsetOtherFirstSteps(Step $currentStep, LifecycleEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $treeFlow = $currentStep->getTreeFlow();

        if (!$treeFlow) {
            return;
        }

        // Find all other steps in this TreeFlow that are marked as first
        $repository = $entityManager->getRepository(Step::class);
        $otherFirstSteps = $repository->createQueryBuilder('s')
            ->where('s.treeFlow = :treeFlow')
            ->andWhere('s.first = :first')
            ->andWhere('s.id != :currentId')
            ->setParameter('treeFlow', $treeFlow)
            ->setParameter('first', true)
            ->setParameter('currentId', $currentStep->getId())
            ->getQuery()
            ->getResult();

        // Set them all to first=false
        foreach ($otherFirstSteps as $step) {
            $step->setFirst(false);
            $entityManager->persist($step);
        }
    }
}
