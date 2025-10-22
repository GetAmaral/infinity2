<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\StepConnectionGenerated;
use App\Repository\StepConnectionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * StepConnection - Visual connection between StepOutput and StepInput
 *
 * Represents a visual workflow connection on the canvas:
 * - One StepOutput can have AT MOST one StepConnection
 * - One StepInput can have MANY StepConnections
 * - No self-loops allowed (Step A → Step A)
 * - Unique constraint prevents duplicate connections
 *
 * Extends generated base class - only add custom business logic here
 */
#[ORM\Entity(repositoryClass: StepConnectionRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_connection', columns: ['source_output_id', 'target_input_id'])]
class StepConnection extends StepConnectionGenerated
{
    /**
     * Custom __toString implementation showing the connection flow
     */
    public function __toString(): string
    {
        $output = $this->sourceOutput->getStep()->getName() . '.' . $this->sourceOutput->getName();
        $input = $this->targetInput->getStep()->getName() . '.' . $this->targetInput->getName();
        return $output . ' → ' . $input;
    }
}
