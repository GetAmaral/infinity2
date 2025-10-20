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
#[ORM\Index(name: 'idx_user_email', columns: ['email'])]
#[ORM\Index(name: 'idx_user_username', columns: ['username'])]
#[ORM\Index(name: 'idx_user_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_user_two_factor_enabled', columns: ['two_factor_enabled'])]
#[ORM\Index(name: 'idx_user_password_reset_token', columns: ['password_reset_token'])]
#[ORM\Index(name: 'idx_user_session_token', columns: ['session_token'])]
#[ORM\Index(name: 'idx_user_last_password_change_at', columns: ['last_password_change_at'])]
#[ORM\Index(name: 'idx_user_password_expires_at', columns: ['password_expires_at'])]
#[ORM\Index(name: 'idx_user_must_change_password', columns: ['must_change_password'])]
#[ORM\Index(name: 'idx_user_passkey_enabled', columns: ['passkey_enabled'])]
#[ORM\Index(name: 'idx_user_email_verified_at', columns: ['email_verified_at'])]
#[ORM\Index(name: 'idx_user_department', columns: ['department'])]
#[ORM\Index(name: 'idx_user_manager_id', columns: ['manager_id'])]
#[ORM\Index(name: 'idx_user_sales_team', columns: ['sales_team'])]
#[ORM\Index(name: 'idx_user_is_agent', columns: ['is_agent'])]
#[ORM\Index(name: 'idx_user_agent_type', columns: ['agent_type'])]
#[ORM\Index(name: 'idx_user_is_active', columns: ['is_active'])]
#[ORM\Index(name: 'idx_user_deleted_at', columns: ['deleted_at'])]
#[ORM\Index(name: 'idx_user_failed_login_attempts', columns: ['failed_login_attempts'])]
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
    #[Assert\Length(max: 255)]
    #[Groups(['user:read', 'user:write'])]
    protected string $email = '';

    #[ORM\Column(length: 255)]
    // CRITICAL SECURITY: Password must NEVER be in serialization groups, NEVER api_readable
    protected string $password = '';

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_roles')]
    #[Groups(['user:read'])]
    protected Collection $roles;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:read', 'user:write'])]
    protected bool $verified = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:read', 'user:write'])]
    protected bool $termsSigned = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read'])]
    protected ?\DateTimeImmutable $termsSignedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $verificationToken = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $apiToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $apiTokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    // CRITICAL SECURITY: API key must NEVER be api_readable
    protected ?string $openAiApiKey = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read', 'audit:read'])]
    protected ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['audit:read'])]
    protected int $failedLoginAttempts = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['audit:read'])]
    protected ?\DateTimeImmutable $lockedUntil = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['user:read'])] // CRITICAL: api_writable=false - users cannot change organization
    protected ?Organization $organization = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read'])]
    protected ?array $uiSettings = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read'])]
    protected ?array $listPreferences = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Course::class)]
    protected Collection $ownedCourses;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: StudentCourse::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    protected Collection $studentCourses;

    // ===== NEW SECURITY FIELDS (2FA, Passwordless, Session Security) =====

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]
    protected bool $twoFactorEnabled = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    // CRITICAL: TOTP secret - NEVER expose via API
    protected ?string $twoFactorSecret = null;

    #[ORM\Column(type: 'json', nullable: true)]
    // CRITICAL: Backup codes - NEVER expose via API
    protected ?array $twoFactorBackupCodes = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    // CRITICAL: Password reset token - NEVER api_readable
    protected ?string $passwordResetToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['audit:read'])]
    protected ?\DateTimeImmutable $passwordResetExpiry = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    // CRITICAL: Session token - NEVER api_readable
    protected ?string $sessionToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read', 'audit:read'])]
    protected ?\DateTimeImmutable $lastPasswordChangeAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read', 'audit:read'])]
    protected ?\DateTimeImmutable $passwordExpiresAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read', 'audit:read'])]
    protected bool $mustChangePassword = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]
    protected bool $passkeyEnabled = false;

    #[ORM\Column(type: 'json', nullable: true)]
    // CRITICAL: FIDO2 credentials - NEVER expose via API
    protected ?array $passkeyCredentials = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read'])]
    protected ?\DateTimeImmutable $emailVerifiedAt = null;

    // ===== NEW CRM FIELDS =====

    #[ORM\Column(type: 'string', length: 100, nullable: true, unique: true)]
    #[Assert\Length(min: 3, max: 100)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: 'Username must contain only letters, numbers, underscores, and hyphens')]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $username = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^[+]?[0-9\s()-]+$/')]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $phone = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^[+]?[0-9\s()-]+$/')]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $mobilePhone = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $jobTitle = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $department = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true, options: ['default' => 'UTC'])]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $timezone = 'UTC';

    #[ORM\Column(type: 'string', length: 10, nullable: true, options: ['default' => 'en'])]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $locale = 'en';

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $preferredLanguage = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $emailSignature = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['user:read', 'user:write'])]
    protected bool $emailNotificationsEnabled = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]
    protected bool $smsNotificationsEnabled = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]
    protected bool $calendarSyncEnabled = false;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?array $workingHours = null;

    #[ORM\Column(type: 'string', length: 3, nullable: true, options: ['default' => 'USD'])]
    #[Assert\Length(min: 3, max: 3)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $defaultCurrency = 'USD';

    #[ORM\Column(type: 'string', length: 20, nullable: true, options: ['default' => 'Y-m-d'])]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $dateFormat = 'Y-m-d';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['user:read', 'user:write'])]
    protected ?User $manager = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $salesTeam = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $quotaAmount = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $commissionRate = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]
    protected bool $agent = false;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $agentType = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['user:read', 'user:write'])]
    protected bool $active = true;

    // ===== AUDIT FIELDS (deletedAt for soft delete) =====

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['audit:read'])]
    protected ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $avatar = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $gender = null;

    // ===== ADDITIONAL CRM FIELDS (2025 Best Practices) =====

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $title = null; // Mr., Mrs., Dr., etc.

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $middleName = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $suffix = null; // Jr., Sr., III, etc.

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $nickname = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $secondaryEmail = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $workPhone = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $homePhone = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $phoneExtension = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $fax = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $website = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $linkedinUrl = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $twitterHandle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $address = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $city = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $state = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $postalCode = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $country = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $region = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $officeLocation = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $employeeId = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?\DateTimeImmutable $hireDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?\DateTimeImmutable $terminationDate = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $employmentStatus = null; // full-time, part-time, contract, etc.

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $costCenter = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $division = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $businessUnit = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $salary = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $salaryFrequency = null; // hourly, monthly, annually

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?array $skills = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?array $certifications = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?array $languages = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $bio = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $notes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?array $tags = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['user:read'])]
    protected int $loginCount = 0;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read', 'audit:read'])]
    protected ?string $lastIpAddress = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read', 'audit:read'])]
    protected ?string $lastUserAgent = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['user:read', 'user:write'])]
    protected bool $visible = true; // Visibility in directory/team lists

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['user:read'])]
    protected int $profileCompleteness = 0; // 0-100 percentage

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read', 'audit:read'])]
    protected ?\DateTimeImmutable $lastActivityAt = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $status = null; // available, busy, away, offline, do-not-disturb

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $statusMessage = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]
    protected bool $locked = false; // Account locked by admin

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['audit:read'])]
    protected ?string $lockedReason = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['audit:read'])]
    protected ?\DateTimeImmutable $lockedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?array $customFields = null; // Flexible JSON for custom attributes

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
        return $this->verified;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;
        return $this;
    }

    // Backward compatibility alias
    public function setIsVerified(bool $verified): self
    {
        return $this->setVerified($verified);
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

    // Terms methods
    public function hasSignedTerms(): bool
    {
        return $this->termsSigned;
    }

    public function setTermsSigned(bool $termsSigned): self
    {
        $this->termsSigned = $termsSigned;
        if ($termsSigned && $this->termsSignedAt === null) {
            $this->termsSignedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getTermsSignedAt(): ?\DateTimeImmutable
    {
        return $this->termsSignedAt;
    }

    public function setTermsSignedAt(?\DateTimeImmutable $termsSignedAt): self
    {
        $this->termsSignedAt = $termsSignedAt;
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

    // OpenAI API Key methods
    public function getOpenAiApiKey(): ?string
    {
        return $this->openAiApiKey;
    }

    public function setOpenAiApiKey(?string $openAiApiKey): self
    {
        $this->openAiApiKey = $openAiApiKey;
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

    // ===== GETTERS/SETTERS FOR NEW SECURITY FIELDS =====

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function setTwoFactorEnabled(bool $twoFactorEnabled): self
    {
        $this->twoFactorEnabled = $twoFactorEnabled;
        return $this;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function setTwoFactorSecret(?string $twoFactorSecret): self
    {
        $this->twoFactorSecret = $twoFactorSecret;
        return $this;
    }

    public function getTwoFactorBackupCodes(): ?array
    {
        return $this->twoFactorBackupCodes;
    }

    public function setTwoFactorBackupCodes(?array $twoFactorBackupCodes): self
    {
        $this->twoFactorBackupCodes = $twoFactorBackupCodes;
        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): self
    {
        $this->passwordResetToken = $passwordResetToken;
        return $this;
    }

    public function getPasswordResetExpiry(): ?\DateTimeImmutable
    {
        return $this->passwordResetExpiry;
    }

    public function setPasswordResetExpiry(?\DateTimeImmutable $passwordResetExpiry): self
    {
        $this->passwordResetExpiry = $passwordResetExpiry;
        return $this;
    }

    public function generatePasswordResetToken(int $validityMinutes = 60): self
    {
        $this->passwordResetToken = bin2hex(random_bytes(32));
        $this->passwordResetExpiry = (new \DateTimeImmutable())->modify("+{$validityMinutes} minutes");
        return $this;
    }

    public function isPasswordResetTokenValid(): bool
    {
        if (!$this->passwordResetToken || !$this->passwordResetExpiry) {
            return false;
        }
        return $this->passwordResetExpiry > new \DateTimeImmutable();
    }

    public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    public function setSessionToken(?string $sessionToken): self
    {
        $this->sessionToken = $sessionToken;
        return $this;
    }

    public function getLastPasswordChangeAt(): ?\DateTimeImmutable
    {
        return $this->lastPasswordChangeAt;
    }

    public function setLastPasswordChangeAt(?\DateTimeImmutable $lastPasswordChangeAt): self
    {
        $this->lastPasswordChangeAt = $lastPasswordChangeAt;
        return $this;
    }

    public function getPasswordExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordExpiresAt;
    }

    public function setPasswordExpiresAt(?\DateTimeImmutable $passwordExpiresAt): self
    {
        $this->passwordExpiresAt = $passwordExpiresAt;
        return $this;
    }

    public function mustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    public function setMustChangePassword(bool $mustChangePassword): self
    {
        $this->mustChangePassword = $mustChangePassword;
        return $this;
    }

    public function isPasskeyEnabled(): bool
    {
        return $this->passkeyEnabled;
    }

    public function setPasskeyEnabled(bool $passkeyEnabled): self
    {
        $this->passkeyEnabled = $passkeyEnabled;
        return $this;
    }

    public function getPasskeyCredentials(): ?array
    {
        return $this->passkeyCredentials;
    }

    public function setPasskeyCredentials(?array $passkeyCredentials): self
    {
        $this->passkeyCredentials = $passkeyCredentials;
        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeImmutable $emailVerifiedAt): self
    {
        $this->emailVerifiedAt = $emailVerifiedAt;
        return $this;
    }

    // ===== GETTERS/SETTERS FOR NEW CRM FIELDS =====

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
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

    public function getMobilePhone(): ?string
    {
        return $this->mobilePhone;
    }

    public function setMobilePhone(?string $mobilePhone): self
    {
        $this->mobilePhone = $mobilePhone;
        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): self
    {
        $this->jobTitle = $jobTitle;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): self
    {
        $this->department = $department;
        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function getPreferredLanguage(): ?string
    {
        return $this->preferredLanguage;
    }

    public function setPreferredLanguage(?string $preferredLanguage): self
    {
        $this->preferredLanguage = $preferredLanguage;
        return $this;
    }

    public function getEmailSignature(): ?string
    {
        return $this->emailSignature;
    }

    public function setEmailSignature(?string $emailSignature): self
    {
        $this->emailSignature = $emailSignature;
        return $this;
    }

    public function isEmailNotificationsEnabled(): bool
    {
        return $this->emailNotificationsEnabled;
    }

    public function setEmailNotificationsEnabled(bool $emailNotificationsEnabled): self
    {
        $this->emailNotificationsEnabled = $emailNotificationsEnabled;
        return $this;
    }

    public function isSmsNotificationsEnabled(): bool
    {
        return $this->smsNotificationsEnabled;
    }

    public function setSmsNotificationsEnabled(bool $smsNotificationsEnabled): self
    {
        $this->smsNotificationsEnabled = $smsNotificationsEnabled;
        return $this;
    }

    public function isCalendarSyncEnabled(): bool
    {
        return $this->calendarSyncEnabled;
    }

    public function setCalendarSyncEnabled(bool $calendarSyncEnabled): self
    {
        $this->calendarSyncEnabled = $calendarSyncEnabled;
        return $this;
    }

    public function getWorkingHours(): ?array
    {
        return $this->workingHours;
    }

    public function setWorkingHours(?array $workingHours): self
    {
        $this->workingHours = $workingHours;
        return $this;
    }

    public function getDefaultCurrency(): ?string
    {
        return $this->defaultCurrency;
    }

    public function setDefaultCurrency(?string $defaultCurrency): self
    {
        $this->defaultCurrency = $defaultCurrency;
        return $this;
    }

    public function getDateFormat(): ?string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(?string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(?User $manager): self
    {
        $this->manager = $manager;
        return $this;
    }

    public function getSalesTeam(): ?string
    {
        return $this->salesTeam;
    }

    public function setSalesTeam(?string $salesTeam): self
    {
        $this->salesTeam = $salesTeam;
        return $this;
    }

    public function getQuotaAmount(): ?string
    {
        return $this->quotaAmount;
    }

    public function setQuotaAmount(?string $quotaAmount): self
    {
        $this->quotaAmount = $quotaAmount;
        return $this;
    }

    public function getCommissionRate(): ?string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(?string $commissionRate): self
    {
        $this->commissionRate = $commissionRate;
        return $this;
    }

    public function isAgent(): bool
    {
        return $this->agent;
    }

    public function setAgent(bool $agent): self
    {
        $this->agent = $agent;
        return $this;
    }

    // Backward compatibility alias
    public function setIsAgent(bool $agent): self
    {
        return $this->setAgent($agent);
    }

    public function getAgentType(): ?string
    {
        return $this->agentType;
    }

    public function setAgentType(?string $agentType): self
    {
        $this->agentType = $agentType;
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

    // Backward compatibility alias
    public function setIsActive(bool $active): self
    {
        return $this->setActive($active);
    }

    // ===== GETTERS/SETTERS FOR AUDIT FIELDS =====

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }


    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): self
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    // ===== GETTERS/SETTERS FOR ADDITIONAL CRM FIELDS =====

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): self
    {
        $this->middleName = $middleName;
        return $this;
    }

    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    public function setSuffix(?string $suffix): self
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function getSecondaryEmail(): ?string
    {
        return $this->secondaryEmail;
    }

    public function setSecondaryEmail(?string $secondaryEmail): self
    {
        $this->secondaryEmail = $secondaryEmail;
        return $this;
    }

    public function getWorkPhone(): ?string
    {
        return $this->workPhone;
    }

    public function setWorkPhone(?string $workPhone): self
    {
        $this->workPhone = $workPhone;
        return $this;
    }

    public function getHomePhone(): ?string
    {
        return $this->homePhone;
    }

    public function setHomePhone(?string $homePhone): self
    {
        $this->homePhone = $homePhone;
        return $this;
    }

    public function getPhoneExtension(): ?string
    {
        return $this->phoneExtension;
    }

    public function setPhoneExtension(?string $phoneExtension): self
    {
        $this->phoneExtension = $phoneExtension;
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

    public function getLinkedinUrl(): ?string
    {
        return $this->linkedinUrl;
    }

    public function setLinkedinUrl(?string $linkedinUrl): self
    {
        $this->linkedinUrl = $linkedinUrl;
        return $this;
    }

    public function getTwitterHandle(): ?string
    {
        return $this->twitterHandle;
    }

    public function setTwitterHandle(?string $twitterHandle): self
    {
        $this->twitterHandle = $twitterHandle;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
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

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;
        return $this;
    }

    public function getOfficeLocation(): ?string
    {
        return $this->officeLocation;
    }

    public function setOfficeLocation(?string $officeLocation): self
    {
        $this->officeLocation = $officeLocation;
        return $this;
    }

    public function getEmployeeId(): ?string
    {
        return $this->employeeId;
    }

    public function setEmployeeId(?string $employeeId): self
    {
        $this->employeeId = $employeeId;
        return $this;
    }

    public function getHireDate(): ?\DateTimeImmutable
    {
        return $this->hireDate;
    }

    public function setHireDate(?\DateTimeImmutable $hireDate): self
    {
        $this->hireDate = $hireDate;
        return $this;
    }

    public function getTerminationDate(): ?\DateTimeImmutable
    {
        return $this->terminationDate;
    }

    public function setTerminationDate(?\DateTimeImmutable $terminationDate): self
    {
        $this->terminationDate = $terminationDate;
        return $this;
    }

    public function getEmploymentStatus(): ?string
    {
        return $this->employmentStatus;
    }

    public function setEmploymentStatus(?string $employmentStatus): self
    {
        $this->employmentStatus = $employmentStatus;
        return $this;
    }

    public function getCostCenter(): ?string
    {
        return $this->costCenter;
    }

    public function setCostCenter(?string $costCenter): self
    {
        $this->costCenter = $costCenter;
        return $this;
    }

    public function getDivision(): ?string
    {
        return $this->division;
    }

    public function setDivision(?string $division): self
    {
        $this->division = $division;
        return $this;
    }

    public function getBusinessUnit(): ?string
    {
        return $this->businessUnit;
    }

    public function setBusinessUnit(?string $businessUnit): self
    {
        $this->businessUnit = $businessUnit;
        return $this;
    }

    public function getSalary(): ?string
    {
        return $this->salary;
    }

    public function setSalary(?string $salary): self
    {
        $this->salary = $salary;
        return $this;
    }

    public function getSalaryFrequency(): ?string
    {
        return $this->salaryFrequency;
    }

    public function setSalaryFrequency(?string $salaryFrequency): self
    {
        $this->salaryFrequency = $salaryFrequency;
        return $this;
    }

    public function getSkills(): ?array
    {
        return $this->skills;
    }

    public function setSkills(?array $skills): self
    {
        $this->skills = $skills;
        return $this;
    }

    public function getCertifications(): ?array
    {
        return $this->certifications;
    }

    public function setCertifications(?array $certifications): self
    {
        $this->certifications = $certifications;
        return $this;
    }

    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    public function setLanguages(?array $languages): self
    {
        $this->languages = $languages;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getLoginCount(): int
    {
        return $this->loginCount;
    }

    public function setLoginCount(int $loginCount): self
    {
        $this->loginCount = $loginCount;
        return $this;
    }

    public function incrementLoginCount(): self
    {
        $this->loginCount++;
        return $this;
    }

    public function getLastIpAddress(): ?string
    {
        return $this->lastIpAddress;
    }

    public function setLastIpAddress(?string $lastIpAddress): self
    {
        $this->lastIpAddress = $lastIpAddress;
        return $this;
    }

    public function getLastUserAgent(): ?string
    {
        return $this->lastUserAgent;
    }

    public function setLastUserAgent(?string $lastUserAgent): self
    {
        $this->lastUserAgent = $lastUserAgent;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;
        return $this;
    }

    public function getProfileCompleteness(): int
    {
        return $this->profileCompleteness;
    }

    public function setProfileCompleteness(int $profileCompleteness): self
    {
        $this->profileCompleteness = max(0, min(100, $profileCompleteness));
        return $this;
    }

    public function calculateProfileCompleteness(): self
    {
        $fields = [
            $this->name, $this->email, $this->username, $this->phone,
            $this->jobTitle, $this->department, $this->avatar, $this->bio,
            $this->timezone, $this->locale, $this->address, $this->city
        ];

        $filled = count(array_filter($fields, fn($field) => !empty($field)));
        $total = count($fields);

        $this->profileCompleteness = (int) round(($filled / $total) * 100);
        return $this;
    }

    public function getLastActivityAt(): ?\DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeImmutable $lastActivityAt): self
    {
        $this->lastActivityAt = $lastActivityAt;
        return $this;
    }

    public function updateLastActivity(): self
    {
        $this->lastActivityAt = new \DateTimeImmutable();
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusMessage(): ?string
    {
        return $this->statusMessage;
    }

    public function setStatusMessage(?string $statusMessage): self
    {
        $this->statusMessage = $statusMessage;
        return $this;
    }

    public function isLocked(): bool
    {
        // Check both lockedUntil (temporary lock) and locked (permanent/admin lock)
        if ($this->locked) {
            return true;
        }

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

    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;
        if ($locked && !$this->lockedAt) {
            $this->lockedAt = new \DateTimeImmutable();
        } elseif (!$locked) {
            $this->lockedAt = null;
            $this->lockedReason = null;
        }
        return $this;
    }

    public function getLockedReason(): ?string
    {
        return $this->lockedReason;
    }

    public function setLockedReason(?string $lockedReason): self
    {
        $this->lockedReason = $lockedReason;
        return $this;
    }

    public function getLockedAt(): ?\DateTimeImmutable
    {
        return $this->lockedAt;
    }

    public function setLockedAt(?\DateTimeImmutable $lockedAt): self
    {
        $this->lockedAt = $lockedAt;
        return $this;
    }

    public function lockAccount(string $reason): self
    {
        $this->locked = true;
        $this->lockedReason = $reason;
        $this->lockedAt = new \DateTimeImmutable();
        return $this;
    }

    public function unlockAccount(): self
    {
        $this->locked = false;
        $this->lockedReason = null;
        $this->lockedAt = null;
        $this->lockedUntil = null;
        $this->resetFailedLoginAttempts();
        return $this;
    }

    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    public function setCustomFields(?array $customFields): self
    {
        $this->customFields = $customFields;
        return $this;
    }

    public function getCustomField(string $key, mixed $default = null): mixed
    {
        return $this->customFields[$key] ?? $default;
    }

    public function setCustomField(string $key, mixed $value): self
    {
        if ($this->customFields === null) {
            $this->customFields = [];
        }
        $this->customFields[$key] = $value;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?: $this->email;
    }
}
