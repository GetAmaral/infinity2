<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TalkTypeRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * TalkType Entity - Modern CRM Communication Type Classification System
 *
 * Implements enterprise-grade communication taxonomy following 2025 CRM best practices:
 * - Omnichannel communication tracking (Phone, Email, SMS, Chat, WhatsApp, Video, Social)
 * - Multi-channel support with platform-specific configurations
 * - Visual identification system (icons, colors, badges)
 * - Behavior configuration (automation, notifications, recording)
 * - SLA and response time management
 * - Compliance tracking (GDPR, TCPA, data retention)
 * - Analytics and engagement metrics (98% SMS open rate, 20% email open rate)
 * - Multi-tenant organization isolation
 * - API Platform integration with comprehensive normalization groups
 *
 * Communication Channels (2025 CRM Standards):
 * - Phone Call (inbound, outbound, cold call, callback, voicemail)
 * - Email (campaign, follow-up, newsletter, transactional, automated)
 * - SMS/Text (bulk SMS, personalized, two-way SMS, automated)
 * - WhatsApp (message, business API, broadcast, chatbot)
 * - Live Chat (website chat, in-app chat, chatbot, live agent)
 * - Video Call (meeting, demo, consultation, webinar)
 * - Social Media (Facebook, Instagram, LinkedIn, Twitter/X)
 * - Meeting (in-person, virtual, conference, presentation)
 * - Voice Message (voicemail, voice note, voice broadcast)
 * - Push Notification (mobile push, web push, in-app notification)
 *
 * Key Statistics (2025):
 * - SMS: 98% open rate, 3-minute average read time
 * - Email: 20% open rate, varying engagement
 * - WhatsApp: High engagement, instant deliverability
 * - Live Chat: Real-time, immediate response expected
 * - Social Media: Platform-specific engagement patterns
 *
 * @see https://crm-messaging.cloud/blog/why-sms-in-2025-is-still-king/
 * @see https://textellent.com/blog/crm-with-email-and-sms/
 * @see https://www.messagedesk.com/blog/crm-text-messaging-crm-sms-integrations
 *
 * @author Luminai CRM Team
 */
#[ORM\Entity(repositoryClass: TalkTypeRepository::class)]
#[ORM\Table(name: 'talk_type')]
#[ORM\Index(name: 'idx_talk_type_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_talk_type_code', columns: ['code'])]
#[ORM\Index(name: 'idx_talk_type_channel', columns: ['channel'])]
#[ORM\Index(name: 'idx_talk_type_category', columns: ['category'])]
#[ORM\Index(name: 'idx_talk_type_active', columns: ['active'])]
#[ORM\Index(name: 'idx_talk_type_default', columns: ['default_type'])]
#[ORM\Index(name: 'idx_talk_type_direction', columns: ['direction'])]
#[ORM\Index(name: 'idx_talk_type_automated', columns: ['automated'])]
#[ORM\Index(name: 'idx_talk_type_requires_response', columns: ['requires_response'])]
#[ORM\Index(name: 'idx_talk_type_compliance', columns: ['compliance_enabled'])]
#[ORM\UniqueConstraint(name: 'uniq_talk_type_code_org', columns: ['code', 'organization_id'])]
#[UniqueEntity(fields: ['code', 'organization'], message: 'A talk type with this code already exists in your organization')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['talk_type:read']],
    denormalizationContext: ['groups' => ['talk_type:write']],
    operations: [
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type:read', 'talk_type:detail']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type:read', 'talk_type:list']]
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['talk_type:write', 'talk_type:create']]
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['talk_type:write', 'talk_type:update']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['talk_type:write', 'talk_type:patch']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        // Custom endpoint for active talk types only
        new GetCollection(
            uriTemplate: '/talk-types/active',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type:read', 'talk_type:list']]
        ),
        // Custom endpoint for talk types by channel
        new GetCollection(
            uriTemplate: '/talk-types/channel/{channel}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type:read', 'talk_type:list']]
        ),
        // Custom endpoint for default talk types
        new GetCollection(
            uriTemplate: '/talk-types/defaults',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type:read', 'talk_type:list']]
        )
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 50,
    order: ['sortOrder' => 'ASC', 'name' => 'ASC']
)]
class TalkType extends EntityBase
{
    // ==================== CORE IDENTIFICATION FIELDS (4 fields) ====================

