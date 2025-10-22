<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\StepQuestionGenerated;
use App\Repository\StepQuestionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * StepQuestion - A question for the AI to answer within a Step
 *
 * Questions guide the AI's decision-making process with:
 * - A prompt defining what to ask
 * - An objective explaining the purpose
 * - Importance weighting (1-3 stars)
 * - Few-shot examples stored as JSONB arrays (positive and negative)
 *
 * Extends generated base class - only add custom business logic here
 */
#[ORM\Entity(repositoryClass: StepQuestionRepository::class)]
class StepQuestion extends StepQuestionGenerated
{
    /**
     * Override getImportance to ensure default value
     */
    public function getImportance(): int
    {
        return $this->importance ?? 1;
    }

    /**
     * Override setImportance to ensure default value
     */
    public function setImportance(?int $importance): self
    {
        $this->importance = $importance ?? 1;
        return $this;
    }

    /**
     * Override getFewShotPositive to ensure array return
     */
    public function getFewShotPositive(): ?array
    {
        return $this->fewShotPositive ?? [];
    }

    /**
     * Override getFewShotNegative to ensure array return
     */
    public function getFewShotNegative(): ?array
    {
        return $this->fewShotNegative ?? [];
    }

    /**
     * Custom __toString implementation
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
