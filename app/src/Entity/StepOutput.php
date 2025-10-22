<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\StepOutputGenerated;
use App\Repository\StepOutputRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * StepOutput - Defines a possible exit from a Step
 *
 * Outputs define conditional routing to next steps:
 * - Name and description for documentation
 * - Conditional expression (regex, keywords, or custom logic)
 * - Destination step to route to when condition matches
 *
 * Extends generated base class - only add custom business logic here
 */
#[ORM\Entity(repositoryClass: StepOutputRepository::class)]
class StepOutput extends StepOutputGenerated
{
    /**
     * Override setConnection to handle bidirectional relationship
     */
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
     * Check if this output has a conditional
     */
    public function hasConditional(): bool
    {
        return !empty($this->conditional);
    }

    /**
     * Check if this output has a connection
     */
    public function hasConnection(): bool
    {
        return $this->connection !== null;
    }

    /**
     * Custom __toString implementation
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
