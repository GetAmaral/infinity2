<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\LeadSourceGenerated;
use App\Repository\LeadSourceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Lead Source Entity
 *
 * Lead sources for multi-touch attribution tracking and ROI analysis *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: LeadSourceRepository::class)]
#[ORM\Table(name: 'lead_source')]
class LeadSource extends LeadSourceGenerated
{
    // Add custom properties here

    // Add custom methods here
}
