<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CourseLectureGenerated;
use App\Repository\CourseLectureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * CourseLecture Entity
 *
 * Individual lectures and learning content
 * Extends generated base class - only add custom business logic here
 */
#[ORM\Entity(repositoryClass: CourseLectureRepository::class)]
#[Vich\Uploadable]
class CourseLecture extends CourseLectureGenerated
{
    // Vich Uploader field (not in generated class)
    #[Vich\UploadableField(mapping: 'lecture_videos', fileNameProperty: 'videoFileName')]
    private ?File $videoFile = null;

    public function getVideoFile(): ?File
    {
        return $this->videoFile;
    }

    public function setVideoFile(?File $videoFile): self
    {
        $this->videoFile = $videoFile;

        // VERY IMPORTANT: Force Doctrine to trigger update event
        if ($videoFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * Get video URL for playback
     * Priority: direct URL > uploaded file path > null
     */
    public function getVideoUrl(): ?string
    {
        if ($this->videoUrl) {
            return $this->videoUrl;
        }

        if ($this->videoPath) {
            return '/uploads/lectures/' . $this->videoPath;
        }

        return null;
    }

    /**
     * Set published status and automatically set publishedAt timestamp
     */
    public function setPublished(bool $published): self
    {
        $this->published = $published;
        if ($published && $this->publishedAt === null) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    // === CUSTOM ARRAY METHODS ===

    public function addAttachment(array $attachment): self
    {
        $this->attachments[] = $attachment;
        return $this;
    }

    public function addExternalLink(array $link): self
    {
        $this->externalLinks[] = $link;
        return $this;
    }

    // === ANALYTICS METHODS ===

    public function incrementViewCount(): self
    {
        $this->viewCount++;
        return $this;
    }

    public function incrementCompletionCount(): self
    {
        $this->completionCount++;
        return $this;
    }

    public function addRating(float $newRating): self
    {
        if ($this->rating === null) {
            $this->rating = $newRating;
            $this->ratingCount = 1;
        } else {
            $totalRating = $this->rating * $this->ratingCount;
            $this->ratingCount++;
            $this->rating = ($totalRating + $newRating) / $this->ratingCount;
        }
        return $this;
    }

    // === DURATION METHODS ===

    public function setDurationSeconds(int $durationSeconds): self
    {
        $this->durationSeconds = $durationSeconds;
        // Keep deprecated field in sync for backward compatibility
        $this->lengthSeconds = $durationSeconds;
        return $this;
    }

    /**
     * @deprecated Use getDurationSeconds() instead
     */
    public function getLengthSeconds(): int
    {
        return $this->durationSeconds;
    }

    /**
     * @deprecated Use setDurationSeconds() instead
     */
    public function setLengthSeconds(int $lengthSeconds): self
    {
        return $this->setDurationSeconds($lengthSeconds);
    }

    public function getDurationFormatted(): string
    {
        if ($this->durationSeconds < 60) {
            return $this->durationSeconds . ' s';
        }

        if ($this->durationSeconds < 3600) {
            $minutes = (int)floor($this->durationSeconds / 60);
            return $minutes . ' m';
        }

        $hours = (int)floor($this->durationSeconds / 3600);
        $minutes = (int)floor(($this->durationSeconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * @deprecated Use getDurationFormatted() instead
     */
    public function getLengthFormatted(): string
    {
        return $this->getDurationFormatted();
    }

    // === BUSINESS LOGIC METHODS ===

    /**
     * Check if lecture has video content
     */
    public function hasVideo(): bool
    {
        return $this->videoUrl !== null || $this->videoPath !== null || $this->videoFileName !== null;
    }

    /**
     * Check if lecture has transcript (accessibility)
     */
    public function hasTranscript(): bool
    {
        return $this->transcript !== null && $this->transcript !== '';
    }

    /**
     * Check if lecture has subtitles (accessibility)
     */
    public function hasSubtitles(): bool
    {
        return $this->subtitleUrl !== null;
    }

    /**
     * Check if lecture has downloadable attachments
     */
    public function hasAttachments(): bool
    {
        return count($this->attachments) > 0;
    }

    /**
     * Get number of attachments
     */
    public function getAttachmentCount(): int
    {
        return count($this->attachments);
    }

    /**
     * Check if lecture is microlearning (3-5 minutes)
     */
    public function isMicrolearning(): bool
    {
        return $this->durationSeconds > 0 && $this->durationSeconds <= 300;
    }

    /**
     * Get optimal duration assessment
     */
    public function getOptimalDuration(): string
    {
        if ($this->durationSeconds <= 180) {
            return 'excellent';
        }
        if ($this->durationSeconds <= 300) {
            return 'good';
        }
        if ($this->durationSeconds <= 600) {
            return 'acceptable';
        }
        return 'too_long';
    }

    /**
     * Check if lecture is available to a specific student
     */
    public function isAvailableToStudent(User $student): bool
    {
        // Must be active and published
        if (!$this->active || !$this->published) {
            return false;
        }

        // Free lectures available to all
        if ($this->free) {
            return true;
        }

        // Check if student is enrolled in the course
        $course = $this->courseModule->getCourse();
        foreach ($course->getStudentCourses() as $studentCourse) {
            if ($studentCourse->getStudent()->getId() === $student->getId() && $studentCourse->isActive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this is a free preview lecture
     */
    public function isFreePreview(): bool
    {
        return $this->free && $this->published && $this->active;
    }

    /**
     * Get completion rate (percentage of students who completed)
     */
    public function getCompletionRate(): float
    {
        if ($this->viewCount === 0) {
            return 0.0;
        }
        return ($this->completionCount / $this->viewCount) * 100;
    }

    /**
     * Get average rating (0-5 stars)
     */
    public function getAverageRating(): float
    {
        return $this->rating ?? 0.0;
    }

    /**
     * Get thumbnail URL (placeholder for future implementation)
     */
    public function getThumbnailUrl(): ?string
    {
        // TODO: Implement thumbnail generation/upload
        return null;
    }

    /**
     * Publish this lecture
     */
    public function publish(): self
    {
        $this->published = true;
        if ($this->publishedAt === null) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    /**
     * Unpublish this lecture
     */
    public function unpublish(): self
    {
        $this->published = false;
        return $this;
    }

    /**
     * Mark as free preview lecture
     */
    public function markAsFree(): self
    {
        $this->free = true;
        return $this;
    }

    /**
     * Update average watch percentage based on student progress
     */
    public function updateAverageWatchPercentage(): void
    {
        $totalPercentage = 0.0;
        $count = 0;

        foreach ($this->studentLectures as $studentLecture) {
            $totalPercentage += $studentLecture->getCompletionPercentage();
            $count++;
        }

        $this->averageWatchPercentage = $count > 0 ? ($totalPercentage / $count) : 0.0;
    }

    // === LIFECYCLE CALLBACKS ===

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        // Sync deprecated field
        if ($this->durationSeconds > 0 && $this->lengthSeconds === null) {
            $this->lengthSeconds = $this->durationSeconds;
        }

        // Set organization from course module if not set
        if (!isset($this->organization) && isset($this->courseModule)) {
            $this->organization = $this->courseModule->getCourse()->getOrganization();
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        // Sync deprecated field
        $this->lengthSeconds = $this->durationSeconds;
    }

    #[ORM\PostPersist]
    #[ORM\PostUpdate]
    #[ORM\PostRemove]
    public function updateModuleTotalLength(): void
    {
        if ($this->courseModule) {
            $this->courseModule->calculateTotalLengthSeconds();
            // Also update the parent course total length
            $this->courseModule->getCourse()->calculateTotalLengthSeconds();
        }
    }
}
