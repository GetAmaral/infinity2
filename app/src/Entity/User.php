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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
    // ===== CONSTRUCTOR =====

    public function __construct()
    {
        parent::__construct();
    }

    // ===== SYMFONY SECURITY INTERFACE METHODS =====

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        foreach ($this->grantedRoles as $role) {
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
        if (!$this->grantedRoles->contains($role)) {
            $this->grantedRoles->add($role);
        }
        return $this;
    }

    public function removeRole(Role $role): self
    {
        $this->grantedRoles->removeElement($role);
        return $this;
    }

    public function getRoleEntities(): \Doctrine\Common\Collections\Collection
    {
        return $this->grantedRoles;
    }

    public function hasRole(string $roleName): bool
    {
        foreach ($this->grantedRoles as $role) {
            if ($role->getName() === $roleName) {
                return true;
            }
        }
        return false;
    }

    public function hasPermission(string $permission): bool
    {
        foreach ($this->grantedRoles as $role) {
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

    // ===== API TOKEN METHODS =====

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

    // ===== SECURITY METHODS =====

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

    // ===== UI SETTINGS METHODS =====

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

    // ===== PASSWORD RESET METHODS =====

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

    // ===== PASSWORD MANAGEMENT =====

    public function mustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    // ===== AGENT ALIAS =====

    public function setIsAgent(bool $agent): self
    {
        return $this->setAgent($agent);
    }

    // ===== SOFT DELETE =====

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    // ===== ACTIVITY TRACKING METHODS =====

    public function incrementLoginCount(): self
    {
        $this->loginCount++;
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

    public function updateLastActivity(): self
    {
        $this->lastActivityAt = new \DateTimeImmutable();
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
