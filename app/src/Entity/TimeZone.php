<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TimeZoneGenerated;
use App\Repository\TimeZoneRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * TimeZone Entity
 *
 * Time zones for global calendar management *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: TimeZoneRepository::class)]
#[ORM\Table(name: 'time_zone')]
class TimeZone extends TimeZoneGenerated
{
    // Add custom properties here

    // Add custom methods here
}
