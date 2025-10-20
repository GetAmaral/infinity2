<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\UuidV7Generator;
use App\Repository\ProfileTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;

/**
 * ProfileTemplate Entity
 *
 * CRM Profile Template System for defining reusable profile structures.
 * Enables organizations to create standardized profile templates with custom fields
 * for different user types, roles, or departments.
 *
 * Features (2025 Best Practices):
 * - Multi-tenant template management
 * - Flexible field definitions via ProfileTemplateField
 * - Category-based template organization
 * - Industry-specific templates
 * - Default template selection
 * - Version tracking for template evolution
 * - Clone/duplicate functionality
 * - Field validation rules
 * - Conditional field visibility
 * - Template inheritance
 * - AI-powered field suggestions
 * - GDPR/privacy compliance tracking
 *
 * Use Cases:
 * - Sales Team Profile Template
 * - Customer Profile Template
 * - Partner Profile Template
 * - Lead Profile Template
 * - Employee Profile Template
 * - Consultant Profile Template
 *
 * @see Profile
 * @see ProfileTemplateField
 */
#[ORM\Entity(repositoryClass: ProfileTemplateRepository::class)]
#[ORM\Table(name: 'profile_template')]
#[ORM\Index(name: 'idx_template_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_template_active', columns: ['active'])]
#[ORM\Index(name: 'idx_template_default', columns: ['default_template'])]
#[ORM\Index(name: 'idx_template_category', columns: ['category'])]
#[ORM\Index(name: 'idx_template_industry', columns: ['industry'])]
#[ORM\Index(name: 'idx_template_name', columns: ['template_name'])]
#[ORM\Index(name: 'idx_template_version', columns: ['version'])]
#[ORM\Index(name: 'idx_template_created', columns: ['created_at'])]
#[ORM\Index(name: 'idx_template_deleted', columns: ['deleted_at'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    shortName: 'ProfileTemplate',
    description: 'CRM Profile Template for defining reusable profile structures with custom fields',
    normalizationContext: [
        'groups' => ['profile_template:read'],
        'swagger_definition_name' => 'Read',
        'enable_max_depth' => true
    ],
    denormalizationContext: [
        'groups' => ['profile_template:write'],
        'swagger_definition_name' => 'Write'
    ],
    operations: [
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['profile_template:read', 'profile_template:read:full']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['profile_template:read']],
            paginationEnabled: true,
            paginationItemsPerPage: 30
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['profile_template:write', 'profile_template:create']],
            validationContext: ['groups' => ['Default', 'profile_template:create']]
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['profile_template:write']],
            validationContext: ['groups' => ['Default', 'profile_template:update']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['profile_template:write']],
            validationContext: ['groups' => ['Default', 'profile_template:update']]
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        ),
        // Custom operations
        new Get(
            uriTemplate: '/profile-templates/{id}/clone',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['profile_template:read', 'profile_template:read:full']],
            description: 'Clone a profile template'
        ),
        new GetCollection(
            uriTemplate: '/profile-templates/defaults',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['profile_template:read']],
            description: 'Get all default templates'
        ),
        new GetCollection(
            uriTemplate: '/profile-templates/by-category/{category}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['profile_template:read']],
            description: 'Get templates by category'
        )
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    order: ['templateName' => 'ASC']
)]
#[ApiFilter(SearchFilter::class, properties: [
    'templateName' => 'partial',
    'category' => 'exact',
    'industry' => 'exact',
    'templateCode' => 'exact'
])]
#[ApiFilter(BooleanFilter::class, properties: ['active', 'defaultTemplate', 'system', 'published'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt', 'updatedAt'])]
#[ApiFilter(OrderFilter::class, properties: [
    'templateName',
    'category',
    'usageCount',
    'version',
    'createdAt',
    'updatedAt'
], arguments: ['orderParameterName' => 'order'])]
class ProfileTemplate extends EntityBase
{
    // ===== BASIC INFORMATION =====

