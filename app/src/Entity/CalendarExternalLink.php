<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CalendarExternalLinkGenerated;
use App\Repository\CalendarExternalLinkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CalendarExternalLink Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: CalendarExternalLinkRepository::class)]
#[ORM\Table(name: 'calendar_external_link')]
class CalendarExternalLink extends CalendarExternalLinkGenerated
{
    // Add custom properties here

    // Add custom methods here
}
