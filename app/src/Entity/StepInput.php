<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InputType;
use App\Repository\StepInputRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(type: 'string', enumType: InputType::class)]
    #[Groups(['input:read', 'input:write'])]
    protected InputType $type = InputType::ANY;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['input:read', 'input:write'])]
    protected string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['input:read', 'input:write'])]
    protected ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['input:read', 'input:write'])]
    protected ?string $prompt = null;

    #[ORM\OneToMany(mappedBy: 'targetInput', targetEntity: StepConnection::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['input:read'])]
    protected Collection $connections;

    public function __construct()
    {
        parent::__construct();
        $this->connections = new ArrayCollection();
    }

    public function getStep(): Step
    {
        return $this->step;
    }

    public function setStep(?Step $step): self
    {
        $this->step = $step;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
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

    /**
     * @return Collection<int, StepConnection>
     */
    public function getConnections(): Collection
    {
        return $this->connections;
    }

    public function addConnection(StepConnection $connection): self
    {
        if (!$this->connections->contains($connection)) {
            $this->connections->add($connection);
            $connection->setTargetInput($this);
        }
        return $this;
    }

    public function removeConnection(StepConnection $connection): self
    {
        if ($this->connections->removeElement($connection)) {
            if ($connection->getTargetInput() === $this) {
                $connection->setTargetInput(null);
            }
        }
        return $this;
    }

    /**
     * Check if this input has any connections
     */
    public function hasConnections(): bool
    {
        return !$this->connections->isEmpty();
    }

    public function __toString(): string
    {
        return $this->name . ' [' . $this->type->value . ']';
    }
}
