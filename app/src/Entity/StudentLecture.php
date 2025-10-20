<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StudentLectureRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
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
        ),
        new Get(
            uriTemplate: '/student-lectures/{id}',
            security: "is_granted('VIEW', object)"
        ),
        new Patch(
            uriTemplate: '/student-lectures/{id}',
            security: "is_granted('EDIT', object)"
        ),
        new Post(
            uriTemplate: '/student-lectures',
            security: "is_granted('ROLE_USER')"
        ),
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

    // === ENGAGEMENT ANALYTICS ===

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['student_lecture:read'])]
    private ?\DateTimeImmutable $firstWatchedAt = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['student_lecture:read'])]
    private int $watchCount = 0;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['student_lecture:read'])]
    private int $totalWatchTimeSeconds = 0;

    #[ORM\Column(type: 'json')]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private array $videoBookmarks = [];

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private ?string $notes = null;

    // === QUIZ MANAGEMENT ===

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['student_lecture:read'])]
    private int $quizAttempts = 0;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['student_lecture:read'])]
    private ?float $quizBestScore = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['student_lecture:read'])]
    private ?float $quizLastScore = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['student_lecture:read'])]
    private bool $quizPassed = false;

    // === ASSIGNMENT MANAGEMENT ===

    #[ORM\Column(type: 'boolean')]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private bool $assignmentSubmitted = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['student_lecture:read'])]
    private ?\DateTimeImmutable $assignmentSubmittedAt = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['student_lecture:read'])]
    private ?string $assignmentFilePath = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['student_lecture:read'])]
    private ?float $assignmentScore = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['student_lecture:read'])]
    private ?string $assignmentFeedback = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['student_lecture:read'])]
    private ?\DateTimeImmutable $assignmentGradedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['student_lecture:read'])]
    private ?User $assignmentGradedBy = null;

    // === FLAGGING SYSTEM ===

    #[ORM\Column(type: 'boolean', name: 'is_flagged')]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private bool $flagged = false;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private ?string $flaggedReason = null;

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

    // === ENGAGEMENT ANALYTICS GETTERS/SETTERS ===

    public function getFirstWatchedAt(): ?\DateTimeImmutable
    {
        return $this->firstWatchedAt;
    }

    public function setFirstWatchedAt(?\DateTimeImmutable $firstWatchedAt): self
    {
        $this->firstWatchedAt = $firstWatchedAt;
        return $this;
    }

    public function getWatchCount(): int
    {
        return $this->watchCount;
    }

    public function setWatchCount(int $watchCount): self
    {
        $this->watchCount = $watchCount;
        return $this;
    }

    public function incrementWatchCount(): self
    {
        $this->watchCount++;
        return $this;
    }

    public function getTotalWatchTimeSeconds(): int
    {
        return $this->totalWatchTimeSeconds;
    }

    public function setTotalWatchTimeSeconds(int $totalWatchTimeSeconds): self
    {
        $this->totalWatchTimeSeconds = $totalWatchTimeSeconds;
        return $this;
    }

    public function addWatchTimeSeconds(int $seconds): self
    {
        $this->totalWatchTimeSeconds += $seconds;
        return $this;
    }

    public function getVideoBookmarks(): array
    {
        return $this->videoBookmarks;
    }

    public function setVideoBookmarks(array $videoBookmarks): self
    {
        $this->videoBookmarks = $videoBookmarks;
        return $this;
    }

    public function addBookmark(int $timestampSeconds, ?string $note = null): self
    {
        $this->videoBookmarks[] = [
            'timestamp' => $timestampSeconds,
            'note' => $note,
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        return $this;
    }

    public function removeBookmark(int $timestampSeconds): self
    {
        $this->videoBookmarks = array_filter(
            $this->videoBookmarks,
            fn($bookmark) => $bookmark['timestamp'] !== $timestampSeconds
        );
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    // === QUIZ MANAGEMENT GETTERS/SETTERS ===

    public function getQuizAttempts(): int
    {
        return $this->quizAttempts;
    }

    public function setQuizAttempts(int $quizAttempts): self
    {
        $this->quizAttempts = $quizAttempts;
        return $this;
    }

    public function incrementQuizAttempts(): self
    {
        $this->quizAttempts++;
        return $this;
    }

    public function getQuizBestScore(): ?float
    {
        return $this->quizBestScore;
    }

    public function setQuizBestScore(?float $quizBestScore): self
    {
        $this->quizBestScore = $quizBestScore;
        return $this;
    }

    public function getQuizLastScore(): ?float
    {
        return $this->quizLastScore;
    }

    public function setQuizLastScore(?float $quizLastScore): self
    {
        $this->quizLastScore = $quizLastScore;
        return $this;
    }

    public function isQuizPassed(): bool
    {
        return $this->quizPassed;
    }

    public function setQuizPassed(bool $quizPassed): self
    {
        $this->quizPassed = $quizPassed;
        return $this;
    }

    /**
     * Record a quiz attempt and update scores
     */
    public function recordQuizAttempt(float $score, bool $passed): self
    {
        $this->incrementQuizAttempts();
        $this->quizLastScore = $score;

        // Update best score if this is better
        if ($this->quizBestScore === null || $score > $this->quizBestScore) {
            $this->quizBestScore = $score;
        }

        // Update passed status
        $this->quizPassed = $passed;

        return $this;
    }

    // === ASSIGNMENT MANAGEMENT GETTERS/SETTERS ===

    public function isAssignmentSubmitted(): bool
    {
        return $this->assignmentSubmitted;
    }

    public function setAssignmentSubmitted(bool $assignmentSubmitted): self
    {
        $this->assignmentSubmitted = $assignmentSubmitted;
        return $this;
    }

    public function getAssignmentSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->assignmentSubmittedAt;
    }

    public function setAssignmentSubmittedAt(?\DateTimeImmutable $assignmentSubmittedAt): self
    {
        $this->assignmentSubmittedAt = $assignmentSubmittedAt;
        return $this;
    }

    public function getAssignmentFilePath(): ?string
    {
        return $this->assignmentFilePath;
    }

    public function setAssignmentFilePath(?string $assignmentFilePath): self
    {
        $this->assignmentFilePath = $assignmentFilePath;
        return $this;
    }

    public function getAssignmentScore(): ?float
    {
        return $this->assignmentScore;
    }

    public function setAssignmentScore(?float $assignmentScore): self
    {
        $this->assignmentScore = $assignmentScore;
        return $this;
    }

    public function getAssignmentFeedback(): ?string
    {
        return $this->assignmentFeedback;
    }

    public function setAssignmentFeedback(?string $assignmentFeedback): self
    {
        $this->assignmentFeedback = $assignmentFeedback;
        return $this;
    }

    public function getAssignmentGradedAt(): ?\DateTimeImmutable
    {
        return $this->assignmentGradedAt;
    }

    public function setAssignmentGradedAt(?\DateTimeImmutable $assignmentGradedAt): self
    {
        $this->assignmentGradedAt = $assignmentGradedAt;
        return $this;
    }

    public function getAssignmentGradedBy(): ?User
    {
        return $this->assignmentGradedBy;
    }

    public function setAssignmentGradedBy(?User $assignmentGradedBy): self
    {
        $this->assignmentGradedBy = $assignmentGradedBy;
        return $this;
    }

    /**
     * Submit an assignment
     */
    public function submitAssignment(string $filePath): self
    {
        $this->assignmentSubmitted = true;
        $this->assignmentSubmittedAt = new \DateTimeImmutable();
        $this->assignmentFilePath = $filePath;
        return $this;
    }

    /**
     * Grade a submitted assignment
     */
    public function gradeAssignment(float $score, string $feedback, User $gradedBy): self
    {
        $this->assignmentScore = $score;
        $this->assignmentFeedback = $feedback;
        $this->assignmentGradedAt = new \DateTimeImmutable();
        $this->assignmentGradedBy = $gradedBy;
        return $this;
    }

    // === FLAGGING SYSTEM GETTERS/SETTERS ===

    public function isFlagged(): bool
    {
        return $this->flagged;
    }

    public function setFlagged(bool $flagged): self
    {
        $this->flagged = $flagged;
        return $this;
    }

    public function getFlaggedReason(): ?string
    {
        return $this->flaggedReason;
    }

    public function setFlaggedReason(?string $flaggedReason): self
    {
        $this->flaggedReason = $flaggedReason;
        return $this;
    }

    /**
     * Flag this lecture for instructor review
     */
    public function flag(string $reason): self
    {
        $this->flagged = true;
        $this->flaggedReason = $reason;
        return $this;
    }

    /**
     * Remove flag
     */
    public function unflag(): self
    {
        $this->flagged = false;
        $this->flaggedReason = null;
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
        // Track first watch timestamp
        if ($this->watchedSeconds > 0 && $this->firstWatchedAt === null) {
            $this->firstWatchedAt = new \DateTimeImmutable();
        }

        $lectureLength = $this->lecture->getDurationSeconds();

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