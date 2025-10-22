<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\DealGenerated;
use App\Repository\DealRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Deal Entity
 *
 * Sales opportunities and deals tracking *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: DealRepository::class)]
#[ORM\Table(name: 'deal')]
class Deal extends DealGenerated
{
    // Add custom properties here

    // Add custom methods here
}
