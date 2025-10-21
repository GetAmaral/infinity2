<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CourseModuleGenerated;
use App\Repository\CourseModuleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CourseModule Entity
 *
 * Course modules for structured learning paths
 * Extends generated base class - only add custom business logic here
 */
#[ORM\Entity(repositoryClass: CourseModuleRepository::class)]
class CourseModule extends CourseModuleGenerated
{
    /**
     * Calculate total length by summing all lecture lengths
     */
    public function calculateTotalLengthSeconds(): void
    {
        $total = 0;
        foreach ($this->lectures as $lecture) {
            $total += $lecture->getLengthSeconds();
        }
        $this->totalLengthSeconds = $total;
    }

    /**
     * Get formatted total length (e.g., "1:23" for 1 hour 23 minutes)
     */
    public function getTotalLengthFormatted(): string
    {
        if ($this->totalLengthSeconds < 60) {
            return $this->totalLengthSeconds . ' s';
        }

        if ($this->totalLengthSeconds < 3600) {
            $minutes = (int)floor($this->totalLengthSeconds / 60);
            return $minutes . ' m';
        }

        $hours = (int)floor($this->totalLengthSeconds / 3600);
        $minutes = (int)floor(($this->totalLengthSeconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
