<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\WorkingHourGenerated;
use App\Repository\WorkingHourRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Working Hour Entity
 *
 * Defines employee/user working hours and availability schedules for calendar management and appointment booking *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: WorkingHourRepository::class)]
#[ORM\Table(name: 'working_hour')]
class WorkingHour extends WorkingHourGenerated
{
    // Add custom properties here

    // Add custom methods here
}
