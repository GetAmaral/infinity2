<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CourseModuleRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CourseModuleRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['course_module:read']],
    denormalizationContext: ['groups' => ['course_module:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/course-modules',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['course_module:read', 'audit:read']]
        )
    ]
)]
class CourseModule extends EntityBase
{

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['course_module:read', 'course_module:write', 'course:read'])]
    protected string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected bool $active = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected ?\DateTimeImmutable $releaseDate = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected int $viewOrder = 0;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['course_module:read'])]
    protected int $totalLengthSeconds = 0;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'modules')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['course_module:read'])]
    protected Course $course;

    #[ORM\OneToMany(targetEntity: CourseLecture::class, mappedBy: 'courseModule', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['viewOrder' => 'ASC'])]
    #[Groups(['course_module:read'])]
    protected Collection $lectures;

    public function __construct()
    {
        parent::__construct();
        $this->lectures = new ArrayCollection();
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

    public function getViewOrder(): int
    {
        return $this->viewOrder;
    }

    public function setViewOrder(int $viewOrder): self
    {
        $this->viewOrder = $viewOrder;
        return $this;
    }

    public function getTotalLengthSeconds(): int
    {
        return $this->totalLengthSeconds;
    }

    public function calculateTotalLengthSeconds(): void
    {
        $total = 0;
        foreach ($this->lectures as $lecture) {
            $total += $lecture->getLengthSeconds();
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

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;
        return $this;
    }

    /**
     * @return Collection<int, CourseLecture>
     */
    public function getLectures(): Collection
    {
        return $this->lectures;
    }

    public function addLecture(CourseLecture $lecture): self
    {
        if (!$this->lectures->contains($lecture)) {
            $this->lectures->add($lecture);
            $lecture->setCourseModule($this);
        }
        return $this;
    }

    public function removeLecture(CourseLecture $lecture): self
    {
        if ($this->lectures->removeElement($lecture)) {
            if ($lecture->getCourseModule() === $this) {
                $lecture->setCourseModule(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
