<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CalendarGenerated;
use App\Repository\CalendarRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Calendar Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: CalendarRepository::class)]
#[ORM\Table(name: 'calendar')]
class Calendar extends CalendarGenerated
{
    // Add custom properties here

    // Add custom methods here
}
