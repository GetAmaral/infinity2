<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ModuleRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Module Entity - CRM System Module Management
 *
 * Represents a system module (feature/functionality) in the CRM with:
 * - Role-based access control (RBAC)
 * - Permission matrix management
 * - Module activation/deactivation
 * - Hierarchical module structure (parent-child relationships)
 * - Organization-based multi-tenancy support
 * - Audit trail tracking
 *
 * Following 2025 CRM Best Practices:
 * - Principle of Least Privilege
 * - Matrix Permission Management
 * - Field-level security
 * - Module dependencies tracking
 * - License/feature gating
 */
#[ORM\Entity(repositoryClass: ModuleRepository::class)]
#[ORM\Table(name: 'module')]
#[ORM\Index(name: 'idx_module_name', columns: ['name'])]
#[ORM\Index(name: 'idx_module_code', columns: ['code'])]
#[ORM\Index(name: 'idx_module_active', columns: ['active'])]
#[ORM\Index(name: 'idx_module_enabled', columns: ['enabled'])]
#[ORM\Index(name: 'idx_module_system', columns: ['system'])]
#[ORM\Index(name: 'idx_module_category', columns: ['category'])]
#[ORM\Index(name: 'idx_module_parent_id', columns: ['parent_id'])]
#[ORM\Index(name: 'idx_module_organization_id', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_module_display_order', columns: ['display_order'])]
#[ORM\Index(name: 'idx_module_license_required', columns: ['license_required'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['code'], message: 'This module code is already in use.')]
#[UniqueEntity(fields: ['name', 'organization'], message: 'This module name is already in use in this organization.')]
#[ApiResource(
    normalizationContext: ['groups' => ['module:read']],
    denormalizationContext: ['groups' => ['module:write']],
    operations: [
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['module:read', 'module:detail']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['module:read', 'module:list']]
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['module:write', 'module:create']]
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['module:write', 'module:update']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['module:write', 'module:update']]
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') and object.getSystem() == false"
        ),
        // Admin endpoint with full audit information
        new GetCollection(
            uriTemplate: '/admin/modules',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['module:read', 'module:detail', 'module:admin', 'audit:read']]
        ),
        // Active modules endpoint (for user navigation)
        new GetCollection(
            uriTemplate: '/modules/active',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['module:read', 'module:navigation']]
        ),
        // Module permissions endpoint
        new GetCollection(
            uriTemplate: '/modules/{id}/permissions',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['module:read', 'module:permissions']]
        )
    ]
)]
class Module extends EntityBase
{
    // ===== CORE IDENTITY FIELDS =====

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Module name is required')]
    #[Assert\Length(min: 2, max: 255)]
    #[Groups(['module:read', 'module:write', 'module:list', 'module:navigation'])]
    protected string $name = '';

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Module code is required')]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9_]+$/',
        message: 'Module code must contain only lowercase letters, numbers, and underscores'
    )]
    #[Groups(['module:read', 'module:write', 'module:list', 'module:detail'])]
    protected string $code = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:detail'])]
    protected ?string $description = null;

    // ===== ACTIVATION CONTROL =====

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['module:read', 'module:write', 'module:list', 'module:navigation'])]
    protected bool $active = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['module:read', 'module:write', 'module:list', 'module:navigation'])]
    protected bool $enabled = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected bool $system = false;

    // ===== VISUAL REPRESENTATION =====

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['module:read', 'module:write', 'module:list', 'module:navigation'])]
    protected ?string $icon = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/')]
    #[Groups(['module:read', 'module:write', 'module:detail'])]
    protected ?string $color = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['module:read', 'module:write', 'module:list'])]
    protected int $displayOrder = 0;

    // ===== CATEGORIZATION =====

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['module:read', 'module:write', 'module:list'])]
    protected ?string $category = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:detail'])]
    protected ?array $tags = null;

    // ===== ROUTING & NAVIGATION =====

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['module:read', 'module:write', 'module:navigation'])]
    protected ?string $routeName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['module:read', 'module:write', 'module:navigation'])]
    protected ?string $url = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['module:read', 'module:write', 'module:navigation'])]
    protected bool $visibleInMenu = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['module:read', 'module:write', 'module:detail'])]
    protected bool $openInNewWindow = false;

    // ===== PERMISSIONS & SECURITY (2025 CRM Best Practices) =====

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:permissions', 'module:admin'])]
    protected ?array $permissions = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:permissions', 'module:admin'])]
    protected ?array $defaultPermissions = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected ?array $requiredRoles = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected ?array $securityPolicy = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected bool $publicAccess = false;

    // ===== MODULE RELATIONSHIPS =====

    #[ORM\ManyToOne(targetEntity: Module::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['module:read', 'module:write', 'module:detail'])]
    protected ?Module $parent = null;

    #[ORM\OneToMany(targetEntity: Module::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['displayOrder' => 'ASC'])]
    #[Groups(['module:read', 'module:detail'])]
    protected Collection $children;

    // ===== ORGANIZATION (MULTI-TENANT) =====

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['module:read', 'module:admin'])]
    protected ?Organization $organization = null;

    // ===== LICENSING & FEATURE GATING =====

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected bool $licenseRequired = false;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected ?string $licenseType = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected ?array $featureFlags = null;

    // ===== DEPENDENCIES =====

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected ?array $dependencies = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected ?array $conflicts = null;

    // ===== VERSIONING =====

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Groups(['module:read', 'module:write', 'module:detail', 'module:admin'])]
    protected ?string $version = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['module:read', 'module:admin'])]
    protected ?\DateTimeImmutable $installedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['module:read', 'module:admin'])]
    protected ?\DateTimeImmutable $lastActivatedAt = null;

    // ===== CONFIGURATION =====

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected ?array $configuration = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected ?array $settings = null;

    // ===== METADATA =====

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['module:read', 'module:write', 'module:detail'])]
    protected ?string $vendor = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    #[Assert\Length(max: 255)]
    #[Groups(['module:read', 'module:write', 'module:detail'])]
    protected ?string $documentationUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    #[Assert\Length(max: 255)]
    #[Groups(['module:read', 'module:write', 'module:detail'])]
    protected ?string $supportUrl = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['module:read', 'module:write', 'module:admin'])]
    protected ?array $metadata = null;

    // ===== USAGE STATISTICS =====

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['module:read', 'module:admin'])]
    protected int $usageCount = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['module:read', 'module:admin'])]
    protected ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct()
    {
        parent::__construct();
        $this->children = new ArrayCollection();
        $this->permissions = [];
        $this->defaultPermissions = [];
        $this->requiredRoles = [];
        $this->featureFlags = [];
        $this->dependencies = [];
        $this->conflicts = [];
        $this->tags = [];
        $this->configuration = [];
        $this->settings = [];
        $this->metadata = [];
        $this->securityPolicy = [];
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if ($this->installedAt === null) {
            $this->installedAt = new \DateTimeImmutable();
        }
        if ($this->active && $this->lastActivatedAt === null) {
            $this->lastActivatedAt = new \DateTimeImmutable();
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        if ($this->active && $this->lastActivatedAt === null) {
            $this->lastActivatedAt = new \DateTimeImmutable();
        }
    }

    // ===== GETTERS & SETTERS =====

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtolower($code);
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

    // Active/Enabled getters (following convention)
    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        if ($active) {
            $this->lastActivatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function isSystem(): bool
    {
        return $this->system;
    }

    public function setSystem(bool $system): self
    {
        $this->system = $system;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
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

    public function addTag(string $tag): self
    {
        if ($this->tags === null) {
            $this->tags = [];
        }
        if (!in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }
        return $this;
    }

    public function removeTag(string $tag): self
    {
        if ($this->tags === null) {
            return $this;
        }
        $this->tags = array_values(array_filter($this->tags, fn($t) => $t !== $tag));
        return $this;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteName(?string $routeName): self
    {
        $this->routeName = $routeName;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function isVisibleInMenu(): bool
    {
        return $this->visibleInMenu;
    }

    public function setVisibleInMenu(bool $visibleInMenu): self
    {
        $this->visibleInMenu = $visibleInMenu;
        return $this;
    }

    public function isOpenInNewWindow(): bool
    {
        return $this->openInNewWindow;
    }

    public function setOpenInNewWindow(bool $openInNewWindow): self
    {
        $this->openInNewWindow = $openInNewWindow;
        return $this;
    }

    // ===== PERMISSIONS MANAGEMENT (2025 CRM Best Practices) =====

    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    public function setPermissions(?array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions !== null && in_array($permission, $this->permissions, true);
    }

    public function addPermission(string $permission): self
    {
        if ($this->permissions === null) {
            $this->permissions = [];
        }
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }
        return $this;
    }

    public function removePermission(string $permission): self
    {
        if ($this->permissions === null) {
            return $this;
        }
        $this->permissions = array_values(array_filter(
            $this->permissions,
            fn($p) => $p !== $permission
        ));
        return $this;
    }

    public function getDefaultPermissions(): ?array
    {
        return $this->defaultPermissions;
    }

    public function setDefaultPermissions(?array $defaultPermissions): self
    {
        $this->defaultPermissions = $defaultPermissions;
        return $this;
    }

    public function getRequiredRoles(): ?array
    {
        return $this->requiredRoles;
    }

    public function setRequiredRoles(?array $requiredRoles): self
    {
        $this->requiredRoles = $requiredRoles;
        return $this;
    }

    public function getSecurityPolicy(): ?array
    {
        return $this->securityPolicy;
    }

    public function setSecurityPolicy(?array $securityPolicy): self
    {
        $this->securityPolicy = $securityPolicy;
        return $this;
    }

    public function isPublicAccess(): bool
    {
        return $this->publicAccess;
    }

    public function setPublicAccess(bool $publicAccess): self
    {
        $this->publicAccess = $publicAccess;
        return $this;
    }

    // ===== MODULE RELATIONSHIPS =====

    public function getParent(): ?Module
    {
        return $this->parent;
    }

    public function setParent(?Module $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, Module>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Module $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
        return $this;
    }

    public function removeChild(Module $child): self
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }
        return $this;
    }

    public function hasChildren(): bool
    {
        return !$this->children->isEmpty();
    }

    public function getLevel(): int
    {
        $level = 0;
        $parent = $this->parent;
        while ($parent !== null) {
            $level++;
            $parent = $parent->getParent();
        }
        return $level;
    }

    // ===== ORGANIZATION =====

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    // ===== LICENSING & FEATURE GATING =====

    public function isLicenseRequired(): bool
    {
        return $this->licenseRequired;
    }

    public function setLicenseRequired(bool $licenseRequired): self
    {
        $this->licenseRequired = $licenseRequired;
        return $this;
    }

    public function getLicenseType(): ?string
    {
        return $this->licenseType;
    }

    public function setLicenseType(?string $licenseType): self
    {
        $this->licenseType = $licenseType;
        return $this;
    }

    public function getFeatureFlags(): ?array
    {
        return $this->featureFlags;
    }

    public function setFeatureFlags(?array $featureFlags): self
    {
        $this->featureFlags = $featureFlags;
        return $this;
    }

    public function hasFeatureFlag(string $flag): bool
    {
        return $this->featureFlags !== null && isset($this->featureFlags[$flag]) && $this->featureFlags[$flag] === true;
    }

    public function setFeatureFlag(string $flag, bool $value): self
    {
        if ($this->featureFlags === null) {
            $this->featureFlags = [];
        }
        $this->featureFlags[$flag] = $value;
        return $this;
    }

    // ===== DEPENDENCIES =====

    public function getDependencies(): ?array
    {
        return $this->dependencies;
    }

    public function setDependencies(?array $dependencies): self
    {
        $this->dependencies = $dependencies;
        return $this;
    }

    public function hasDependency(string $moduleCode): bool
    {
        return $this->dependencies !== null && in_array($moduleCode, $this->dependencies, true);
    }

    public function addDependency(string $moduleCode): self
    {
        if ($this->dependencies === null) {
            $this->dependencies = [];
        }
        if (!in_array($moduleCode, $this->dependencies, true)) {
            $this->dependencies[] = $moduleCode;
        }
        return $this;
    }

    public function getConflicts(): ?array
    {
        return $this->conflicts;
    }

    public function setConflicts(?array $conflicts): self
    {
        $this->conflicts = $conflicts;
        return $this;
    }

    // ===== VERSIONING =====

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getInstalledAt(): ?\DateTimeImmutable
    {
        return $this->installedAt;
    }

    public function setInstalledAt(?\DateTimeImmutable $installedAt): self
    {
        $this->installedAt = $installedAt;
        return $this;
    }

    public function getLastActivatedAt(): ?\DateTimeImmutable
    {
        return $this->lastActivatedAt;
    }

    public function setLastActivatedAt(?\DateTimeImmutable $lastActivatedAt): self
    {
        $this->lastActivatedAt = $lastActivatedAt;
        return $this;
    }

    // ===== CONFIGURATION =====

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(?array $configuration): self
    {
        $this->configuration = $configuration;
        return $this;
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->configuration[$key] ?? $default;
    }

    public function setConfigValue(string $key, mixed $value): self
    {
        if ($this->configuration === null) {
            $this->configuration = [];
        }
        $this->configuration[$key] = $value;
        return $this;
    }

    public function getSettings(): ?array
    {
        return $this->settings;
    }

    public function setSettings(?array $settings): self
    {
        $this->settings = $settings;
        return $this;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, mixed $value): self
    {
        if ($this->settings === null) {
            $this->settings = [];
        }
        $this->settings[$key] = $value;
        return $this;
    }

    // ===== METADATA =====

    public function getVendor(): ?string
    {
        return $this->vendor;
    }

    public function setVendor(?string $vendor): self
    {
        $this->vendor = $vendor;
        return $this;
    }

    public function getDocumentationUrl(): ?string
    {
        return $this->documentationUrl;
    }

    public function setDocumentationUrl(?string $documentationUrl): self
    {
        $this->documentationUrl = $documentationUrl;
        return $this;
    }

    public function getSupportUrl(): ?string
    {
        return $this->supportUrl;
    }

    public function setSupportUrl(?string $supportUrl): self
    {
        $this->supportUrl = $supportUrl;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    // ===== USAGE STATISTICS =====

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): self
    {
        $this->usageCount = $usageCount;
        return $this;
    }

    public function incrementUsageCount(): self
    {
        $this->usageCount++;
        $this->lastUsedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    // ===== UTILITY METHODS =====

    /**
     * Check if module is fully accessible (active, enabled, and meets all requirements)
     */
    public function isAccessible(): bool
    {
        return $this->active && $this->enabled && !$this->isLicenseBlocked();
    }

    /**
     * Check if module is blocked by license requirements
     */
    public function isLicenseBlocked(): bool
    {
        // Implement license validation logic here
        return $this->licenseRequired && empty($this->licenseType);
    }

    /**
     * Get full module path (for hierarchical display)
     */
    public function getFullPath(string $separator = ' > '): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        while ($parent !== null) {
            array_unshift($path, $parent->getName());
            $parent = $parent->getParent();
        }
        return implode($separator, $path);
    }

    /**
     * Check if all dependencies are satisfied
     */
    public function areDependenciesSatisfied(array $installedModules): bool
    {
        if ($this->dependencies === null || empty($this->dependencies)) {
            return true;
        }
        foreach ($this->dependencies as $dependency) {
            if (!in_array($dependency, $installedModules, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if module conflicts with any installed modules
     */
    public function hasConflictsWith(array $installedModules): bool
    {
        if ($this->conflicts === null || empty($this->conflicts)) {
            return false;
        }
        foreach ($this->conflicts as $conflict) {
            if (in_array($conflict, $installedModules, true)) {
                return true;
            }
        }
        return false;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
