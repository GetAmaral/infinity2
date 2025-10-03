<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StepOutputRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * StepOutput - Defines a possible exit from a Step
 *
 * Outputs define conditional routing to next steps:
 * - Name and description for documentation
 * - Conditional expression (regex, keywords, or custom logic)
 * - Destination step to route to when condition matches
 */
#[ORM\Entity(repositoryClass: StepOutputRepository::class)]
class StepOutput extends EntityBase
{
    #[ORM\ManyToOne(targetEntity: Step::class, inversedBy: 'outputs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['output:read'])]
    protected Step $step;

    #[ORM\ManyToOne(targetEntity: Step::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['output:read', 'output:write'])]
    protected ?Step $destinationStep = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['output:read', 'output:write'])]
    protected string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['output:read', 'output:write'])]
    protected ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['output:read', 'output:write'])]
    protected ?string $conditional = null;

    public function getStep(): Step
    {
        return $this->step;
    }

    public function setStep(?Step $step): self
    {
        $this->step = $step;
        return $this;
    }

    public function getDestinationStep(): ?Step
    {
        return $this->destinationStep;
    }

    public function setDestinationStep(?Step $destinationStep): self
    {
        $this->destinationStep = $destinationStep;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getConditional(): ?string
    {
        return $this->conditional;
    }

    public function setConditional(?string $conditional): self
    {
        $this->conditional = $conditional;
        return $this;
    }

    /**
     * Check if this output has a destination
     */
    public function hasDestination(): bool
    {
        return $this->destinationStep !== null;
    }

    /**
     * Check if this output has a conditional
     */
    public function hasConditional(): bool
    {
        return !empty($this->conditional);
    }

    public function __toString(): string
    {
        $destination = $this->destinationStep ? ' → ' . $this->destinationStep->getName() : '';
        return $this->name . $destination;
    }
}
