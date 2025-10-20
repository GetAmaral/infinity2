<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\AgentTypeGenerated;
use App\Repository\AgentTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * AgentType Entity
 *
 * Agent types for customer support and sales teams *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: AgentTypeRepository::class)]
#[ORM\Table(name: 'agent_type')]
class AgentType extends AgentTypeGenerated
{
    // Add custom properties here

    // Add custom methods here
}
