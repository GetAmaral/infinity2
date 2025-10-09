<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\WorkingHourGenerated;
use App\Repository\WorkingHourRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * WorkingHour Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: WorkingHourRepository::class)]
#[ORM\Table(name: 'working_hour')]
class WorkingHour extends WorkingHourGenerated
{
    // Add custom properties here

    // Add custom methods here
}
