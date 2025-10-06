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

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['output:read', 'output:write'])]
    protected string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['output:read', 'output:write'])]
    protected ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['output:read', 'output:write'])]
    protected ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['output:read', 'output:write'])]
    protected ?string $conditional = null;

    #[ORM\OneToOne(mappedBy: 'sourceOutput', targetEntity: StepConnection::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['output:read'])]
    protected ?StepConnection $connection = null;

    public function getStep(): Step
    {
        return $this->step;
    }

    public function setStep(?Step $step): self
    {
        $this->step = $step;
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
     * Check if this output has a conditional
     */
    public function hasConditional(): bool
    {
        return !empty($this->conditional);
    }

    public function getConnection(): ?StepConnection
    {
        return $this->connection;
    }

    public function setConnection(?StepConnection $connection): self
    {
        // Unset the owning side of the relation if necessary
        if ($connection === null && $this->connection !== null) {
            $this->connection->setSourceOutput(null);
        }

        // Set the owning side of the relation if necessary
        if ($connection !== null && $connection->getSourceOutput() !== $this) {
            $connection->setSourceOutput($this);
        }

        $this->connection = $connection;

        return $this;
    }

    /**
     * Check if this output has a connection
     */
    public function hasConnection(): bool
    {
        return $this->connection !== null;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
