<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\UserGenerated;
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
#[ORM\Index(name: 'idx_user_agent', columns: ['agent'])]
#[ORM\Index(name: 'idx_user_agent_type', columns: ['agent_type'])]
#[ORM\Index(name: 'idx_user_active', columns: ['active'])]
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
class User extends UserGenerated implements UserInterface, PasswordAuthenticatedUserInterface
{
    // ===== ROLE MANAGEMENT SYSTEM =====

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_roles')]
    #[Groups(['user:read'])]
    protected Collection $roles;

    // ===== VERIFICATION & TERMS =====

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

    // ===== API TOKEN SYSTEM =====

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $apiToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $apiTokenExpiresAt = null;

    // ===== OPENAI INTEGRATION =====

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    // CRITICAL SECURITY: API key must NEVER be api_readable
    protected ?string $openAiApiKey = null;

    // ===== ENHANCED LOGIN TRACKING =====

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read', 'audit:read'])]
    protected ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['audit:read'])]
    protected ?\DateTimeImmutable $lockedUntil = null;

    // ===== UI/UX PREFERENCES =====

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read'])]
    protected ?array $uiSettings = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read'])]
    protected ?array $listPreferences = null;

    // ===== 2FA & ADVANCED SECURITY =====

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

    // ===== EXTENDED CRM FIELDS =====

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

    // ===== AUDIT FIELDS =====

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['audit:read'])]
    protected ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $avatar = null;

    // Note: birthDate and gender already in UserGenerated, but with different types
    // We may need to override these if type mismatch is intentional

    // ===== NAME COMPONENTS =====

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $title = null;

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
    protected ?string $suffix = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $nickname = null;

    // ===== EXTENDED CONTACT INFO =====

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

    // ===== ADDRESS FIELDS =====

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

    // ===== EMPLOYMENT DETAILS =====

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
    protected ?string $employmentStatus = null;

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
    protected ?string $salaryFrequency = null;

    // ===== PROFESSIONAL DATA =====

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

    // ===== ACTIVITY TRACKING =====

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
    protected bool $visible = true;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['user:read'])]
    protected int $profileCompleteness = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read', 'audit:read'])]
    protected ?\DateTimeImmutable $lastActivityAt = null;

    // ===== STATUS SYSTEM =====

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $status = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?string $statusMessage = null;

    // ===== ACCOUNT LOCKING (ADMIN) =====

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]
    protected bool $locked = false;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['audit:read'])]
    protected ?string $lockedReason = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['audit:read'])]
    protected ?\DateTimeImmutable $lockedAt = null;

    // ===== CUSTOM FIELDS SYSTEM =====

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    protected ?array $customFields = null;

    // ===== CONSTRUCTOR =====

    public function __construct()
    {
        parent::__construct();
        $this->roles = new ArrayCollection();
    }

    // ===== SYMFONY SECURITY INTERFACE METHODS =====

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

    // ===== ROLE MANAGEMENT =====

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

    // ===== VERIFICATION METHODS =====

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;
        return $this;
    }

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

    // ===== TERMS METHODS =====

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

    // ===== API TOKEN METHODS =====

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

    // ===== OPENAI API KEY METHODS =====

    public function getOpenAiApiKey(): ?string
    {
        return $this->openAiApiKey;
    }

    public function setOpenAiApiKey(?string $openAiApiKey): self
    {
        $this->openAiApiKey = $openAiApiKey;
        return $this;
    }

    // ===== SECURITY METHODS =====

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

    // ===== UI SETTINGS METHODS =====

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

    public function getListPreference(string $key, mixed $default = null): mixed
    {
        if ($this->listPreferences === null) {
            return $default;
        }
        return $this->listPreferences[$key] ?? $default;
    }

    public function setListPreference(string $key, mixed $value): self
    {
        if ($this->listPreferences === null) {
            $this->listPreferences = [];
        }
        $this->listPreferences[$key] = $value;
        return $this;
    }

    public function getUiSetting(string $key, mixed $default = null): mixed
    {
        return $this->uiSettings[$key] ?? $default;
    }

    public function setUiSetting(string $key, mixed $value): self
    {
        if ($this->uiSettings === null) {
            $this->uiSettings = [];
        }
        $this->uiSettings[$key] = $value;
        return $this;
    }

    public function mergeUiSettings(array $settings): self
    {
        if ($this->uiSettings === null) {
            $this->uiSettings = [];
        }
        $this->uiSettings = array_merge($this->uiSettings, $settings);
        return $this;
    }

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

    public function initializeUiSettings(): self
    {
        if ($this->uiSettings === null) {
            $this->uiSettings = $this->getDefaultUiSettings();
        }
        return $this;
    }

    // ===== 2FA METHODS =====

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

    // ===== PASSWORD RESET METHODS =====

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

    // ===== SESSION TOKEN METHODS =====

    public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    public function setSessionToken(?string $sessionToken): self
    {
        $this->sessionToken = $sessionToken;
        return $this;
    }

    // ===== PASSWORD MANAGEMENT =====

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

    // ===== PASSKEY METHODS =====

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

    // ===== CRM FIELD GETTERS/SETTERS =====

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

    // Note: setIsActive() alias exists in parent UserGenerated

    // ===== AUDIT FIELD METHODS =====

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

    // Note: birthDate and gender getters/setters exist in parent

    // ===== NAME COMPONENT METHODS =====

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

    // ===== EXTENDED CONTACT INFO METHODS =====

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

    // ===== ADDRESS METHODS =====

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

    // ===== EMPLOYMENT METHODS =====

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

    // ===== PROFESSIONAL DATA METHODS =====

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

    // ===== ACTIVITY TRACKING METHODS =====

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

    // ===== STATUS METHODS =====

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

    // ===== ACCOUNT LOCKING METHODS =====

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

    // ===== CUSTOM FIELDS METHODS =====

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

    // ===== __toString =====

    public function __toString(): string
    {
        return $this->name ?: $this->email;
    }
}
