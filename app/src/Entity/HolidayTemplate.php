<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\HolidayTemplateGenerated;
use App\Repository\HolidayTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * HolidayTemplate Entity
 *
 * Templates for holidays (National, Regional, Company, etc.) *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: HolidayTemplateRepository::class)]
#[ORM\Table(name: 'holiday_template')]
class HolidayTemplate extends HolidayTemplateGenerated
{
    // Add custom properties here

    // Add custom methods here
}