    #[ORM\Column(type: Types::STRING, length: 150, unique: false)]
    #[Assert\NotBlank(message: 'Template name is required', groups: ['profile_template:create'])]
    #[Assert\Length(
        min: 3,
        max: 150,
        minMessage: 'Template name must be at least {{ limit }} characters',
        maxMessage: 'Template name cannot exceed {{ limit }} characters'
    )]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Template name (e.g., "Sales Team Profile", "Customer Profile")',
        example: 'Sales Team Profile',
        openapiContext: ['minLength' => 3, 'maxLength' => 150]
    )]
    private string $templateName;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Template code is required', groups: ['profile_template:create'])]
    #[Assert\Regex(
        pattern: '/^[a-z0-9_-]+$/',
        message: 'Template code must contain only lowercase letters, numbers, hyphens, and underscores'
    )]
    #[Assert\Length(min: 3, max: 50)]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Unique template code (slug format, e.g., "sales-team-profile")',
        example: 'sales-team-profile',
        openapiContext: ['minLength' => 3, 'maxLength' => 50, 'pattern' => '^[a-z0-9_-]+$']
    )]
    private string $templateCode;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed {{ limit }} characters')]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Template description explaining its purpose and use case',
        example: 'Profile template for sales team members with sales-specific fields',
        openapiContext: ['maxLength' => 1000]
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Regex(
        pattern: '/^bi-[a-z0-9-]+$/',
        message: 'Icon must be a valid Bootstrap icon (e.g., bi-person-badge)'
    )]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Bootstrap icon class for template visualization',
        example: 'bi-person-badge',
        openapiContext: ['pattern' => '^bi-[a-z0-9-]+$']
    )]
    private ?string $icon = 'bi-file-earmark-person';

    #[ORM\Column(type: Types::STRING, length: 7, options: ['default' => '#6c757d'])]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'Color must be a valid hex color (e.g., #6c757d)'
    )]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Hex color for template visualization',
        example: '#0d6efd',
        openapiContext: ['pattern' => '^#[0-9A-Fa-f]{6}$']
    )]
    private string $color = '#6c757d';

    // ===== CATEGORIZATION =====

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank(message: 'Category is required')]
    #[Assert\Choice(
        choices: ['sales', 'customer', 'partner', 'lead', 'employee', 'consultant', 'contractor', 'vendor', 'other'],
        message: 'Invalid category'
    )]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Template category',
        example: 'sales',
        openapiContext: ['enum' => ['sales', 'customer', 'partner', 'lead', 'employee', 'consultant', 'contractor', 'vendor', 'other']]
    )]
    private string $category;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ['technology', 'finance', 'healthcare', 'retail', 'manufacturing', 'education', 'real-estate', 'hospitality', 'professional-services', 'other'],
        message: 'Invalid industry'
    )]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Target industry for this template',
        example: 'technology',
        openapiContext: ['enum' => ['technology', 'finance', 'healthcare', 'retail', 'manufacturing', 'education', 'real-estate', 'hospitality', 'professional-services', 'other']]
    )]
    private ?string $industry = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Tags for template organization and search',
        example: '["crm", "sales", "b2b"]',
        openapiContext: ['type' => 'array', 'items' => ['type' => 'string']]
    )]
    private ?array $tags = null;

    // ===== STATUS & CONFIGURATION =====

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Template is active and available for use',
        example: true
    )]
    private bool $active = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'This is the default template for the organization',
        example: false
    )]
    private bool $defaultTemplate = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template:read'])]
    #[ApiProperty(
        description: 'System template (cannot be deleted)',
        example: false,
        readable: true,
        writable: false
    )]
    private bool $system = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Template is published and visible to users',
        example: false
    )]
    private bool $published = false;

    // ===== VERSIONING =====

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => '1.0.0'])]
    #[Assert\Regex(
        pattern: '/^\d+\.\d+\.\d+$/',
        message: 'Version must follow semantic versioning (e.g., 1.0.0)'
    )]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Template version following semantic versioning',
        example: '1.0.0',
        openapiContext: ['pattern' => '^\d+\.\d+\.\d+$']
    )]
    private string $version = '1.0.0';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Changelog documenting version changes',
        example: '1.0.0: Initial release',
        openapiContext: ['type' => 'string']
    )]
    private ?string $changelog = null;

    // ===== RELATIONSHIPS =====

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Organization is required')]
    #[Groups(['profile_template:read'])]
    #[ApiProperty(
        description: 'Organization this template belongs to',
        readableLink: true,
        writableLink: false
    )]
    private Organization $organization;

    #[ORM\OneToMany(
        mappedBy: 'profileTemplate',
        targetEntity: ProfileTemplateField::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['fieldOrder' => 'ASC'])]
    #[Groups(['profile_template:read', 'profile_template:read:full'])]
    #[ApiProperty(
        description: 'Fields defined in this template',
        readableLink: true,
        writableLink: false
    )]
    private Collection $fields;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['profile_template:read'])]
    #[ApiProperty(
        description: 'Parent template for inheritance',
        readableLink: true,
        writableLink: false
    )]
    private ?self $parentTemplate = null;

    // ===== USAGE STATISTICS =====

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['profile_template:read'])]
    #[ApiProperty(
        description: 'Number of profiles using this template',
        example: 25,
        readable: true,
        writable: false
    )]
    private int $usageCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['profile_template:read'])]
    #[ApiProperty(
        description: 'Last time this template was used',
        readable: true,
        writable: false,
        openapiContext: ['format' => 'date-time']
    )]
    private ?\DateTimeImmutable $lastUsedAt = null;

    // ===== METADATA =====

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Template-level configuration options',
        example: '{"validation_strict": true, "allow_custom_fields": false}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $config = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Custom metadata for extensibility',
        example: '{"author": "System Admin", "department": "Sales"}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $metadata = null;

    // ===== AI & COMPLIANCE (2025 Features) =====

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Enable AI-powered field suggestions',
        example: false
    )]
    private bool $aiSuggestionsEnabled = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Template complies with GDPR regulations',
        example: true
    )]
    private bool $gdprCompliant = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile_template:read', 'profile_template:write'])]
    #[ApiProperty(
        description: 'Privacy settings for data retention and consent',
        example: '{"data_retention_days": 365, "requires_consent": true}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $privacySettings = null;

    // ===== SOFT DELETE =====

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['profile_template:read:full'])]
    #[ApiProperty(
        description: 'Soft delete timestamp',
        readable: false,
        writable: false
    )]
    private ?\DateTimeImmutable $deletedAt = null;

    // ===== CONSTRUCTOR =====

    public function __construct()
    {
        parent::__construct();
        $this->fields = new ArrayCollection();
        $this->active = true;
        $this->defaultTemplate = false;
        $this->system = false;
        $this->published = false;
        $this->version = '1.0.0';
        $this->usageCount = 0;
        $this->aiSuggestionsEnabled = false;
        $this->gdprCompliant = false;
        $this->color = '#6c757d';
        $this->icon = 'bi-file-earmark-person';
    }

    // ===== DOMAIN LOGIC METHODS =====

    /**
     * Check if template can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->system && $this->usageCount === 0;
    }

    /**
     * Check if template is in use
     */
    public function isInUse(): bool
    {
        return $this->usageCount > 0;
    }

    /**
     * Increment usage count
     */
    public function incrementUsageCount(): self
    {
        $this->usageCount++;
        $this->lastUsedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Decrement usage count
     */
    public function decrementUsageCount(): self
    {
        if ($this->usageCount > 0) {
            $this->usageCount--;
        }
        return $this;
    }

    /**
     * Clone template with new name
     */
    public function cloneTemplate(string $newName, string $newCode): self
    {
        $clone = new self();
        $clone->setTemplateName($newName);
        $clone->setTemplateCode($newCode);
        $clone->setDescription($this->description);
        $clone->setCategory($this->category);
        $clone->setIndustry($this->industry);
        $clone->setIcon($this->icon);
        $clone->setColor($this->color);
        $clone->setTags($this->tags);
        $clone->setConfig($this->config);
        $clone->setMetadata($this->metadata);
        $clone->setOrganization($this->organization);
        $clone->setActive(false);
        $clone->setDefaultTemplate(false);
        $clone->setPublished(false);
        $clone->setParentTemplate($this);
        $clone->setVersion('1.0.0');
        $clone->setChangelog('Cloned from: ' . $this->templateName);

        return $clone;
    }

    /**
     * Bump version (patch)
     */
    public function bumpVersion(string $type = 'patch'): self
    {
        $parts = explode('.', $this->version);
        $major = (int)($parts[0] ?? 1);
        $minor = (int)($parts[1] ?? 0);
        $patch = (int)($parts[2] ?? 0);

        switch ($type) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                break;
            default: // patch
                $patch++;
                break;
        }

        $this->version = sprintf('%d.%d.%d', $major, $minor, $patch);
        return $this;
    }

    /**
     * Add field to template
     */
    public function addField(ProfileTemplateField $field): self
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setProfileTemplate($this);
        }
        return $this;
    }

    /**
     * Remove field from template
     */
    public function removeField(ProfileTemplateField $field): self
    {
        if ($this->fields->removeElement($field)) {
            if ($field->getProfileTemplate() === $this) {
                $field->setProfileTemplate(null);
            }
        }
        return $this;
    }

    /**
     * Get field count
     */
    public function getFieldCount(): int
    {
        return $this->fields->count();
    }

    /**
     * Check if template has a specific tag
     */
    public function hasTag(string $tag): bool
    {
        return $this->tags !== null && in_array($tag, $this->tags, true);
    }

    /**
     * Add a tag
     */
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

    /**
     * Remove a tag
     */
    public function removeTag(string $tag): self
    {
        if ($this->tags !== null) {
            $this->tags = array_values(array_filter($this->tags, fn($t) => $t !== $tag));
        }

        return $this;
    }

    // ===== COMPUTED PROPERTIES =====

    #[Groups(['profile_template:read'])]
    #[ApiProperty(
        description: 'Template is deleted (soft delete)',
        readable: true,
        writable: false
    )]
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    #[Groups(['profile_template:read'])]
    #[ApiProperty(
        description: 'Number of fields in template',
        readable: true,
        writable: false
    )]
    public function getFieldCountProperty(): int
    {
        return $this->getFieldCount();
    }

    // ===== GETTERS & SETTERS =====

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function setTemplateName(string $templateName): self
    {
        $this->templateName = $templateName;
        return $this;
    }

    public function getTemplateCode(): string
    {
        return $this->templateCode;
    }

    public function setTemplateCode(string $templateCode): self
    {
        $this->templateCode = $templateCode;
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    public function setIndustry(?string $industry): self
    {
        $this->industry = $industry;
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

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function isDefaultTemplate(): bool
    {
        return $this->defaultTemplate;
    }

    public function setDefaultTemplate(bool $defaultTemplate): self
    {
        $this->defaultTemplate = $defaultTemplate;
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

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;
        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getChangelog(): ?string
    {
        return $this->changelog;
    }

    public function setChangelog(?string $changelog): self
    {
        $this->changelog = $changelog;
        return $this;
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

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function getParentTemplate(): ?self
    {
        return $this->parentTemplate;
    }

    public function setParentTemplate(?self $parentTemplate): self
    {
        $this->parentTemplate = $parentTemplate;
        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): self
    {
        $this->usageCount = $usageCount;
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

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function setConfig(?array $config): self
    {
        $this->config = $config;
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

    public function isAiSuggestionsEnabled(): bool
    {
        return $this->aiSuggestionsEnabled;
    }

    public function setAiSuggestionsEnabled(bool $aiSuggestionsEnabled): self
    {
        $this->aiSuggestionsEnabled = $aiSuggestionsEnabled;
        return $this;
    }

    public function isGdprCompliant(): bool
    {
        return $this->gdprCompliant;
    }

    public function setGdprCompliant(bool $gdprCompliant): self
    {
        $this->gdprCompliant = $gdprCompliant;
        return $this;
    }

    public function getPrivacySettings(): ?array
    {
        return $this->privacySettings;
    }

    public function setPrivacySettings(?array $privacySettings): self
    {
        $this->privacySettings = $privacySettings;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function __toString(): string
    {
        return $this->templateName;
    }
}
