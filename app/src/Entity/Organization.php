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
#[ORM\Index(name: 'idx_organization_active', columns: ['active'])]
#[ORM\Index(name: 'idx_organization_subscription_status', columns: ['subscription_status'])]
#[ORM\Index(name: 'idx_organization_domain', columns: ['domain'])]
#[ORM\Index(name: 'idx_organization_verified', columns: ['verified'])]
#[ApiResource(
    normalizationContext: ['groups' => ['organization:read']],
    denormalizationContext: ['groups' => ['organization:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/organizations',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['organization:read', 'audit:read']]
        ),
        new \ApiPlatform\Metadata\Get(
            uriTemplate: '/admin/organizations/{id}',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['organization:read', 'audit:read']]
        ),
        new \ApiPlatform\Metadata\Post(
            uriTemplate: '/admin/organizations',
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['organization:write']]
        ),
        new \ApiPlatform\Metadata\Put(
            uriTemplate: '/admin/organizations/{id}',
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['organization:write']]
        ),
        new \ApiPlatform\Metadata\Patch(
            uriTemplate: '/admin/organizations/{id}',
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['organization:write']]
        ),
        new \ApiPlatform\Metadata\Delete(
            uriTemplate: '/admin/organizations/{id}',
            security: "is_granted('ROLE_ADMIN')"
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

    // Contact Information
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $domain = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $phone = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $fax = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $website = null;

    // Address Information
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $addressLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $addressLine2 = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $state = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $postalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $country = null;

    // Business Information
    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $industry = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $organizationType = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?int $employeeCount = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $annualRevenue = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?string $taxId = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    protected ?\DateTimeImmutable $foundedDate = null;

    // Status & Verification
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['organization:read', 'organization:write'])]
    protected bool $active = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['organization:read', 'organization:write'])]
    protected bool $verified = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['organization:read'])]
    protected ?\DateTimeImmutable $verifiedAt = null;

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
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    // Contact Information Getters/Setters
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(?string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(?string $fax): self
    {
        $this->fax = $fax;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;
        return $this;
    }

    // Address Information Getters/Setters
    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(?string $addressLine1): self
    {
        $this->addressLine1 = $addressLine1;
        return $this;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $addressLine2): self
    {
        $this->addressLine2 = $addressLine2;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->addressLine1,
            $this->addressLine2,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country
        ]);
        return implode(', ', $parts);
    }

    // Business Information Getters/Setters
    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    public function setIndustry(?string $industry): self
    {
        $this->industry = $industry;
        return $this;
    }

    public function getOrganizationType(): ?string
    {
        return $this->organizationType;
    }

    public function setOrganizationType(?string $organizationType): self
    {
        $this->organizationType = $organizationType;
        return $this;
    }

    public function getEmployeeCount(): ?int
    {
        return $this->employeeCount;
    }

    public function setEmployeeCount(?int $employeeCount): self
    {
        $this->employeeCount = $employeeCount;
        return $this;
    }

    public function getAnnualRevenue(): ?string
    {
        return $this->annualRevenue;
    }

    public function setAnnualRevenue(?string $annualRevenue): self
    {
        $this->annualRevenue = $annualRevenue;
        return $this;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTaxId(?string $taxId): self
    {
        $this->taxId = $taxId;
        return $this;
    }

    public function getFoundedDate(): ?\DateTimeImmutable
    {
        return $this->foundedDate;
    }

    public function setFoundedDate(?\DateTimeImmutable $foundedDate): self
    {
        $this->foundedDate = $foundedDate;
        return $this;
    }

    // Status & Verification Getters/Setters
    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;
        if ($verified && $this->verifiedAt === null) {
            $this->verifiedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): self
    {
        $this->verifiedAt = $verifiedAt;
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

    /**
     * String representation of the organization
     */
    public function __toString(): string
    {
        return $this->name ?: 'Organization#' . ($this->id ?? 'unsaved');
    }

    /**
     * Get current user count
     */
    public function getUserCount(): int
    {
        return $this->users->count();
    }

    /**
     * Check if organization can add more users
     */
    public function canAddUsers(): bool
    {
        return $this->getUserCount() < $this->maxUsers;
    }

    /**
     * Get remaining user slots
     */
    public function getRemainingUserSlots(): int
    {
        return max(0, $this->maxUsers - $this->getUserCount());
    }

}