    /**
     * Talk type name (e.g., "Phone Call", "Email Campaign", "SMS Message")
     */
    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Talk type name is required')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Name must be at least 2 characters', maxMessage: 'Name cannot exceed 100 characters')]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private string $name = '';

    /**
     * Unique code identifier (e.g., "PHONE_CALL", "EMAIL_CAMPAIGN", "SMS_TEXT")
     */
    #[ORM\Column(type: 'string', length: 50, unique: false)]
    #[Assert\NotBlank(message: 'Talk type code is required')]
    #[Assert\Length(min: 2, max: 50)]
    #[Assert\Regex(pattern: '/^[A-Z0-9_]+$/', message: 'Code must contain only uppercase letters, numbers, and underscores')]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private string $code = '';

    /**
     * Detailed description of the talk type
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $description = null;

    /**
     * Display label for UI (can be different from name)
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private ?string $displayLabel = null;

    // ==================== ORGANIZATION & MULTI-TENANCY (1 field) ====================

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Organization is required')]
    #[Groups(['talk_type:read', 'talk_type:detail'])]
    private ?Organization $organization = null;

    // ==================== COMMUNICATION CHANNEL CLASSIFICATION (5 fields) ====================

    /**
     * Primary communication channel
     * Choices: phone, email, sms, whatsapp, chat, video, social, meeting, voice_message, push_notification, other
     */
    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Channel is required')]
    #[Assert\Choice(
        choices: ['phone', 'email', 'sms', 'whatsapp', 'chat', 'video', 'social', 'meeting', 'voice_message', 'push_notification', 'other'],
        message: 'Invalid channel. Must be one of: phone, email, sms, whatsapp, chat, video, social, meeting, voice_message, push_notification, other'
    )]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private string $channel = 'other';

    /**
     * Category for grouping (e.g., "sales", "support", "marketing", "internal")
     */
    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Category is required')]
    #[Assert\Choice(
        choices: ['sales', 'support', 'marketing', 'internal', 'customer_service', 'technical', 'administrative', 'outreach', 'other'],
        message: 'Invalid category'
    )]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private string $category = 'other';

    /**
     * Communication direction
     * Choices: inbound, outbound, bidirectional
     */
    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'Direction is required')]
    #[Assert\Choice(
        choices: ['inbound', 'outbound', 'bidirectional'],
        message: 'Invalid direction. Must be one of: inbound, outbound, bidirectional'
    )]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private string $direction = 'bidirectional';

    /**
     * Sub-category for detailed classification
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $subCategory = null;

    /**
     * Platform/Provider (e.g., "Twilio", "SendGrid", "WhatsApp Business", "Zoom")
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $platform = null;

    // ==================== VISUAL IDENTIFICATION (4 fields) ====================

    /**
     * Bootstrap icon class (e.g., "bi-telephone", "bi-envelope", "bi-chat-dots")
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Regex(
        pattern: '/^bi-[a-z0-9-]+$/',
        message: 'Icon must be a valid Bootstrap icon (e.g., bi-telephone)'
    )]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private ?string $icon = null;

    /**
     * Primary color (hex code, e.g., "#3498db" for blue)
     */
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Color must be a valid hex color code (e.g., #3498db)')]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private ?string $color = null;

    /**
     * Badge color for UI display
     */
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Badge color must be a valid hex color code')]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $badgeColor = null;

    /**
     * Background color for visual distinction
     */
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Background color must be a valid hex color code')]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $backgroundColor = null;

    // ==================== STATUS & CONFIGURATION (5 fields) ====================

    /**
     * Talk type is active and can be used
     * Convention: "active" NOT "isActive"
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private bool $active = true;

    /**
     * Default talk type for this channel
     * Convention: "default" NOT "isDefault"
     */
    #[ORM\Column(type: 'boolean', name: 'default_type', options: ['default' => false])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private bool $default = false;

    /**
     * System-defined type (cannot be modified/deleted)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['talk_type:read', 'talk_type:detail'])]
    private bool $system = false;

    /**
     * Display sort order (lower = higher priority)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    #[Assert\Range(min: 0, max: 9999)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private int $sortOrder = 100;

    /**
     * Visible in UI selections
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:list'])]
    private bool $visible = true;

    // ==================== BEHAVIOR & AUTOMATION (9 fields) ====================

    /**
     * Requires follow-up action after communication
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $requiresFollowUp = false;

    /**
     * Requires response from recipient
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $requiresResponse = false;

    /**
     * Requires contact/lead attachment
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $requiresContact = true;

    /**
     * Can be scheduled for future delivery
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $allowsScheduling = true;

    /**
     * Supports bulk/mass communication
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $allowsBulkSending = false;

    /**
     * Communication can be recorded/logged
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $allowsRecording = true;

    /**
     * Supports file attachments
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $allowsAttachments = true;

    /**
     * Automated communication (no manual intervention)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $automated = false;

    /**
     * Notifications enabled for this type
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $notificationsEnabled = true;

    // ==================== SLA & RESPONSE TIME (5 fields) ====================

    /**
     * Expected response time in minutes (SLA)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive(message: 'Response time must be a positive number')]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?int $expectedResponseMinutes = null;

    /**
     * Default duration/length in minutes
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive(message: 'Duration must be a positive number')]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?int $defaultDurationMinutes = null;

    /**
     * Default priority level
     * Choices: low, normal, high, urgent, critical
     */
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['low', 'normal', 'high', 'urgent', 'critical'],
        message: 'Invalid priority. Must be one of: low, normal, high, urgent, critical'
    )]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $defaultPriority = 'normal';

    /**
     * SLA tracking enabled
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $slaEnabled = false;

    /**
     * SLA hours threshold
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?int $slaHours = null;

    // ==================== COMPLIANCE & PRIVACY (6 fields) ====================

    /**
     * Compliance tracking enabled (GDPR, TCPA, etc.)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $complianceEnabled = false;

    /**
     * Requires opt-in consent before sending
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $requiresOptIn = false;

    /**
     * Respects do-not-contact preferences
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $respectsDoNotContact = true;

    /**
     * Data retention period in days (0 = indefinite)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private int $dataRetentionDays = 0;

    /**
     * Compliance regulations applicable (JSON array)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?array $complianceRegulations = null;

    /**
     * Privacy level (public, internal, confidential, restricted)
     */
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['public', 'internal', 'confidential', 'restricted'],
        message: 'Invalid privacy level'
    )]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $privacyLevel = 'internal';

    // ==================== TEMPLATES & CONTENT (4 fields) ====================

    /**
     * Default message template
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 5000)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $defaultTemplate = null;

    /**
     * Subject line template (for email/messages)
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $subjectTemplate = null;

    /**
     * Available template variables (JSON array)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?array $templateVariables = null;

    /**
     * Signature/footer template
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $signatureTemplate = null;

    // ==================== INTEGRATION & WORKFLOW (5 fields) ====================

    /**
     * Webhook URL for external integrations
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Assert\Url]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $webhookUrl = null;

    /**
     * API endpoint for channel-specific operations
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $apiEndpoint = null;

    /**
     * Custom workflow automation rules (JSON)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?array $automationRules = null;

    /**
     * Integration configuration (API keys, credentials, etc.)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?array $integrationConfig = null;

    /**
     * Custom metadata (JSON)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?array $metadata = null;

    // ==================== ANALYTICS & METRICS (6 fields) ====================

    /**
     * Expected open rate percentage (e.g., 98 for SMS, 20 for email)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?int $expectedOpenRate = null;

    /**
     * Expected response rate percentage
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?int $expectedResponseRate = null;

    /**
     * Average engagement time in minutes
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?int $avgEngagementMinutes = null;

    /**
     * Total usage count (number of communications sent)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['talk_type:read', 'talk_type:detail'])]
    private int $usageCount = 0;

    /**
     * Last time this type was used
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['talk_type:read', 'talk_type:detail'])]
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * Track engagement metrics (opens, clicks, responses)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $trackEngagement = true;

    // ==================== COST & BILLING (3 fields) ====================

    /**
     * Cost per communication (in cents/smallest currency unit)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?int $costPerUnit = null;

    /**
     * Currency code (ISO 4217)
     */
    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    #[Assert\Length(exactly: 3)]
    #[Assert\Regex(pattern: '/^[A-Z]{3}$/', message: 'Currency must be a valid ISO 4217 code (e.g., USD, EUR)')]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private ?string $currency = 'USD';

    /**
     * Billable communication type
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['talk_type:read', 'talk_type:write', 'talk_type:detail'])]
    private bool $billable = false;

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

    public function getDisplayLabel(): ?string
    {
        return $this->displayLabel ?? $this->name;
    }

    public function setDisplayLabel(?string $displayLabel): self
    {
        $this->displayLabel = $displayLabel;
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

    // ==================== CHANNEL & CLASSIFICATION ====================

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel;
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

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): self
    {
        $this->direction = $direction;
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

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(?string $platform): self
    {
        $this->platform = $platform;
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

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor): self
    {
        $this->backgroundColor = $backgroundColor;
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
        return $this->default;
    }

    public function setDefault(bool $default): self
    {
        $this->default = $default;
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

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
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

    // ==================== BEHAVIOR ====================

    public function requiresFollowUp(): bool
    {
        return $this->requiresFollowUp;
    }

    public function setRequiresFollowUp(bool $requiresFollowUp): self
    {
        $this->requiresFollowUp = $requiresFollowUp;
        return $this;
    }

    public function requiresResponse(): bool
    {
        return $this->requiresResponse;
    }

    public function setRequiresResponse(bool $requiresResponse): self
    {
        $this->requiresResponse = $requiresResponse;
        return $this;
    }

    public function requiresContact(): bool
    {
        return $this->requiresContact;
    }

    public function setRequiresContact(bool $requiresContact): self
    {
        $this->requiresContact = $requiresContact;
        return $this;
    }

    public function allowsScheduling(): bool
    {
        return $this->allowsScheduling;
    }

    public function setAllowsScheduling(bool $allowsScheduling): self
    {
        $this->allowsScheduling = $allowsScheduling;
        return $this;
    }

    public function allowsBulkSending(): bool
    {
        return $this->allowsBulkSending;
    }

    public function setAllowsBulkSending(bool $allowsBulkSending): self
    {
        $this->allowsBulkSending = $allowsBulkSending;
        return $this;
    }

    public function allowsRecording(): bool
    {
        return $this->allowsRecording;
    }

    public function setAllowsRecording(bool $allowsRecording): self
    {
        $this->allowsRecording = $allowsRecording;
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

    // ==================== SLA & RESPONSE TIME ====================

    public function getExpectedResponseMinutes(): ?int
    {
        return $this->expectedResponseMinutes;
    }

    public function setExpectedResponseMinutes(?int $expectedResponseMinutes): self
    {
        $this->expectedResponseMinutes = $expectedResponseMinutes;
        return $this;
    }

    public function getDefaultDurationMinutes(): ?int
    {
        return $this->defaultDurationMinutes;
    }

    public function setDefaultDurationMinutes(?int $defaultDurationMinutes): self
    {
        $this->defaultDurationMinutes = $defaultDurationMinutes;
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

    public function isSlaEnabled(): bool
    {
        return $this->slaEnabled;
    }

    public function setSlaEnabled(bool $slaEnabled): self
    {
        $this->slaEnabled = $slaEnabled;
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

    // ==================== COMPLIANCE & PRIVACY ====================

    public function isComplianceEnabled(): bool
    {
        return $this->complianceEnabled;
    }

    public function setComplianceEnabled(bool $complianceEnabled): self
    {
        $this->complianceEnabled = $complianceEnabled;
        return $this;
    }

    public function requiresOptIn(): bool
    {
        return $this->requiresOptIn;
    }

    public function setRequiresOptIn(bool $requiresOptIn): self
    {
        $this->requiresOptIn = $requiresOptIn;
        return $this;
    }

    public function respectsDoNotContact(): bool
    {
        return $this->respectsDoNotContact;
    }

    public function setRespectsDoNotContact(bool $respectsDoNotContact): self
    {
        $this->respectsDoNotContact = $respectsDoNotContact;
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

    public function getComplianceRegulations(): ?array
    {
        return $this->complianceRegulations;
    }

    public function setComplianceRegulations(?array $complianceRegulations): self
    {
        $this->complianceRegulations = $complianceRegulations;
        return $this;
    }

    public function getPrivacyLevel(): ?string
    {
        return $this->privacyLevel;
    }

    public function setPrivacyLevel(?string $privacyLevel): self
    {
        $this->privacyLevel = $privacyLevel;
        return $this;
    }

    // ==================== TEMPLATES ====================

    public function getDefaultTemplate(): ?string
    {
        return $this->defaultTemplate;
    }

    public function setDefaultTemplate(?string $defaultTemplate): self
    {
        $this->defaultTemplate = $defaultTemplate;
        return $this;
    }

    public function getSubjectTemplate(): ?string
    {
        return $this->subjectTemplate;
    }

    public function setSubjectTemplate(?string $subjectTemplate): self
    {
        $this->subjectTemplate = $subjectTemplate;
        return $this;
    }

    public function getTemplateVariables(): ?array
    {
        return $this->templateVariables;
    }

    public function setTemplateVariables(?array $templateVariables): self
    {
        $this->templateVariables = $templateVariables;
        return $this;
    }

    public function getSignatureTemplate(): ?string
    {
        return $this->signatureTemplate;
    }

    public function setSignatureTemplate(?string $signatureTemplate): self
    {
        $this->signatureTemplate = $signatureTemplate;
        return $this;
    }

    // ==================== INTEGRATION ====================

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl(?string $webhookUrl): self
    {
        $this->webhookUrl = $webhookUrl;
        return $this;
    }

    public function getApiEndpoint(): ?string
    {
        return $this->apiEndpoint;
    }

    public function setApiEndpoint(?string $apiEndpoint): self
    {
        $this->apiEndpoint = $apiEndpoint;
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

    public function getIntegrationConfig(): ?array
    {
        return $this->integrationConfig;
    }

    public function setIntegrationConfig(?array $integrationConfig): self
    {
        $this->integrationConfig = $integrationConfig;
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

    // ==================== ANALYTICS ====================

    public function getExpectedOpenRate(): ?int
    {
        return $this->expectedOpenRate;
    }

    public function setExpectedOpenRate(?int $expectedOpenRate): self
    {
        $this->expectedOpenRate = $expectedOpenRate;
        return $this;
    }

    public function getExpectedResponseRate(): ?int
    {
        return $this->expectedResponseRate;
    }

    public function setExpectedResponseRate(?int $expectedResponseRate): self
    {
        $this->expectedResponseRate = $expectedResponseRate;
        return $this;
    }

    public function getAvgEngagementMinutes(): ?int
    {
        return $this->avgEngagementMinutes;
    }

    public function setAvgEngagementMinutes(?int $avgEngagementMinutes): self
    {
        $this->avgEngagementMinutes = $avgEngagementMinutes;
        return $this;
    }

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

    public function isTrackEngagement(): bool
    {
        return $this->trackEngagement;
    }

    public function setTrackEngagement(bool $trackEngagement): self
    {
        $this->trackEngagement = $trackEngagement;
        return $this;
    }

    // ==================== COST & BILLING ====================

    public function getCostPerUnit(): ?int
    {
        return $this->costPerUnit;
    }

    public function setCostPerUnit(?int $costPerUnit): self
    {
        $this->costPerUnit = $costPerUnit;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = strtoupper($currency ?? 'USD');
        return $this;
    }

    public function isBillable(): bool
    {
        return $this->billable;
    }

    public function setBillable(bool $billable): self
    {
        $this->billable = $billable;
        return $this;
    }

    // ==================== UTILITY METHODS ====================

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Get full classification path for display
     */
    public function getClassificationPath(): string
    {
        $parts = [$this->channel, $this->category];
        if ($this->subCategory) {
            $parts[] = $this->subCategory;
        }
        return implode(' > ', $parts);
    }

    /**
     * Get display color with fallback
     */
    public function getDisplayColor(): string
    {
        return $this->color ?? $this->getDefaultChannelColor();
    }

    /**
     * Get default color based on channel
     */
    private function getDefaultChannelColor(): string
    {
        return match ($this->channel) {
            'phone' => '#3498db',        // Blue
            'email' => '#e74c3c',        // Red
            'sms' => '#2ecc71',          // Green
            'whatsapp' => '#25d366',     // WhatsApp green
            'chat' => '#9b59b6',         // Purple
            'video' => '#1abc9c',        // Turquoise
            'social' => '#3b5998',       // Facebook blue
            'meeting' => '#f39c12',      // Orange
            'voice_message' => '#34495e', // Dark gray
            'push_notification' => '#e67e22', // Orange-red
            default => '#95a5a6',        // Gray
        };
    }

    /**
     * Get default icon based on channel
     */
    public function getDefaultIcon(): string
    {
        return $this->icon ?? match ($this->channel) {
            'phone' => 'bi-telephone',
            'email' => 'bi-envelope',
            'sms' => 'bi-chat-text',
            'whatsapp' => 'bi-whatsapp',
            'chat' => 'bi-chat-dots',
            'video' => 'bi-camera-video',
            'social' => 'bi-share',
            'meeting' => 'bi-calendar-event',
            'voice_message' => 'bi-mic',
            'push_notification' => 'bi-bell',
            default => 'bi-chat-square',
        };
    }

    /**
     * Check if talk type is configurable (not system type)
     */
    public function isConfigurable(): bool
    {
        return !$this->system;
    }

    /**
     * Check if SLA is configured
     */
    public function hasSla(): bool
    {
        return $this->slaEnabled && $this->slaHours !== null && $this->slaHours > 0;
    }

    /**
     * Get formatted cost for display
     */
    public function getFormattedCost(): string
    {
        if ($this->costPerUnit === null) {
            return 'N/A';
        }
        return number_format($this->costPerUnit / 100, 2) . ' ' . $this->currency;
    }
}
