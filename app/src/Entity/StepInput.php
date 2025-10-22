<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\StepInputGenerated;
use App\Enum\InputType;
use App\Repository\StepInputRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * StepInput - Defines how a Step can be entered
 *
 * Inputs define entry conditions from previous steps:
 * - Source step that can route to this step
 * - Type of completion required (fully completed, failed, or any)
 * - Additional prompt context when entering via this input
 *
 * Extends generated base class - only add custom business logic here
 */
#[ORM\Entity(repositoryClass: StepInputRepository::class)]
class StepInput extends StepInputGenerated
{
    // Removed type override - using parent's string-based type
    // Use InputType::from(parent::getType()) if enum is needed

    /**
     * Check if this input requires full completion
     */
    public function requiresFullCompletion(): bool
    {
        return $this->getType() === InputType::FULLY_COMPLETED->value;
    }

    /**
     * Check if this input accepts any status
     */
    public function acceptsAnyStatus(): bool
    {
        return $this->getType() === InputType::ANY->value;
    }

    /**
     * Check if this input has any connections
     */
    public function hasConnections(): bool
    {
        return !$this->connections->isEmpty();
    }

    /**
     * Custom __toString implementation
     */
    public function __toString(): string
    {
        return $this->name . ' [' . $this->getType()->value . ']';
    }
}
