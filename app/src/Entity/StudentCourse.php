<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StudentCourseRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StudentCourseRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['student_course:read']],
    denormalizationContext: ['groups' => ['student_course:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/student-courses',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['student_course:read', 'audit:read']]
        )
    ]
)]
class StudentCourse extends EntityBase
{

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['student_course:read', 'student_course:write'])]
    protected \DateTimeImmutable $enrolledAt;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['student_course:read', 'student_course:write'])]
    protected bool $active = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['student_course:read', 'student_course:write'])]
    protected ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['student_course:read', 'student_course:write'])]
    protected ?\DateTimeImmutable $lastDate = null;

    #[ORM\Column(type: 'float')]
    #[Assert\PositiveOrZero]
    #[Groups(['student_course:read', 'student_course:write'])]
    protected float $progressSeconds = 0.0;

    #[ORM\Column(type: 'float')]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['student_course:read', 'student_course:write'])]
    protected float $progressPercentage = 0.0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['student_course:read', 'student_course:write'])]
    protected ?\DateTimeImmutable $completedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'studentCourses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['student_course:read'])]
    protected User $student;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'studentCourses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['student_course:read'])]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: CourseLecture::class, inversedBy: 'studentCoursesOnThisLecture')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['student_course:read'])]
    protected ?CourseLecture $currentLecture = null;

    public function __construct()
    {
        parent::__construct();
        $this->enrolledAt = new \DateTimeImmutable();
    }

    public function getEnrolledAt(): \DateTimeImmutable
    {
        return $this->enrolledAt;
    }

    public function setEnrolledAt(\DateTimeImmutable $enrolledAt): self
    {
        $this->enrolledAt = $enrolledAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getLastDate(): ?\DateTimeImmutable
    {
        return $this->lastDate;
    }

    public function setLastDate(?\DateTimeImmutable $lastDate): self
    {
        $this->lastDate = $lastDate;
        return $this;
    }

    public function getProgressSeconds(): float
    {
        return $this->progressSeconds;
    }

    public function setProgressSeconds(float $progressSeconds): self
    {
        $this->progressSeconds = $progressSeconds;
        return $this;
    }

    public function getProgressPercentage(): float
    {
        return $this->progressPercentage;
    }

    public function setProgressPercentage(float $progressPercentage): self
    {
        $this->progressPercentage = $progressPercentage;
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

    public function getStudent(): User
    {
        return $this->student;
    }

    public function setStudent(User $student): self
    {
        $this->student = $student;
        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;
        return $this;
    }

    public function getCurrentLecture(): ?CourseLecture
    {
        return $this->currentLecture;
    }

    public function setCurrentLecture(?CourseLecture $currentLecture): self
    {
        $this->currentLecture = $currentLecture;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->student->getName(), $this->course->getName());
    }
}