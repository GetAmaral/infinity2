<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StudentLectureRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StudentLectureRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['student_lecture:read']],
    denormalizationContext: ['groups' => ['student_lecture:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/student-lectures',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['student_lecture:read', 'audit:read']]
        )
    ]
)]
class StudentLecture extends EntityBase
{
    public const MIN_COMPLETION = 90.0;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['student_lecture:read'])]
    private User $student;

    #[ORM\ManyToOne(targetEntity: CourseLecture::class, inversedBy: 'studentLectures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['student_lecture:read'])]
    private CourseLecture $lecture;

    #[ORM\ManyToOne(targetEntity: StudentCourse::class, inversedBy: 'studentLectures')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['student_lecture:read'])]
    private ?StudentCourse $studentCourse = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private int $watchedSeconds = 0;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private int $lastPositionSeconds = 0;

    #[ORM\Column(type: 'float')]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private float $completionPercentage = 0.0;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private bool $completed = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private ?\DateTimeImmutable $lastWatchedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private ?\DateTimeImmutable $completedAt = null;

    public function getStudent(): User
    {
        return $this->student;
    }

    public function setStudent(User $student): self
    {
        $this->student = $student;
        return $this;
    }

    public function getLecture(): CourseLecture
    {
        return $this->lecture;
    }

    public function setLecture(CourseLecture $lecture): self
    {
        $this->lecture = $lecture;
        return $this;
    }

    public function getWatchedSeconds(): int
    {
        return $this->watchedSeconds;
    }

    public function setWatchedSeconds(int $watchedSeconds): self
    {
        $this->watchedSeconds = $watchedSeconds;
        return $this;
    }

    public function getLastPositionSeconds(): int
    {
        return $this->lastPositionSeconds;
    }

    public function setLastPositionSeconds(int $lastPositionSeconds): self
    {
        $this->lastPositionSeconds = $lastPositionSeconds;
        return $this;
    }

    public function getCompletionPercentage(): float
    {
        return $this->completionPercentage;
    }

    public function setCompletionPercentage(float $completionPercentage): self
    {
        $this->completionPercentage = $completionPercentage;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): self
    {
        $this->completed = $completed;
        return $this;
    }

    public function getLastWatchedAt(): ?\DateTimeImmutable
    {
        return $this->lastWatchedAt;
    }

    public function setLastWatchedAt(?\DateTimeImmutable $lastWatchedAt): self
    {
        $this->lastWatchedAt = $lastWatchedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    /**
     * Dynamic check: returns true if completion >= 25%
     */
    public function isReached25Percent(): bool
    {
        return $this->completionPercentage >= 25.0;
    }

    /**
     * Dynamic check: returns true if completion >= 50%
     */
    public function isReached50Percent(): bool
    {
        return $this->completionPercentage >= 50.0;
    }

    /**
     * Dynamic check: returns true if completion >= 75%
     */
    public function isReached75Percent(): bool
    {
        return $this->completionPercentage >= 75.0;
    }

    public function getStudentCourse(): ?StudentCourse
    {
        return $this->studentCourse;
    }

    public function setStudentCourse(?StudentCourse $studentCourse): self
    {
        $this->studentCourse = $studentCourse;
        return $this;
    }

    /**
     * Calculate completion percentage and status.
     * Called automatically before persist or update.
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateCompletion(): void
    {
        $lectureLength = $this->lecture->getLengthSeconds();

        error_log(sprintf('[PreUpdate] Calculating completion: watchedSeconds=%d, lectureLength=%d',
            $this->watchedSeconds,
            $lectureLength
        ));

        if ($lectureLength > 0) {
            // Lecture with video - calculate based on watched time
            $percentage = ($this->watchedSeconds / $lectureLength) * 100;
            $this->completionPercentage = min($percentage, 100.0); // Max 100%
            error_log(sprintf('[PreUpdate] Calculated percentage: %.2f%%', $this->completionPercentage));
        } else {
            // Videoless lecture - mark as 100% if any watched seconds recorded, 0% otherwise
            if ($this->watchedSeconds > 0) {
                $this->completionPercentage = 100.0;
                error_log('[PreUpdate] Videoless lecture marked as complete (100%)');
            } else {
                $this->completionPercentage = 0.0;
                error_log('[PreUpdate] Videoless lecture not started (0%)');
            }
        }

        // Auto-mark as completed if >= MIN_COMPLETION
        if ($this->completionPercentage >= self::MIN_COMPLETION) {
            if (!$this->completed) {
                $this->completed = true;
                $this->completedAt = new \DateTimeImmutable();
                error_log('[PreUpdate] Marking as COMPLETED');
            }
        } else {
            // Reset completion if below threshold
            $this->completed = false;
            $this->completedAt = null;
            error_log('[PreUpdate] Marking as NOT COMPLETED');
        }
    }

    /**
     * Update parent StudentCourse progress.
     * Called automatically after persist or update.
     */
    #[ORM\PostPersist]
    #[ORM\PostUpdate]
    public function updateParentProgress(): void
    {
        if ($this->studentCourse !== null) {
            $this->studentCourse->recalculateProgress();
        }
    }

    public function __toString(): string
    {
        return sprintf('%s - %s',
            $this->student->getName(),
            $this->lecture->getName()
        );
    }
}