<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\EventResourceTypeGenerated;
use App\Repository\EventResourceTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * EventResourceType Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: EventResourceTypeRepository::class)]
#[ORM\Table(name: 'event_resource_type')]
class EventResourceType extends EventResourceTypeGenerated
{
    // Add custom properties here

    // Add custom methods here
}
