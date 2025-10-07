<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CourseRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['course:read']],
    denormalizationContext: ['groups' => ['course:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/courses',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['course:read', 'audit:read']]
        )
    ]
)]
class Course extends EntityBase
{

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    protected string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    protected ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['course:read', 'course:write'])]
    protected bool $active = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    protected ?\DateTimeImmutable $releaseDate = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['course:read'])]
    protected int $totalLengthSeconds = 0;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'courses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['course:read'])]
    protected Organization $organization;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'ownedCourses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['course:read'])]
    protected User $owner;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: CourseModule::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['viewOrder' => 'ASC'])]
    #[Groups(['course:read'])]
    protected Collection $modules;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: StudentCourse::class, cascade: ['persist', 'remove'])]
    protected Collection $studentCourses;

    public function __construct()
    {
        parent::__construct();
        $this->modules = new ArrayCollection();
        $this->studentCourses = new ArrayCollection();
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

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getReleaseDate(): ?\DateTimeImmutable
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeImmutable $releaseDate): self
    {
        $this->releaseDate = $releaseDate;
        return $this;
    }

    public function getTotalLengthSeconds(): int
    {
        return $this->totalLengthSeconds;
    }

    public function calculateTotalLengthSeconds(): void
    {
        $total = 0;
        foreach ($this->modules as $module) {
            $total += $module->getTotalLengthSeconds();
        }
        $this->totalLengthSeconds = $total;
    }

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

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @return Collection<int, CourseModule>
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(CourseModule $module): self
    {
        if (!$this->modules->contains($module)) {
            $this->modules->add($module);
            $module->setCourse($this);
        }
        return $this;
    }

    public function removeModule(CourseModule $module): self
    {
        if ($this->modules->removeElement($module)) {
            if ($module->getCourse() === $this) {
                $module->setCourse(null);
            }
        }
        return $this;
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

    /**
     * @return Collection<int, StudentCourse>
     */
    public function getStudentCourses(): Collection
    {
        return $this->studentCourses;
    }

    public function addStudentCourse(StudentCourse $studentCourse): self
    {
        if (!$this->studentCourses->contains($studentCourse)) {
            $this->studentCourses->add($studentCourse);
            $studentCourse->setCourse($this);
        }
        return $this;
    }

    public function removeStudentCourse(StudentCourse $studentCourse): self
    {
        if ($this->studentCourses->removeElement($studentCourse)) {
            if ($studentCourse->getCourse() === $this) {
                $studentCourse->setCourse(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}