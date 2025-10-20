<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\AgentGenerated;
use App\Repository\AgentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Agent Entity
 *
 * Customer service and sales agents *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: AgentRepository::class)]
#[ORM\Table(name: 'agent_table')]
class Agent extends AgentGenerated
{
    // Add custom properties here

    // Add custom methods here
}
