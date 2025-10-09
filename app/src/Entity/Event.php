<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\EventGenerated;
use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Event Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'event')]
class Event extends EventGenerated
{
    // Add custom properties here

    // Add custom methods here
}
