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

        // If TreeFlow doesn't have an ID yet (being created), no other steps can exist yet
        if (!$treeFlow->getId()) {
            return;
        }

        // If current step doesn't have an ID yet (being created), we can't use it in WHERE clause
        // So we need to find all first steps and check them in PHP
        $repository = $entityManager->getRepository(Step::class);

        if ($currentStep->getId()) {
            // Current step has ID - use query with exclusion
            $otherFirstSteps = $repository->createQueryBuilder('s')
                ->where('s.treeFlow = :treeFlow')
                ->andWhere('s.first = :first')
                ->andWhere('s.id != :currentId')
                ->setParameter('treeFlow', $treeFlow)
                ->setParameter('first', true)
                ->setParameter('currentId', $currentStep->getId())
                ->getQuery()
                ->getResult();
        } else {
            // Current step has no ID yet - get all first steps and filter in PHP
            $allFirstSteps = $repository->createQueryBuilder('s')
                ->where('s.treeFlow = :treeFlow')
                ->andWhere('s.first = :first')
                ->setParameter('treeFlow', $treeFlow)
                ->setParameter('first', true)
                ->getQuery()
                ->getResult();

            // Filter out current step (by object identity)
            $otherFirstSteps = array_filter($allFirstSteps, function($step) use ($currentStep) {
                return $step !== $currentStep;
            });
        }

        // Set them all to first=false
        foreach ($otherFirstSteps as $step) {
            $step->setFirst(false);
            $entityManager->persist($step);
        }
    }
}
