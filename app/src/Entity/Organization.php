<?php

namespace App\Entity;

use App\Repository\OrganizationRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['organization:read']],
    denormalizationContext: ['groups' => ['organization:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/organizations',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['organization:read', 'audit:read']]
        )
    ]
)]
class Organization extends EntityBase
{

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['organization:read', 'organization:write'])]
    protected string $name = '';

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[a-z0-9\-]+$/',
        message: 'The slug can only contain lowercase letters, numbers, and hyphens.'
    )]
    #[Groups(['organization:read', 'organization:write'])]
    protected string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $logoPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $logoPathDark = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['organization:read', 'organization:write'])]
    protected bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: User::class)]
    #[Groups(['organization:read'])]
    protected Collection $users;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: Course::class)]
    #[Groups(['organization:read'])]
    protected Collection $courses;

    public function __construct()
    {
        parent::__construct();
        $this->users = new ArrayCollection();
        $this->courses = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        // Auto-generate slug if not set
        if (empty($this->slug)) {
            $this->slug = $this->generateSlugFromName($name);
        }
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = strtolower($slug);
        return $this;
    }

    /**
     * Generate a URL-friendly slug from organization name
     */
    private function generateSlugFromName(string $name): string
    {
        // Convert to lowercase
        $slug = strtolower($name);

        // Replace spaces and special characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');

        // Remove consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug);

        return $slug;
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

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): self
    {
        $this->logoPath = $logoPath;
        return $this;
    }

    public function getLogoPathDark(): ?string
    {
        return $this->logoPathDark;
    }

    public function setLogoPathDark(?string $logoPathDark): self
    {
        $this->logoPathDark = $logoPathDark;
        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setOrganization($this);
        }
        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            if ($user->getOrganization() === $this) {
                $user->setOrganization(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Course>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function addCourse(Course $course): self
    {
        if (!$this->courses->contains($course)) {
            $this->courses->add($course);
            $course->setOrganization($this);
        }
        return $this;
    }

    public function removeCourse(Course $course): self
    {
        if ($this->courses->removeElement($course)) {
            if ($course->getOrganization() === $this) {
                $course->setOrganization(null);
            }
        }
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

}