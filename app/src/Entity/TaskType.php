<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TaskTypeRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * TaskType Entity - Defines categorization and classification for CRM tasks
 *
 * Modern CRM task type taxonomy following 2025 best practices:
 * - Dual-layer classification (Type + Category)
 * - Visual identification (icons + colors)
 * - Behavior configuration (automation settings)
 * - SLA and priority management
 * - Multi-organization support with tenant isolation
 *
 * Standard Task Types (2025 CRM Standards):
 * - Call (phone outreach, callbacks, cold calls)
 * - Email (outbound email, email campaign, follow-up email)
 * - Meeting (client meeting, internal meeting, demo, presentation)
 * - To-Do (general task, administrative task, research)
 * - Follow-Up (customer follow-up, lead nurturing, post-meeting)
 * - Appointment (scheduled appointment, site visit, consultation)
 * - Proposal (send proposal, review proposal, negotiate)
 * - Document (prepare document, review contract, send agreement)
 * - Research (market research, competitor analysis, lead research)
 * - Reporting (create report, analyze metrics, monthly review)
 *
 * @author Luminai CRM Team
 */
#[ORM\Entity(repositoryClass: TaskTypeRepository::class)]
#[ORM\Table(name: 'task_type')]
#[ORM\Index(name: 'idx_task_type_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_task_type_code', columns: ['code'])]
#[ORM\Index(name: 'idx_task_type_category', columns: ['category'])]
#[ORM\Index(name: 'idx_task_type_active', columns: ['active'])]
#[ORM\Index(name: 'idx_task_type_default', columns: ['is_default'])]
#[ORM\Index(name: 'idx_task_type_sort_order', columns: ['sort_order'])]
#[ORM\Index(name: 'idx_task_type_requires_time', columns: ['requires_time_tracking'])]
#[ORM\Index(name: 'idx_task_type_automated', columns: ['automated'])]
#[ORM\UniqueConstraint(name: 'uniq_task_type_code_org', columns: ['code', 'organization_id'])]
#[UniqueEntity(fields: ['code', 'organization'], message: 'A task type with this code already exists in your organization')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['task_type:read']],
    denormalizationContext: ['groups' => ['task_type:write']],
    operations: [
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['task_type:read', 'task_type:detail']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['task_type:read', 'task_type:list']]
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['task_type:write', 'task_type:create']]
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['task_type:write', 'task_type:update']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        // Custom endpoint for active task types only
        new GetCollection(
            uriTemplate: '/task-types/active',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['task_type:read']]
        ),
        // Custom endpoint for default task types
        new GetCollection(
            uriTemplate: '/task-types/defaults',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['task_type:read']]
        )
    ]
)]
class TaskType extends EntityBase
{
    // ==================== CORE IDENTIFICATION FIELDS ====================

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Task type name is required')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Name must be at least 2 characters', maxMessage: 'Name cannot exceed 100 characters')]
    #[Groups(['task_type:read', 'task_type:write', 'task_type:list'])]
    protected string $name = '';

    #[ORM\Column(type: 'string', length: 50, unique: false)]
    #[Assert\NotBlank(message: 'Task type code is required')]
    #[Assert\Length(min: 2, max: 50)]
    #[Assert\Regex(pattern: '/^[A-Z0-9_]+$/', message: 'Code must contain only uppercase letters, numbers, and underscores')]
    #[Groups(['task_type:read', 'task_type:write', 'task_type:list'])]
    protected string $code = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
    #[Groups(['task_type:read', 'task_type:write', 'task_type:detail'])]
    protected ?string $description = null;

    // ==================== ORGANIZATION & MULTI-TENANCY ====================

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Organization is required')]
    #[Groups(['task_type:read'])]
    protected ?Organization $organization = null;

    // ==================== CLASSIFICATION & TAXONOMY ====================

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Category is required')]
    #[Assert\Choice(
        choices: ['communication', 'meeting', 'administrative', 'sales', 'support', 'marketing', 'project', 'other'],
        message: 'Invalid category. Must be one of: communication, meeting, administrative, sales, support, marketing, project, other'
    )]
    #[Groups(['task_type:read', 'task_type:write', 'task_type:list'])]
    protected string $category = 'other';

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected ?string $subCategory = null;

    // ==================== VISUAL IDENTIFICATION ====================

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Groups(['task_type:read', 'task_type:write', 'task_type:list'])]
    protected ?string $icon = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Color must be a valid hex color code (e.g., #FF5733)')]
    #[Groups(['task_type:read', 'task_type:write', 'task_type:list'])]
    protected ?string $color = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Badge color must be a valid hex color code')]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected ?string $badgeColor = null;

    // ==================== STATUS & CONFIGURATION ====================

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['task_type:read', 'task_type:write', 'task_type:list'])]
    protected bool $active = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $isDefault = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $isSystem = false;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 9999)]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected int $sortOrder = 0;

    // ==================== BEHAVIOR & AUTOMATION ====================

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $requiresTimeTracking = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $requiresDueDate = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $requiresAssignee = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $requiresDescription = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $allowsRecurrence = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $allowsSubtasks = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $automated = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $notificationsEnabled = true;

    // ==================== SLA & PRIORITY MANAGEMENT ====================

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive(message: 'Default duration must be a positive number')]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected ?int $defaultDurationMinutes = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive(message: 'SLA hours must be a positive number')]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected ?int $slaHours = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['low', 'normal', 'high', 'urgent', 'critical'],
        message: 'Invalid priority. Must be one of: low, normal, high, urgent, critical'
    )]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected ?string $defaultPriority = 'normal';

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $escalationEnabled = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected ?int $escalationHours = null;

    // ==================== WORKFLOW & INTEGRATION ====================

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected ?string $workflowTemplate = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected ?array $customFields = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected ?array $automationRules = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected ?array $notificationRules = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['task_type:read', 'task_type:write', 'task_type:detail'])]
    protected ?array $metadata = null;

    // ==================== RELATIONSHIPS ====================

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected ?string $relatedEntityType = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $allowsAttachments = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['task_type:read', 'task_type:write'])]
    protected bool $allowsComments = true;

    // ==================== STATISTICS & ANALYTICS ====================

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['task_type:read', 'task_type:detail'])]
    protected int $usageCount = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['task_type:read', 'task_type:detail'])]
    protected ?\DateTimeImmutable $lastUsedAt = null;

    // ==================== CONSTRUCTOR ====================

    public function __construct()
    {
        parent::__construct();
    }

    // ==================== CORE GETTERS/SETTERS ====================

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
        $this->code = strtoupper($code);
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

    // ==================== ORGANIZATION ====================

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    // ==================== CLASSIFICATION ====================

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getSubCategory(): ?string
    {
        return $this->subCategory;
    }

    public function setSubCategory(?string $subCategory): self
    {
        $this->subCategory = $subCategory;
        return $this;
    }

    // ==================== VISUAL ====================

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

    public function getBadgeColor(): ?string
    {
        return $this->badgeColor;
    }

    public function setBadgeColor(?string $badgeColor): self
    {
        $this->badgeColor = $badgeColor;
        return $this;
    }

    // ==================== STATUS ====================

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): self
    {
        $this->isSystem = $isSystem;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    // ==================== BEHAVIOR ====================

    public function requiresTimeTracking(): bool
    {
        return $this->requiresTimeTracking;
    }

    public function setRequiresTimeTracking(bool $requiresTimeTracking): self
    {
        $this->requiresTimeTracking = $requiresTimeTracking;
        return $this;
    }

    public function requiresDueDate(): bool
    {
        return $this->requiresDueDate;
    }

    public function setRequiresDueDate(bool $requiresDueDate): self
    {
        $this->requiresDueDate = $requiresDueDate;
        return $this;
    }

    public function requiresAssignee(): bool
    {
        return $this->requiresAssignee;
    }

    public function setRequiresAssignee(bool $requiresAssignee): self
    {
        $this->requiresAssignee = $requiresAssignee;
        return $this;
    }

    public function requiresDescription(): bool
    {
        return $this->requiresDescription;
    }

    public function setRequiresDescription(bool $requiresDescription): self
    {
        $this->requiresDescription = $requiresDescription;
        return $this;
    }

    public function allowsRecurrence(): bool
    {
        return $this->allowsRecurrence;
    }

    public function setAllowsRecurrence(bool $allowsRecurrence): self
    {
        $this->allowsRecurrence = $allowsRecurrence;
        return $this;
    }

    public function allowsSubtasks(): bool
    {
        return $this->allowsSubtasks;
    }

    public function setAllowsSubtasks(bool $allowsSubtasks): self
    {
        $this->allowsSubtasks = $allowsSubtasks;
        return $this;
    }

    public function isAutomated(): bool
    {
        return $this->automated;
    }

    public function setAutomated(bool $automated): self
    {
        $this->automated = $automated;
        return $this;
    }

    public function isNotificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    public function setNotificationsEnabled(bool $notificationsEnabled): self
    {
        $this->notificationsEnabled = $notificationsEnabled;
        return $this;
    }

    // ==================== SLA & PRIORITY ====================

    public function getDefaultDurationMinutes(): ?int
    {
        return $this->defaultDurationMinutes;
    }

    public function setDefaultDurationMinutes(?int $defaultDurationMinutes): self
    {
        $this->defaultDurationMinutes = $defaultDurationMinutes;
        return $this;
    }

    public function getSlaHours(): ?int
    {
        return $this->slaHours;
    }

    public function setSlaHours(?int $slaHours): self
    {
        $this->slaHours = $slaHours;
        return $this;
    }

    public function getDefaultPriority(): ?string
    {
        return $this->defaultPriority;
    }

    public function setDefaultPriority(?string $defaultPriority): self
    {
        $this->defaultPriority = $defaultPriority;
        return $this;
    }

    public function isEscalationEnabled(): bool
    {
        return $this->escalationEnabled;
    }

    public function setEscalationEnabled(bool $escalationEnabled): self
    {
        $this->escalationEnabled = $escalationEnabled;
        return $this;
    }

    public function getEscalationHours(): ?int
    {
        return $this->escalationHours;
    }

    public function setEscalationHours(?int $escalationHours): self
    {
        $this->escalationHours = $escalationHours;
        return $this;
    }

    // ==================== WORKFLOW ====================

    public function getWorkflowTemplate(): ?string
    {
        return $this->workflowTemplate;
    }

    public function setWorkflowTemplate(?string $workflowTemplate): self
    {
        $this->workflowTemplate = $workflowTemplate;
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

    public function getAutomationRules(): ?array
    {
        return $this->automationRules;
    }

    public function setAutomationRules(?array $automationRules): self
    {
        $this->automationRules = $automationRules;
        return $this;
    }

    public function getNotificationRules(): ?array
    {
        return $this->notificationRules;
    }

    public function setNotificationRules(?array $notificationRules): self
    {
        $this->notificationRules = $notificationRules;
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

    // ==================== RELATIONSHIPS ====================

    public function getRelatedEntityType(): ?string
    {
        return $this->relatedEntityType;
    }

    public function setRelatedEntityType(?string $relatedEntityType): self
    {
        $this->relatedEntityType = $relatedEntityType;
        return $this;
    }

    public function allowsAttachments(): bool
    {
        return $this->allowsAttachments;
    }

    public function setAllowsAttachments(bool $allowsAttachments): self
    {
        $this->allowsAttachments = $allowsAttachments;
        return $this;
    }

    public function allowsComments(): bool
    {
        return $this->allowsComments;
    }

    public function setAllowsComments(bool $allowsComments): self
    {
        $this->allowsComments = $allowsComments;
        return $this;
    }

    // ==================== STATISTICS ====================

    public function getUsageCount(): int
    {
        return $this->usageCount;
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

    // ==================== UTILITY METHODS ====================

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Get full category path for hierarchical display
     */
    public function getCategoryPath(): string
    {
        if ($this->subCategory) {
            return $this->category . ' > ' . $this->subCategory;
        }
        return $this->category;
    }

    /**
     * Check if task type has SLA configured
     */
    public function hasSla(): bool
    {
        return $this->slaHours !== null && $this->slaHours > 0;
    }

    /**
     * Get display color with fallback
     */
    public function getDisplayColor(): string
    {
        return $this->color ?? '#6C757D'; // Default gray color
    }

    /**
     * Check if task type is configurable (not system type)
     */
    public function isConfigurable(): bool
    {
        return !$this->isSystem;
    }
}
