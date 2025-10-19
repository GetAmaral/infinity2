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
#[ORM\Index(name: 'idx_organization_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_organization_is_active', columns: ['is_active'])]
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

    // Subscription Management
    #[ORM\Column(length: 50, options: ['default' => 'free'])]
    #[Groups(['organization:read', 'organization:write'])]
    protected string $subscriptionPlan = 'free';

    #[ORM\Column(length: 50, options: ['default' => 'active'])]
    #[Groups(['organization:read', 'organization:write'])]
    protected string $subscriptionStatus = 'active';

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?\DateTimeImmutable $subscriptionEndDate = null;

    // Billing Configuration
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $billingEmail = null;

    #[ORM\Column(type: 'integer', options: ['default' => 10])]
    #[Groups(['organization:read', 'organization:write'])]
    protected int $maxUsers = 10;

    #[ORM\Column(type: 'bigint', options: ['default' => 10737418240])]
    #[Groups(['organization:read', 'organization:write'])]
    protected int $storageLimit = 10737418240; // 10GB in bytes

    // Regional Settings
    #[ORM\Column(length: 100, options: ['default' => 'UTC'])]
    #[Groups(['organization:read', 'organization:write'])]
    protected string $timezone = 'UTC';

    #[ORM\Column(length: 10, options: ['default' => 'en'])]
    #[Groups(['organization:read', 'organization:write'])]
    protected string $defaultLocale = 'en';

    #[ORM\Column(length: 10, options: ['default' => 'USD'])]
    #[Groups(['organization:read', 'organization:write'])]
    protected string $defaultCurrency = 'USD';

    // GDPR Compliance
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['organization:read', 'organization:write'])]
    protected bool $gdprEnabled = true;

    #[ORM\Column(type: 'integer', options: ['default' => 365])]
    #[Groups(['organization:read', 'organization:write'])]
    protected int $dataRetentionDays = 365;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: User::class)]
    #[Groups(['organization:read'])]
    protected Collection $users;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: Course::class)]
    #[Groups(['organization:read'])]
    protected Collection $courses;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: StudentCourse::class)]
    #[Groups(['organization:read'])]
    protected Collection $studentCourses;

    public function __construct()
    {
        parent::__construct();
        $this->users = new ArrayCollection();
        $this->courses = new ArrayCollection();
        $this->studentCourses = new ArrayCollection();
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
            $studentCourse->setOrganization($this);
        }
        return $this;
    }

    public function removeStudentCourse(StudentCourse $studentCourse): self
    {
        if ($this->studentCourses->removeElement($studentCourse)) {
            if ($studentCourse->getOrganization() === $this) {
                $studentCourse->setOrganization(null);
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

    // Subscription Management Getters/Setters
    public function getSubscriptionPlan(): string
    {
        return $this->subscriptionPlan;
    }

    public function setSubscriptionPlan(string $subscriptionPlan): self
    {
        $this->subscriptionPlan = $subscriptionPlan;
        return $this;
    }

    public function getSubscriptionStatus(): string
    {
        return $this->subscriptionStatus;
    }

    public function setSubscriptionStatus(string $subscriptionStatus): self
    {
        $this->subscriptionStatus = $subscriptionStatus;
        return $this;
    }

    public function getSubscriptionEndDate(): ?\DateTimeImmutable
    {
        return $this->subscriptionEndDate;
    }

    public function setSubscriptionEndDate(?\DateTimeImmutable $subscriptionEndDate): self
    {
        $this->subscriptionEndDate = $subscriptionEndDate;
        return $this;
    }

    public function isSubscriptionActive(): bool
    {
        if ($this->subscriptionStatus !== 'active') {
            return false;
        }

        if ($this->subscriptionEndDate === null) {
            return true;
        }

        return $this->subscriptionEndDate > new \DateTimeImmutable();
    }

    // Billing Configuration Getters/Setters
    public function getBillingEmail(): ?string
    {
        return $this->billingEmail;
    }

    public function setBillingEmail(?string $billingEmail): self
    {
        $this->billingEmail = $billingEmail;
        return $this;
    }

    public function getMaxUsers(): int
    {
        return $this->maxUsers;
    }

    public function setMaxUsers(int $maxUsers): self
    {
        $this->maxUsers = $maxUsers;
        return $this;
    }

    public function getStorageLimit(): int
    {
        return $this->storageLimit;
    }

    public function setStorageLimit(int $storageLimit): self
    {
        $this->storageLimit = $storageLimit;
        return $this;
    }

    public function getStorageLimitInGB(): float
    {
        return $this->storageLimit / 1073741824; // Convert bytes to GB
    }

    public function setStorageLimitInGB(float $gigabytes): self
    {
        $this->storageLimit = (int)($gigabytes * 1073741824);
        return $this;
    }

    // Regional Settings Getters/Setters
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function setDefaultLocale(string $defaultLocale): self
    {
        $this->defaultLocale = $defaultLocale;
        return $this;
    }

    public function getDefaultCurrency(): string
    {
        return $this->defaultCurrency;
    }

    public function setDefaultCurrency(string $defaultCurrency): self
    {
        $this->defaultCurrency = $defaultCurrency;
        return $this;
    }

    // GDPR Compliance Getters/Setters
    public function isGdprEnabled(): bool
    {
        return $this->gdprEnabled;
    }

    public function setGdprEnabled(bool $gdprEnabled): self
    {
        $this->gdprEnabled = $gdprEnabled;
        return $this;
    }

    public function getDataRetentionDays(): int
    {
        return $this->dataRetentionDays;
    }

    public function setDataRetentionDays(int $dataRetentionDays): self
    {
        $this->dataRetentionDays = $dataRetentionDays;
        return $this;
    }

    public function getDataRetentionDate(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify("-{$this->dataRetentionDays} days");
    }

}