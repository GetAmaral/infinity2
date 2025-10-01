<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    operations: [
        new Get(security: "is_granted('ROLE_USER') and object == user"),
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_USER') and object == user or is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        // Admin endpoint with audit information
        new GetCollection(
            uriTemplate: '/admin/users',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['user:read', 'audit:read']]
        )
    ]
)]
class User extends EntityBase implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'user:write'])]
    protected string $name = '';

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'user:write'])]
    protected string $email = '';

    #[ORM\Column(length: 255)]
    #[Groups(['user:write'])] // Password only for write operations
    protected string $password = '';

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_roles')]
    #[Groups(['user:read'])]
    protected Collection $roles;

    #[ORM\Column(type: 'boolean')]
    protected bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $verificationToken = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $apiToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $apiTokenExpiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(type: 'integer')]
    protected int $failedLoginAttempts = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $lockedUntil = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    protected ?Organization $organization = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read'])]
    protected ?array $uiSettings = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read'])]
    protected ?array $listPreferences = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Course::class)]
    protected Collection $ownedCourses;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: StudentCourse::class)]
    protected Collection $studentCourses;

    public function __construct()
    {
        parent::__construct();
        $this->roles = new ArrayCollection();
        $this->ownedCourses = new ArrayCollection();
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    // UserInterface methods
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        foreach ($this->roles as $role) {
            $roles[] = 'ROLE_' . strtoupper($role->getName());
        }
        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // No sensitive data to erase
    }

    // PasswordAuthenticatedUserInterface method
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    // Role management
    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }
        return $this;
    }

    public function removeRole(Role $role): self
    {
        $this->roles->removeElement($role);
        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRoleEntities(): Collection
    {
        return $this->roles;
    }

    public function hasRole(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->getName() === $roleName) {
                return true;
            }
        }
        return false;
    }

    public function hasPermission(string $permission): bool
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    // Verification methods
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        $this->verificationToken = $verificationToken;
        return $this;
    }

    // API Token methods
    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): self
    {
        $this->apiToken = $apiToken;
        return $this;
    }

    public function getApiTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->apiTokenExpiresAt;
    }

    public function setApiTokenExpiresAt(?\DateTimeImmutable $apiTokenExpiresAt): self
    {
        $this->apiTokenExpiresAt = $apiTokenExpiresAt;
        return $this;
    }

    public function isApiTokenValid(): bool
    {
        if (!$this->apiToken || !$this->apiTokenExpiresAt) {
            return false;
        }
        return $this->apiTokenExpiresAt > new \DateTimeImmutable();
    }

    public function generateApiToken(int $validityDays = 30): self
    {
        $this->apiToken = bin2hex(random_bytes(32));
        $this->apiTokenExpiresAt = (new \DateTimeImmutable())->modify("+{$validityDays} days");
        return $this;
    }

    public function revokeApiToken(): self
    {
        $this->apiToken = null;
        $this->apiTokenExpiresAt = null;
        return $this;
    }

    // Security methods
    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function recordSuccessfulLogin(): self
    {
        $this->lastLoginAt = new \DateTimeImmutable();
        $this->failedLoginAttempts = 0;
        $this->lockedUntil = null;
        return $this;
    }

    public function getFailedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function incrementFailedLoginAttempts(): self
    {
        $this->failedLoginAttempts++;

        // Lock account after 5 failed attempts for 15 minutes
        if ($this->failedLoginAttempts >= 5) {
            $this->lockedUntil = (new \DateTimeImmutable())->modify('+15 minutes');
        }

        return $this;
    }

    public function resetFailedLoginAttempts(): self
    {
        $this->failedLoginAttempts = 0;
        $this->lockedUntil = null;
        return $this;
    }

    public function getLockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function isLocked(): bool
    {
        if (!$this->lockedUntil) {
            return false;
        }

        if ($this->lockedUntil <= new \DateTimeImmutable()) {
            // Lock expired, reset counters
            $this->resetFailedLoginAttempts();
            return false;
        }

        return true;
    }

    public function getUiSettings(): ?array
    {
        return $this->uiSettings;
    }

    public function setUiSettings(?array $uiSettings): self
    {
        $this->uiSettings = $uiSettings;
        return $this;
    }

    public function getListPreferences(): ?array
    {
        return $this->listPreferences;
    }

    public function setListPreferences(?array $listPreferences): self
    {
        $this->listPreferences = $listPreferences;
        return $this;
    }

    /**
     * Get a specific list preference value
     */
    public function getListPreference(string $key, mixed $default = null): mixed
    {
        if ($this->listPreferences === null) {
            return $default;
        }
        return $this->listPreferences[$key] ?? $default;
    }

    /**
     * Set a specific list preference value
     */
    public function setListPreference(string $key, mixed $value): self
    {
        if ($this->listPreferences === null) {
            $this->listPreferences = [];
        }
        $this->listPreferences[$key] = $value;
        return $this;
    }

    /**
     * Get a specific UI setting value
     */
    public function getUiSetting(string $key, mixed $default = null): mixed
    {
        return $this->uiSettings[$key] ?? $default;
    }

    /**
     * Set a specific UI setting value
     */
    public function setUiSetting(string $key, mixed $value): self
    {
        if ($this->uiSettings === null) {
            $this->uiSettings = [];
        }
        $this->uiSettings[$key] = $value;
        return $this;
    }

    /**
     * Merge UI settings with existing ones
     */
    public function mergeUiSettings(array $settings): self
    {
        if ($this->uiSettings === null) {
            $this->uiSettings = [];
        }
        $this->uiSettings = array_merge($this->uiSettings, $settings);
        return $this;
    }

    /**
     * Get default UI settings structure
     */
    public function getDefaultUiSettings(): array
    {
        return [
            'theme' => 'dark',
            'locale' => 'en',
            'sidebar_collapsed' => false,
            'notifications_enabled' => true,
            'auto_save' => true,
            'animations_enabled' => true,
            'dashboard_layout' => 'grid',
            'items_per_page' => 25,
            'timezone' => 'UTC'
        ];
    }

    /**
     * Initialize UI settings with defaults if not set
     */
    public function initializeUiSettings(): self
    {
        if ($this->uiSettings === null) {
            $this->uiSettings = $this->getDefaultUiSettings();
        }
        return $this;
    }

    /**
     * @return Collection<int, Course>
     */
    public function getOwnedCourses(): Collection
    {
        return $this->ownedCourses;
    }

    public function addOwnedCourse(Course $course): self
    {
        if (!$this->ownedCourses->contains($course)) {
            $this->ownedCourses->add($course);
            $course->setOwner($this);
        }
        return $this;
    }

    public function removeOwnedCourse(Course $course): self
    {
        if ($this->ownedCourses->removeElement($course)) {
            if ($course->getOwner() === $this) {
                $course->setOwner(null);
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
            $studentCourse->setStudent($this);
        }
        return $this;
    }

    public function removeStudentCourse(StudentCourse $studentCourse): self
    {
        if ($this->studentCourses->removeElement($studentCourse)) {
            if ($studentCourse->getStudent() === $this) {
                $studentCourse->setStudent(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?: $this->email;
    }
}