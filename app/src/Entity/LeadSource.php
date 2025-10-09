<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\LeadSourceGenerated;
use App\Repository\LeadSourceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * LeadSource Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: LeadSourceRepository::class)]
#[ORM\Table(name: 'lead_source')]
class LeadSource extends LeadSourceGenerated
{
    // Add custom properties here

    // Add custom methods here
}
