<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\DealGenerated;
use App\Repository\DealRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Deal Entity
 *
 * Sales opportunities and deals tracking *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: DealRepository::class)]
#[ORM\Table(name: 'deal')]
class Deal extends DealGenerated
{
    // Add custom properties here

    // Add custom methods here

    /**
     * Check if deal is "hot" (high AI score)
     */
    public function isHotDeal(): bool
    {
        return $this->aiScore !== null && $this->aiScore >= 75;
    }

    /**
     * Check if deal is "at risk"
     */
    public function isAtRisk(): bool
    {
        return $this->aiScore !== null && $this->aiScore < 50;
    }

    /**
     * Check if deal is stale/rotten
     */
    public function isRotten(): bool
    {
        return $this->daysInCurrentStage !== null
            && $this->daysInCurrentStage > 30;
    }

    /**
     * Get formatted win probability
     */
    public function getWinProbabilityFormatted(): string
    {
        if ($this->winProbability === null) {
            return 'N/A';
        }
        return number_format((float)$this->winProbability, 1) . '%';
    }

    /**
     * Calculate days stale (auto-update on save)
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateDaysStale(): void
    {
        if ($this->lastActivityDate) {
            $now = new \DateTimeImmutable();
            $this->daysStale = $now->diff($this->lastActivityDate)->days;
        }
    }

    /**
     * Check if user can move this deal
     */
    public function canBeMovedBy(\App\Entity\User $user): bool
    {
        // Owner can always move
        if ($this->owner === $user) {
            return true;
        }

        // Team members can move
        if ($this->team && $this->team->contains($user)) {
            return true;
        }

        // Manager can move
        if ($this->manager === $user) {
            return true;
        }

        return false;
    }
}
