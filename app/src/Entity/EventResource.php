<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\EventResourceGenerated;
use App\Repository\EventResourceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * EventResource Entity
 *
 * Bookable resources (Rooms, Equipment, etc.) *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: EventResourceRepository::class)]
#[ORM\Table(name: 'event_resource')]
class EventResource extends EventResourceGenerated
{
    // Add custom properties here

    // Add custom methods here
}
