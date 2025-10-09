<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\EventCategoryGenerated;
use App\Repository\EventCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * EventCategory Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: EventCategoryRepository::class)]
#[ORM\Table(name: 'event_category')]
class EventCategory extends EventCategoryGenerated
{
    // Add custom properties here

    // Add custom methods here
}
