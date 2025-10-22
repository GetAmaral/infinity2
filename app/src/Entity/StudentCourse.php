<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\StudentCourseGenerated;
use App\Repository\StudentCourseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * StudentCourse Entity
 *
 * Student course enrollments and participation
 * Extends generated base class - only add custom business logic here
 */
#[ORM\Entity(repositoryClass: StudentCourseRepository::class)]
class StudentCourse extends StudentCourseGenerated
{
    public const MIN_COMPLETED = 95.0;

    public function __construct()
    {
        parent::__construct();
        $this->enrolledAt = new \DateTimeImmutable();
    }

    /**
     * Check if the course is completed
     */
    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    /**
     * Recalculate progress based on all child StudentLectures.
     * Sums watchedSeconds and calculates percentage.
     */
    public function recalculateProgress(): void
    {
        $totalWatchedSeconds = 0;

        foreach ($this->studentLectures as $studentLecture) {
            $totalWatchedSeconds += $studentLecture->getWatchedSeconds();
        }

        $this->progressSeconds = (float) $totalWatchedSeconds;

        // Calculate percentage
        $courseTotalSeconds = $this->course->getTotalLengthSeconds();
        if ($courseTotalSeconds > 0) {
            $percentage = ($this->progressSeconds / $courseTotalSeconds) * 100;
            $this->progressPercentage = min($percentage, 100.0); // Max 100%
        } else {
            $this->progressPercentage = 0.0;
        }

        // Update lastDate
        $this->lastDate = new \DateTimeImmutable();

        // Check if completed (MIN_COMPLETED threshold)
        if ($this->progressPercentage >= self::MIN_COMPLETED && $this->completedAt === null) {
            $this->completedAt = new \DateTimeImmutable();
        } elseif ($this->progressPercentage < self::MIN_COMPLETED && $this->completedAt !== null) {
            // Reset completion if progress drops below threshold
            $this->completedAt = null;
        }
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->student->getName(), $this->course->getName());
    }
}
