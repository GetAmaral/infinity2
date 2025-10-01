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
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['student_lecture:read'])]
    private User $student;

    #[ORM\ManyToOne(targetEntity: CourseLecture::class, inversedBy: 'studentLectures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['student_lecture:read'])]
    private CourseLecture $lecture;

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

    #[ORM\Column(type: 'boolean')]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private bool $reached25Percent = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private bool $reached50Percent = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['student_lecture:read', 'student_lecture:write'])]
    private bool $reached75Percent = false;

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

    public function isReached25Percent(): bool
    {
        return $this->reached25Percent;
    }

    public function setReached25Percent(bool $reached25Percent): self
    {
        $this->reached25Percent = $reached25Percent;
        return $this;
    }

    public function isReached50Percent(): bool
    {
        return $this->reached50Percent;
    }

    public function setReached50Percent(bool $reached50Percent): self
    {
        $this->reached50Percent = $reached50Percent;
        return $this;
    }

    public function isReached75Percent(): bool
    {
        return $this->reached75Percent;
    }

    public function setReached75Percent(bool $reached75Percent): self
    {
        $this->reached75Percent = $reached75Percent;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s',
            $this->student->getName(),
            $this->lecture->getName()
        );
    }
}