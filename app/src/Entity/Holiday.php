<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\HolidayGenerated;
use App\Repository\HolidayRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Holiday Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: HolidayRepository::class)]
#[ORM\Table(name: 'holiday')]
class Holiday extends HolidayGenerated
{
    // Add custom properties here

    // Add custom methods here
}
