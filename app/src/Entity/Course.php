<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CourseGenerated;
use App\Repository\CourseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Course Entity
 *
 * Educational courses and training programs
 * Extends generated base class - only add custom business logic here
 */
#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course extends CourseGenerated
{
    /**
     * Calculate total length by summing all module lengths
     */
    public function calculateTotalLengthSeconds(): void
    {
        $total = 0;
        foreach ($this->modules as $module) {
            $total += $module->getTotalLengthSeconds();
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

    /**
     * Get all lectures from all modules, sorted by module order then lecture order
     * @return array<CourseLecture>
     */
    public function getLectures(): array
    {
        $lectures = [];
        foreach ($this->modules as $module) {
            foreach ($module->getLectures() as $lecture) {
                $lectures[] = $lecture;
            }
        }
        return $lectures;
    }
}
