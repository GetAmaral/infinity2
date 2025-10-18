<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StepConnectionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * StepConnection - Visual connection between StepOutput and StepInput
 *
 * Represents a visual workflow connection on the canvas:
 * - One StepOutput can have AT MOST one StepConnection
 * - One StepInput can have MANY StepConnections
 * - No self-loops allowed (Step A → Step A)
 * - Unique constraint prevents duplicate connections
 */
#[ORM\Entity(repositoryClass: StepConnectionRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_connection', columns: ['source_output_id', 'target_input_id'])]
class StepConnection extends EntityBase
{
    #[ORM\OneToOne(targetEntity: StepOutput::class, inversedBy: 'connection')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['connection:read'])]
    protected StepOutput $sourceOutput;

    #[ORM\ManyToOne(targetEntity: StepInput::class, inversedBy: 'connections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['connection:read'])]
    protected StepInput $targetInput;

    public function getSourceOutput(): StepOutput
    {
        return $this->sourceOutput;
    }

    public function setSourceOutput(?StepOutput $sourceOutput): self
    {
        $this->sourceOutput = $sourceOutput;
        return $this;
    }

    public function getTargetInput(): StepInput
    {
        return $this->targetInput;
    }

    public function setTargetInput(?StepInput $targetInput): self
    {
        $this->targetInput = $targetInput;
        return $this;
    }

    public function __toString(): string
    {
        $output = $this->sourceOutput->getStep()->getName() . '.' . $this->sourceOutput->getName();
        $input = $this->targetInput->getStep()->getName() . '.' . $this->targetInput->getName();
        return $output . ' → ' . $input;
    }
}
