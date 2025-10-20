<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CourseLectureRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * CourseLecture Entity
 *
 * Represents a single lecture/lesson within a course module.
 * Supports video uploads, external video URLs, accessibility features,
 * learning resources, and comprehensive analytics.
 *
 * Key Features:
 * - Video content (upload or external URL)
 * - Accessibility (transcripts, subtitles)
 * - Downloadable attachments
 * - Analytics tracking
 * - Publishing workflow
 * - Free preview support
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: CourseLectureRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[ORM\Index(name: 'idx_lecture_module_order', columns: ['course_module_id', 'view_order'])]
#[ORM\Index(name: 'idx_lecture_published', columns: ['published', 'active'])]
#[ORM\Index(name: 'idx_lecture_free', columns: ['free'])]
#[ORM\Index(name: 'idx_lecture_status', columns: ['processing_status'])]
#[ORM\Index(name: 'idx_lecture_organization', columns: ['organization_id'])]
#[ApiResource(
    normalizationContext: ['groups' => ['course_lecture:read']],
    denormalizationContext: ['groups' => ['course_lecture:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/course-lectures',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['course_lecture:read', 'audit:read']]
        ),
        new Get(
            uriTemplate: '/course-lectures/{id}',
            security: "is_granted('VIEW', object)"
        ),
        new Post(
            uriTemplate: '/course-lectures',
            security: "is_granted('ROLE_INSTRUCTOR')"
        ),
        new Patch(
            uriTemplate: '/course-lectures/{id}',
            security: "is_granted('EDIT', object)"
        ),
        new Delete(
            uriTemplate: '/course-lectures/{id}',
            security: "is_granted('DELETE', object)"
        ),
        new GetCollection(
            uriTemplate: '/lectures/free',
            security: "is_granted('ROLE_USER')"
        ),
    ]
)]
class CourseLecture extends EntityBase
{
    // === BASIC INFORMATION ===

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'course.lecture.validation.name_required')]
    #[Assert\Length(min: 2, max: 255)]
    #[Groups(['course_lecture:read', 'course_lecture:write', 'course:read', 'student:read'])]
    protected string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 5000)]
    #[Groups(['course_lecture:read', 'course_lecture:write', 'student:read'])]
    protected ?string $description = null;

    // === CONTENT CONTROL ===

    #[ORM\Column(type: 'boolean')]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    protected bool $active = true;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    protected bool $published = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['course_lecture:read', 'course_lecture:write', 'student:read'])]
    protected bool $free = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['course_lecture:read'])]
    protected ?\DateTimeImmutable $publishedAt = null;

    // === VIDEO CONTENT ===

    #[Vich\UploadableField(mapping: 'lecture_videos', fileNameProperty: 'videoFileName')]
    private ?File $videoFile = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['course_lecture:read'])]
    private ?string $videoFileName = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['course_lecture:read'])]
    private ?string $videoPath = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Url(message: 'course.lecture.validation.invalid_video_url')]
    #[Assert\Length(max: 500)]
    #[Groups(['course_lecture:read', 'course_lecture:write', 'student:read'])]
    private ?string $videoUrl = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: ['upload', 'youtube', 'vimeo', 's3', 'url'])]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    private string $videoType = 'upload';

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['course_lecture:read'])]
    private ?string $videoResolution = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    #[Groups(['course_lecture:read'])]
    private ?int $videoSizeBytes = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['course_lecture:read'])]
    private string $processingStatus = 'pending';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['course_lecture:read'])]
    private ?string $processingStep = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['course_lecture:read'])]
    private int $processingPercentage = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course_lecture:read'])]
    private ?string $processingError = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['course_lecture:read'])]
    private ?\DateTimeImmutable $processedAt = null;

    // === ACCESSIBILITY (ADA Compliance) ===

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course_lecture:read', 'course_lecture:write', 'student:read'])]
    private ?string $transcript = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Url]
    #[Groups(['course_lecture:read', 'course_lecture:write', 'student:read'])]
    private ?string $subtitleUrl = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    private ?string $subtitleLanguage = null;

    // === LEARNING RESOURCES ===

    #[ORM\Column(type: 'json')]
    #[Groups(['course_lecture:read', 'course_lecture:write', 'student:read'])]
    private array $attachments = [];

    #[ORM\Column(type: 'json')]
    #[Groups(['course_lecture:read', 'course_lecture:write', 'student:read'])]
    private array $externalLinks = [];

    #[ORM\Column(type: 'json')]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    private array $learningObjectives = [];

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    private ?string $prerequisites = null;

    // === ANALYTICS & ENGAGEMENT ===

    #[ORM\Column(type: 'integer')]
    #[Groups(['course_lecture:read'])]
    private int $viewCount = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['course_lecture:read'])]
    private int $completionCount = 0;

    #[ORM\Column(type: 'float')]
    #[Groups(['course_lecture:read'])]
    private float $averageWatchPercentage = 0.0;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0.0, max: 5.0)]
    #[Groups(['course_lecture:read'])]
    private ?float $rating = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['course_lecture:read'])]
    private int $ratingCount = 0;

    // === ORDERING & DURATION ===

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    protected int $viewOrder = 0;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Assert\Range(min: 0, max: 86400, notInRangeMessage: 'course.lecture.validation.duration_max')]
    #[Groups(['course_lecture:read', 'course_lecture:write', 'student:read'])]
    protected int $durationSeconds = 0;

    // Deprecated: kept for backward compatibility, will be removed
    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $lengthSeconds = null;

    // === ADAPTIVE LEARNING & GAMIFICATION ===

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: ['beginner', 'intermediate', 'advanced'])]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    private string $difficultyLevel = 'intermediate';

    #[ORM\Column(type: 'json')]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    private array $tags = [];

    #[ORM\Column(type: 'json')]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    private array $skillsCovered = [];

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    private int $pointsValue = 10;

    #[ORM\Column(type: 'json')]
    #[Groups(['course_lecture:read'])]
    private array $badges = [];

    // === RELATIONSHIPS ===

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['course_lecture:read'])]
    private Organization $organization;

    #[ORM\ManyToOne(targetEntity: CourseModule::class, inversedBy: 'lectures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['course_lecture:read'])]
    protected CourseModule $courseModule;

    #[ORM\OneToMany(mappedBy: 'lecture', targetEntity: StudentLecture::class, cascade: ['persist', 'remove'])]
    protected Collection $studentLectures;

    #[ORM\OneToMany(mappedBy: 'currentLecture', targetEntity: StudentCourse::class)]
    protected Collection $studentCoursesOnThisLecture;

    public function __construct()
    {
        parent::__construct();
        $this->studentLectures = new ArrayCollection();
        $this->studentCoursesOnThisLecture = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

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

    public function getVideoFileName(): ?string
    {
        return $this->videoFileName;
    }

    public function setVideoFileName(?string $videoFileName): self
    {
        $this->videoFileName = $videoFileName;
        return $this;
    }

    public function getVideoPath(): ?string
    {
        return $this->videoPath;
    }

    public function setVideoPath(?string $videoPath): self
    {
        $this->videoPath = $videoPath;
        return $this;
    }

    public function getProcessingStatus(): string
    {
        return $this->processingStatus;
    }

    public function setProcessingStatus(string $processingStatus): self
    {
        $this->processingStatus = $processingStatus;
        return $this;
    }

    public function getProcessingStep(): ?string
    {
        return $this->processingStep;
    }

    public function setProcessingStep(?string $processingStep): self
    {
        $this->processingStep = $processingStep;
        return $this;
    }

    public function getProcessingPercentage(): int
    {
        return $this->processingPercentage;
    }

    public function setProcessingPercentage(int $processingPercentage): self
    {
        $this->processingPercentage = $processingPercentage;
        return $this;
    }

    public function getProcessingError(): ?string
    {
        return $this->processingError;
    }

    public function setProcessingError(?string $processingError): self
    {
        $this->processingError = $processingError;
        return $this;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): self
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function getViewOrder(): int
    {
        return $this->viewOrder;
    }

    public function setViewOrder(int $viewOrder): self
    {
        $this->viewOrder = $viewOrder;
        return $this;
    }

    // === BASIC GETTERS/SETTERS ===

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;
        if ($published && $this->publishedAt === null) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isFree(): bool
    {
        return $this->free;
    }

    public function setFree(bool $free): self
    {
        $this->free = $free;
        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    // === VIDEO GETTERS/SETTERS ===

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

    public function setVideoUrl(?string $videoUrl): self
    {
        $this->videoUrl = $videoUrl;
        return $this;
    }

    public function getVideoType(): string
    {
        return $this->videoType;
    }

    public function setVideoType(string $videoType): self
    {
        $this->videoType = $videoType;
        return $this;
    }

    public function getVideoResolution(): ?string
    {
        return $this->videoResolution;
    }

    public function setVideoResolution(?string $videoResolution): self
    {
        $this->videoResolution = $videoResolution;
        return $this;
    }

    public function getVideoSizeBytes(): ?int
    {
        return $this->videoSizeBytes;
    }

    public function setVideoSizeBytes(?int $videoSizeBytes): self
    {
        $this->videoSizeBytes = $videoSizeBytes;
        return $this;
    }

    // === ACCESSIBILITY GETTERS/SETTERS ===

    public function getTranscript(): ?string
    {
        return $this->transcript;
    }

    public function setTranscript(?string $transcript): self
    {
        $this->transcript = $transcript;
        return $this;
    }

    public function getSubtitleUrl(): ?string
    {
        return $this->subtitleUrl;
    }

    public function setSubtitleUrl(?string $subtitleUrl): self
    {
        $this->subtitleUrl = $subtitleUrl;
        return $this;
    }

    public function getSubtitleLanguage(): ?string
    {
        return $this->subtitleLanguage;
    }

    public function setSubtitleLanguage(?string $subtitleLanguage): self
    {
        $this->subtitleLanguage = $subtitleLanguage;
        return $this;
    }

    // === LEARNING RESOURCES GETTERS/SETTERS ===

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function addAttachment(array $attachment): self
    {
        $this->attachments[] = $attachment;
        return $this;
    }

    public function getExternalLinks(): array
    {
        return $this->externalLinks;
    }

    public function setExternalLinks(array $externalLinks): self
    {
        $this->externalLinks = $externalLinks;
        return $this;
    }

    public function addExternalLink(array $link): self
    {
        $this->externalLinks[] = $link;
        return $this;
    }

    public function getLearningObjectives(): array
    {
        return $this->learningObjectives;
    }

    public function setLearningObjectives(array $learningObjectives): self
    {
        $this->learningObjectives = $learningObjectives;
        return $this;
    }

    public function getPrerequisites(): ?string
    {
        return $this->prerequisites;
    }

    public function setPrerequisites(?string $prerequisites): self
    {
        $this->prerequisites = $prerequisites;
        return $this;
    }

    // === ANALYTICS GETTERS/SETTERS ===

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function incrementViewCount(): self
    {
        $this->viewCount++;
        return $this;
    }

    public function getCompletionCount(): int
    {
        return $this->completionCount;
    }

    public function incrementCompletionCount(): self
    {
        $this->completionCount++;
        return $this;
    }

    public function getAverageWatchPercentage(): float
    {
        return $this->averageWatchPercentage;
    }

    public function setAverageWatchPercentage(float $averageWatchPercentage): self
    {
        $this->averageWatchPercentage = $averageWatchPercentage;
        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function getRatingCount(): int
    {
        return $this->ratingCount;
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

    // === DURATION GETTERS/SETTERS ===

    public function getDurationSeconds(): int
    {
        return $this->durationSeconds;
    }

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

    // === ADAPTIVE LEARNING GETTERS/SETTERS ===

    public function getDifficultyLevel(): string
    {
        return $this->difficultyLevel;
    }

    public function setDifficultyLevel(string $difficultyLevel): self
    {
        $this->difficultyLevel = $difficultyLevel;
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getSkillsCovered(): array
    {
        return $this->skillsCovered;
    }

    public function setSkillsCovered(array $skillsCovered): self
    {
        $this->skillsCovered = $skillsCovered;
        return $this;
    }

    public function getPointsValue(): int
    {
        return $this->pointsValue;
    }

    public function setPointsValue(int $pointsValue): self
    {
        $this->pointsValue = $pointsValue;
        return $this;
    }

    public function getBadges(): array
    {
        return $this->badges;
    }

    public function setBadges(array $badges): self
    {
        $this->badges = $badges;
        return $this;
    }

    // === RELATIONSHIP GETTERS/SETTERS ===

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    public function getCourseModule(): CourseModule
    {
        return $this->courseModule;
    }

    public function setCourseModule(?CourseModule $courseModule): self
    {
        $this->courseModule = $courseModule;
        return $this;
    }

    /**
     * @return Collection<int, StudentLecture>
     */
    public function getStudentLectures(): Collection
    {
        return $this->studentLectures;
    }

    public function addStudentLecture(StudentLecture $studentLecture): self
    {
        if (!$this->studentLectures->contains($studentLecture)) {
            $this->studentLectures->add($studentLecture);
            $studentLecture->setCourseLecture($this);
        }
        return $this;
    }

    public function removeStudentLecture(StudentLecture $studentLecture): self
    {
        if ($this->studentLectures->removeElement($studentLecture)) {
            if ($studentLecture->getCourseLecture() === $this) {
                $studentLecture->setCourseLecture(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, StudentCourse>
     */
    public function getStudentCoursesOnThisLecture(): Collection
    {
        return $this->studentCoursesOnThisLecture;
    }

    public function addStudentCourseOnThisLecture(StudentCourse $studentCourse): self
    {
        if (!$this->studentCoursesOnThisLecture->contains($studentCourse)) {
            $this->studentCoursesOnThisLecture->add($studentCourse);
            $studentCourse->setCurrentLecture($this);
        }
        return $this;
    }

    public function removeStudentCourseOnThisLecture(StudentCourse $studentCourse): self
    {
        if ($this->studentCoursesOnThisLecture->removeElement($studentCourse)) {
            if ($studentCourse->getCurrentLecture() === $this) {
                $studentCourse->setCurrentLecture(null);
            }
        }
        return $this;
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

    public function __toString(): string
    {
        return $this->name;
    }
}