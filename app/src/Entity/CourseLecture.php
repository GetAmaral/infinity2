<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CourseLectureRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: CourseLectureRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[ApiResource(
    normalizationContext: ['groups' => ['course_lecture:read']],
    denormalizationContext: ['groups' => ['course_lecture:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/course-lectures',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['course_lecture:read', 'audit:read']]
        )
    ]
)]
class CourseLecture extends EntityBase
{

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['course_lecture:read', 'course_lecture:write', 'course:read'])]
    protected string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    protected ?string $description = null;

    #[Vich\UploadableField(mapping: 'lecture_videos', fileNameProperty: 'videoFileName')]
    private ?File $videoFile = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['course_lecture:read'])]
    private ?string $videoFileName = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['course_lecture:read'])]
    private ?string $videoPath = null;

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

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    protected int $viewOrder = 0;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['course_lecture:read', 'course_lecture:write'])]
    protected int $lengthSeconds = 0;

    #[ORM\ManyToOne(targetEntity: CourseModule::class, inversedBy: 'lectures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['course_lecture:read'])]
    protected CourseModule $courseModule;

    #[ORM\OneToMany(mappedBy: 'courseLecture', targetEntity: StudentLecture::class, cascade: ['persist', 'remove'])]
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

    public function getLengthSeconds(): int
    {
        return $this->lengthSeconds;
    }

    public function setLengthSeconds(int $lengthSeconds): self
    {
        $this->lengthSeconds = $lengthSeconds;
        return $this;
    }

    public function getLengthFormatted(): string
    {
        if ($this->lengthSeconds < 60) {
            return $this->lengthSeconds . ' s';
        }

        if ($this->lengthSeconds < 3600) {
            $minutes = (int)floor($this->lengthSeconds / 60);
            return $minutes . ' m';
        }

        $hours = (int)floor($this->lengthSeconds / 3600);
        $minutes = (int)floor(($this->lengthSeconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
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