<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CalendarTypeGenerated;
use App\Repository\CalendarTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CalendarType Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: CalendarTypeRepository::class)]
#[ORM\Table(name: 'calendar_type')]
class CalendarType extends CalendarTypeGenerated
{
    // Add custom properties here

    // Add custom methods here
}
