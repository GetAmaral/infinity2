<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CompetitorGenerated;
use App\Repository\CompetitorRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Competitor Entity
 *
 * Competitor tracking for sales and market analysis *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: CompetitorRepository::class)]
#[ORM\Table(name: 'competitor')]
class Competitor extends CompetitorGenerated
{
    // Add custom properties here

    // Add custom methods here
}
