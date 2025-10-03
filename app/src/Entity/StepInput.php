<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InputType;
use App\Repository\StepInputRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * StepInput - Defines how a Step can be entered
 *
 * Inputs define entry conditions from previous steps:
 * - Source step that can route to this step
 * - Type of completion required (fully completed, failed, or any)
 * - Additional prompt context when entering via this input
 */
#[ORM\Entity(repositoryClass: StepInputRepository::class)]
class StepInput extends EntityBase
{
    #[ORM\ManyToOne(targetEntity: Step::class, inversedBy: 'inputs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['input:read'])]
    protected Step $step;

    #[ORM\ManyToOne(targetEntity: Step::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['input:read', 'input:write'])]
    protected ?Step $sourceStep = null;

    #[ORM\Column(type: 'string', enumType: InputType::class)]
    #[Groups(['input:read', 'input:write'])]
    protected InputType $type = InputType::ANY;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['input:read', 'input:write'])]
    protected string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['input:read', 'input:write'])]
    protected ?string $prompt = null;

    public function getStep(): Step
    {
        return $this->step;
    }

    public function setStep(?Step $step): self
    {
        $this->step = $step;
        return $this;
    }

    public function getSourceStep(): ?Step
    {
        return $this->sourceStep;
    }

    public function setSourceStep(?Step $sourceStep): self
    {
        $this->sourceStep = $sourceStep;
        return $this;
    }

    public function getType(): InputType
    {
        return $this->type;
    }

    public function setType(InputType $type): self
    {
        $this->type = $type;
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

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    /**
     * Check if this input has a source step
     */
    public function hasSource(): bool
    {
        return $this->sourceStep !== null;
    }

    /**
     * Check if this input requires full completion
     */
    public function requiresFullCompletion(): bool
    {
        return $this->type === InputType::FULLY_COMPLETED;
    }

    /**
     * Check if this input accepts any status
     */
    public function acceptsAnyStatus(): bool
    {
        return $this->type === InputType::ANY;
    }

    public function __toString(): string
    {
        $source = $this->sourceStep ? $this->sourceStep->getName() . ' → ' : '';
        return $source . $this->name . ' [' . $this->type->value . ']';
    }
}
