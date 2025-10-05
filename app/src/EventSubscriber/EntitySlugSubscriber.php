<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\StepFewShot;
use App\Entity\StepQuestion;
use App\Entity\Step;
use App\Entity\TreeFlow;
use App\Service\Utils;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * EntitySlugSubscriber - Auto-generate slugs from name field
 *
 * Listens to prePersist and preUpdate events for entities with slug fields
 * and automatically generates URL-friendly slugs from the name field.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class EntitySlugSubscriber
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->updateSlug($entity);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        // Only update slug if name has changed
        if ($args->hasChangedField('name')) {
            $this->updateSlug($entity);
        }
    }

    private function updateSlug(object $entity): void
    {
        // Check if entity has slug and name methods
        if (!method_exists($entity, 'setSlug') || !method_exists($entity, 'getName')) {
            return;
        }

        // Only update supported entities
        if (!$entity instanceof TreeFlow
            && !$entity instanceof Step
            && !$entity instanceof StepQuestion
            && !$entity instanceof StepFewShot) {
            return;
        }

        // Get the name and generate slug
        $name = $entity->getName();
        if (empty($name)) {
            return;
        }

        $slug = Utils::stringToSlug($name);
        $entity->setSlug($slug);
    }
}
