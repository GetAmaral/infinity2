<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CommunicationMethodRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * CommunicationMethod Entity - Modern CRM Communication Channel Management System
 *
 * Implements enterprise-grade communication channel taxonomy following 2025 CRM best practices:
 * - Omnichannel communication channel management (Phone, Email, SMS, Chat, WhatsApp, Video, Social)
 * - Multi-platform support with provider-specific configurations
 * - Visual identification system (icons, colors, badges)
 * - Behavior configuration (automation, verification, two-way communication)
 * - Deliverability and engagement metrics (98% SMS open rate, 20% email open rate)
 * - Compliance tracking (GDPR, TCPA, opt-in/opt-out management)
 * - Cost management and billing tracking
 * - Multi-tenant organization isolation
 * - API Platform integration with comprehensive normalization groups
 *
 * Communication Channels (2025 CRM Standards):
 * - Phone (voice call, VoIP, landline, mobile)
 * - Email (SMTP, transactional, marketing, personal)
 * - SMS (short message, bulk SMS, two-way SMS, automated)
 * - WhatsApp (message, business API, broadcast)
 * - Live Chat (website chat, in-app chat, messenger)
 * - Video (video call, webinar, screen sharing)
 * - Social Media (Facebook, Instagram, LinkedIn, Twitter/X, TikTok)
 * - Push Notification (mobile push, web push, in-app)
 * - Voice Message (voicemail, voice note)
 * - Postal Mail (letter, package, postcard)
 * - Fax (traditional, online fax)
 * - Messaging Apps (Telegram, WeChat, Viber, Signal)
 *
 * Key Statistics (2025):
 * - SMS: 98% open rate, 3-minute average read time, instant delivery
 * - Email: 20% average open rate, varying engagement by industry
 * - WhatsApp: 70%+ open rate, high engagement, instant deliverability
 * - Push Notifications: 50-60% opt-in rate, immediate delivery
 * - Live Chat: Real-time, immediate response expected
 * - Social Media: Platform-specific engagement patterns
 *
 * Provider Examples:
 * - Email: SendGrid, Mailgun, AWS SES, Office 365
 * - SMS: Twilio, Plivo, MessageBird, Vonage
 * - WhatsApp: WhatsApp Business API, Twilio WhatsApp
 * - Voice: Twilio Voice, Vonage Voice, RingCentral
 * - Video: Zoom, Microsoft Teams, Google Meet
 * - Chat: Intercom, Drift, LiveChat, Zendesk
 *
 * @see https://gettalkative.com/info/crm-communication-channels
 * @see https://www.bigcontacts.com/blog/multi-channel-crm/
 * @see https://www.touchpoint.com/blog/customer-contact-channels/
 * @see https://clevertap.com/blog/a-2025-guide-to-business-messaging-comparing-sms-whatsapp-and-rcs/
 *
 * @author Luminai CRM Team
 */
#[ORM\Entity(repositoryClass: CommunicationMethodRepository::class)]
#[ORM\Table(name: 'communication_method')]
#[ORM\Index(name: 'idx_comm_method_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_comm_method_code', columns: ['code'])]
#[ORM\Index(name: 'idx_comm_method_name', columns: ['method_name'])]
#[ORM\Index(name: 'idx_comm_method_channel_type', columns: ['channel_type'])]
#[ORM\Index(name: 'idx_comm_method_category', columns: ['category'])]
#[ORM\Index(name: 'idx_comm_method_active', columns: ['active'])]
#[ORM\Index(name: 'idx_comm_method_default', columns: ['default_method'])]
#[ORM\Index(name: 'idx_comm_method_automated', columns: ['automated'])]
#[ORM\Index(name: 'idx_comm_method_verified', columns: ['verified'])]
#[ORM\Index(name: 'idx_comm_method_two_way', columns: ['supports_two_way'])]
#[ORM\Index(name: 'idx_comm_method_provider', columns: ['provider'])]
#[ORM\Index(name: 'idx_comm_method_priority', columns: ['priority'])]
#[ORM\UniqueConstraint(name: 'uniq_comm_method_code_org', columns: ['code', 'organization_id'])]
#[UniqueEntity(fields: ['code', 'organization'], message: 'A communication method with this code already exists in your organization')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['communication_method:read']],
    denormalizationContext: ['groups' => ['communication_method:write']],
    operations: [
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['communication_method:read', 'communication_method:detail']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['communication_method:read', 'communication_method:list']]
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['communication_method:write', 'communication_method:create']]
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['communication_method:write', 'communication_method:update']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['communication_method:write', 'communication_method:patch']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        // Custom endpoint for active communication methods only
        new GetCollection(
            uriTemplate: '/communication-methods/active',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['communication_method:read', 'communication_method:list']]
        ),
        // Custom endpoint for verified methods
        new GetCollection(
            uriTemplate: '/communication-methods/verified',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['communication_method:read', 'communication_method:list']]
        ),
        // Custom endpoint for methods by channel type
        new GetCollection(
            uriTemplate: '/communication-methods/channel/{channelType}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['communication_method:read', 'communication_method:list']]
        ),
        // Custom endpoint for default methods
        new GetCollection(
            uriTemplate: '/communication-methods/defaults',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['communication_method:read', 'communication_method:list']]
        ),
        // Custom endpoint for two-way communication methods
        new GetCollection(
            uriTemplate: '/communication-methods/two-way',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['communication_method:read', 'communication_method:list']]
        )
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 50,
    order: ['priority' => 'ASC', 'sortOrder' => 'ASC', 'methodName' => 'ASC']
)]
class CommunicationMethod extends EntityBase
{
    // ==================== CORE IDENTIFICATION FIELDS (5 fields) ====================

    /**
     * Communication method name (e.g., "Business Email", "Primary Phone", "WhatsApp Business")
     */
    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Communication method name is required')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Method name must be at least 2 characters', maxMessage: 'Method name cannot exceed 100 characters')]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private string $methodName = '';

    /**
     * Unique code identifier (e.g., "EMAIL_PRIMARY", "SMS_MARKETING", "WHATSAPP_BUSINESS")
     */
    #[ORM\Column(type: 'string', length: 50, unique: false)]
    #[Assert\NotBlank(message: 'Communication method code is required')]
    #[Assert\Length(min: 2, max: 50)]
    #[Assert\Regex(pattern: '/^[A-Z0-9_]+$/', message: 'Code must contain only uppercase letters, numbers, and underscores')]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private string $code = '';

    /**
     * Detailed description of the communication method
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters')]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $description = null;

    /**
     * Display label for UI (can be different from methodName)
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private ?string $displayLabel = null;

    /**
     * Help text or instructions for using this method
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $helpText = null;

    // ==================== ORGANIZATION & MULTI-TENANCY (1 field) ====================

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Organization is required')]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private ?Organization $organization = null;

    // ==================== CHANNEL TYPE CLASSIFICATION (6 fields) ====================

    /**
     * Primary communication channel type
     * Choices: phone, email, sms, whatsapp, chat, video, social, push_notification, voice_message, postal_mail, fax, messaging_app, other
     */
    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Channel type is required')]
    #[Assert\Choice(
        choices: ['phone', 'email', 'sms', 'whatsapp', 'chat', 'video', 'social', 'push_notification', 'voice_message', 'postal_mail', 'fax', 'messaging_app', 'other'],
        message: 'Invalid channel type. Must be one of: phone, email, sms, whatsapp, chat, video, social, push_notification, voice_message, postal_mail, fax, messaging_app, other'
    )]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private string $channelType = 'other';

    /**
     * Category for grouping (e.g., "primary", "marketing", "support", "transactional", "emergency")
     */
    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Category is required')]
    #[Assert\Choice(
        choices: ['primary', 'secondary', 'marketing', 'support', 'transactional', 'emergency', 'automated', 'manual', 'other'],
        message: 'Invalid category'
    )]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private string $category = 'primary';

    /**
     * Sub-category for detailed classification
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $subCategory = null;

    /**
     * Platform/Provider name (e.g., "Twilio", "SendGrid", "WhatsApp Business", "Zoom", "Gmail")
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $provider = null;

    /**
     * Provider service type (e.g., "API", "SMTP", "SDK", "Webhook", "Manual")
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ['api', 'smtp', 'sdk', 'webhook', 'manual', 'integration', 'other'],
        message: 'Invalid provider service type'
    )]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $providerServiceType = null;

    /**
     * Protocol used (e.g., "HTTP", "SMTP", "SIP", "WebSocket", "REST")
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $protocol = null;

    // ==================== VISUAL IDENTIFICATION (5 fields) ====================

    /**
     * Bootstrap icon class (e.g., "bi-telephone", "bi-envelope", "bi-whatsapp")
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Regex(
        pattern: '/^bi-[a-z0-9-]+$/',
        message: 'Icon must be a valid Bootstrap icon (e.g., bi-telephone)'
    )]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private ?string $icon = null;

    /**
     * Primary color (hex code, e.g., "#3498db" for blue)
     */
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Color must be a valid hex color code (e.g., #3498db)')]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private ?string $color = null;

    /**
     * Badge color for UI display
     */
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Badge color must be a valid hex color code')]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $badgeColor = null;

    /**
     * Background color for visual distinction
     */
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Background color must be a valid hex color code')]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $backgroundColor = null;

    /**
     * Emoji representation (optional)
     */
    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Assert\Length(max: 10)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private ?string $emoji = null;

    // ==================== STATUS & CONFIGURATION (7 fields) ====================

    /**
     * Communication method is active and can be used
     * Convention: "active" NOT "isActive"
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private bool $active = true;

    /**
     * Default communication method for this channel type
     * Convention: "default" NOT "isDefault"
     */
    #[ORM\Column(type: 'boolean', name: 'default_method', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private bool $default = false;

    /**
     * Method is verified and ready for production use
     * Convention: "verified" NOT "isVerified"
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private bool $verified = false;

    /**
     * System-defined method (cannot be modified/deleted)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private bool $system = false;

    /**
     * Display sort order (lower = higher priority)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    #[Assert\Range(min: 0, max: 9999)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private int $sortOrder = 100;

    /**
     * Visible in UI selections
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private bool $visible = true;

    /**
     * Priority level (low, normal, high, urgent, critical)
     */
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'normal'])]
    #[Assert\Choice(
        choices: ['low', 'normal', 'high', 'urgent', 'critical'],
        message: 'Invalid priority. Must be one of: low, normal, high, urgent, critical'
    )]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:list'])]
    private string $priority = 'normal';

    // ==================== CAPABILITIES & FEATURES (12 fields) ====================

    /**
     * Supports two-way communication (sending and receiving)
     * Convention: "supports_two_way" NOT "isTwoWay"
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsTwoWay = true;

    /**
     * Supports real-time communication
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsRealtime = false;

    /**
     * Supports file attachments
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsAttachments = true;

    /**
     * Supports rich media (images, videos, etc.)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsRichMedia = false;

    /**
     * Supports formatting (bold, italic, links, etc.)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsFormatting = false;

    /**
     * Supports scheduling for future delivery
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsScheduling = true;

    /**
     * Supports bulk/mass sending
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsBulkSending = false;

    /**
     * Supports templates
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsTemplates = true;

    /**
     * Supports tracking (opens, clicks, delivery status)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsTracking = false;

    /**
     * Supports encryption (end-to-end or transport)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsEncryption = false;

    /**
     * Supports delivery confirmation/receipts
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsDeliveryReceipts = false;

    /**
     * Supports read receipts
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsReadReceipts = false;

    // ==================== BEHAVIOR & AUTOMATION (8 fields) ====================

    /**
     * Automated communication (no manual intervention)
     * Convention: "automated" NOT "isAutomated"
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $automated = false;

    /**
     * Requires manual approval before sending
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $requiresApproval = false;

    /**
     * Requires verification before use
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $requiresVerification = false;

    /**
     * Auto-retry on failure
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $autoRetry = false;

    /**
     * Maximum retry attempts
     */
    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 3])]
    #[Assert\Range(min: 0, max: 10)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $maxRetries = 3;

    /**
     * Retry delay in seconds
     */
    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 60])]
    #[Assert\Positive]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $retryDelaySeconds = 60;

    /**
     * Notifications enabled for this method
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $notificationsEnabled = true;

    /**
     * Logging enabled for this method
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $loggingEnabled = true;

    // ==================== LIMITS & CONSTRAINTS (7 fields) ====================

    /**
     * Maximum message length (characters or bytes)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $maxMessageLength = null;

    /**
     * Maximum attachment size in bytes
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $maxAttachmentSize = null;

    /**
     * Maximum recipients per message
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $maxRecipients = null;

    /**
     * Daily send limit
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $dailyLimit = null;

    /**
     * Hourly send limit
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $hourlyLimit = null;

    /**
     * Rate limit (messages per second)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $rateLimit = null;

    /**
     * Allowed file types (JSON array, e.g., ["pdf", "jpg", "png"])
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?array $allowedFileTypes = null;

    // ==================== DELIVERABILITY & METRICS (8 fields) ====================

    /**
     * Expected delivery rate percentage (0-100)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $expectedDeliveryRate = null;

    /**
     * Expected open rate percentage (0-100)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $expectedOpenRate = null;

    /**
     * Expected response rate percentage (0-100)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $expectedResponseRate = null;

    /**
     * Average delivery time in seconds
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $avgDeliveryTimeSeconds = null;

    /**
     * Average response time in minutes
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $avgResponseTimeMinutes = null;

    /**
     * Reliability score (0-100)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $reliabilityScore = null;

    /**
     * Uptime percentage (0-100)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $uptimePercentage = null;

    /**
     * Track engagement metrics (opens, clicks, responses)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $trackEngagement = true;

    // ==================== COMPLIANCE & PRIVACY (9 fields) ====================

    /**
     * Compliance tracking enabled (GDPR, TCPA, CAN-SPAM, etc.)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $complianceEnabled = false;

    /**
     * Requires opt-in consent before use
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $requiresOptIn = false;

    /**
     * Respects do-not-contact preferences
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $respectsDoNotContact = true;

    /**
     * Supports opt-out mechanism
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $supportsOptOut = true;

    /**
     * Data retention period in days (0 = indefinite)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private int $dataRetentionDays = 0;

    /**
     * Compliance regulations applicable (JSON array, e.g., ["GDPR", "TCPA", "CAN-SPAM"])
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?array $complianceRegulations = null;

    /**
     * Privacy level (public, internal, confidential, restricted)
     */
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['public', 'internal', 'confidential', 'restricted'],
        message: 'Invalid privacy level'
    )]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $privacyLevel = 'internal';

    /**
     * Requires consent form/agreement
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $requiresConsentForm = false;

    /**
     * Consent form URL or template ID
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $consentFormUrl = null;

    // ==================== COST & BILLING (6 fields) ====================

    /**
     * Cost per message/communication (in cents/smallest currency unit)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $costPerUnit = null;

    /**
     * Currency code (ISO 4217, e.g., USD, EUR, GBP)
     */
    #[ORM\Column(type: 'string', length: 3, nullable: true, options: ['default' => 'USD'])]
    #[Assert\Length(exactly: 3)]
    #[Assert\Regex(pattern: '/^[A-Z]{3}$/', message: 'Currency must be a valid ISO 4217 code (e.g., USD, EUR)')]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $currency = 'USD';

    /**
     * Billable communication method
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private bool $billable = false;

    /**
     * Monthly subscription cost (in cents)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $monthlySubscriptionCost = null;

    /**
     * Setup/initialization cost (in cents, one-time)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $setupCost = null;

    /**
     * Free tier limit (messages per month)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?int $freeTierLimit = null;

    // ==================== CONFIGURATION & CREDENTIALS (8 fields) ====================

    /**
     * API endpoint URL
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Assert\Url]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $apiEndpoint = null;

    /**
     * API key (encrypted in production)
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['communication_method:write', 'communication_method:detail'])]
    private ?string $apiKey = null;

    /**
     * API secret (encrypted in production)
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['communication_method:write', 'communication_method:detail'])]
    private ?string $apiSecret = null;

    /**
     * Account identifier (e.g., sender ID, phone number, email address)
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $accountIdentifier = null;

    /**
     * Sender name/label
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $senderName = null;

    /**
     * Reply-to address/number
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $replyToAddress = null;

    /**
     * Webhook URL for receiving events/responses
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Assert\Url]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $webhookUrl = null;

    /**
     * Configuration settings (JSON)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?array $config = null;

    // ==================== TEMPLATES & DEFAULTS (5 fields) ====================

    /**
     * Default message template
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 5000)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $defaultTemplate = null;

    /**
     * Subject line template (for email/messages)
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $subjectTemplate = null;

    /**
     * Available template variables (JSON array)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?array $templateVariables = null;

    /**
     * Signature/footer template
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $signatureTemplate = null;

    /**
     * Default sender signature
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $defaultSignature = null;

    // ==================== VERIFICATION & VALIDATION (6 fields) ====================

    /**
     * Verification status (pending, verified, failed, expired)
     */
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['pending', 'verified', 'failed', 'expired', 'not_required'],
        message: 'Invalid verification status'
    )]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private ?string $verificationStatus = 'not_required';

    /**
     * Verification date
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private ?\DateTimeImmutable $verifiedAt = null;

    /**
     * Verification expires at
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private ?\DateTimeImmutable $verificationExpiresAt = null;

    /**
     * Verification code or token
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['communication_method:detail'])]
    private ?string $verificationCode = null;

    /**
     * Last verification check date
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private ?\DateTimeImmutable $lastVerificationCheck = null;

    /**
     * Health check endpoint URL
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Assert\Url]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?string $healthCheckUrl = null;

    // ==================== STATISTICS & ANALYTICS (7 fields) ====================

    /**
     * Total messages sent via this method
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private int $totalSent = 0;

    /**
     * Total messages delivered successfully
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private int $totalDelivered = 0;

    /**
     * Total messages failed
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private int $totalFailed = 0;

    /**
     * Total cost incurred (in cents)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private int $totalCost = 0;

    /**
     * Usage count (number of times used)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private int $usageCount = 0;

    /**
     * Last time this method was used
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * Last successful delivery timestamp
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:detail'])]
    private ?\DateTimeImmutable $lastSuccessAt = null;

    // ==================== INTEGRATION & WORKFLOW (4 fields) ====================

    /**
     * Custom workflow automation rules (JSON)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?array $automationRules = null;

    /**
     * Integration configuration (platform-specific settings)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?array $integrationConfig = null;

    /**
     * Custom metadata (JSON)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?array $metadata = null;

    /**
     * Tags for categorization (JSON array)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['communication_method:read', 'communication_method:write', 'communication_method:detail'])]
    private ?array $tags = null;

    // ==================== CONSTRUCTOR ====================

    public function __construct()
    {
        parent::__construct();
    }

    // ==================== CORE GETTERS/SETTERS ====================

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function setMethodName(string $methodName): self
    {
        $this->methodName = $methodName;
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
        return $this->displayLabel ?? $this->methodName;
    }

    public function setDisplayLabel(?string $displayLabel): self
    {
        $this->displayLabel = $displayLabel;
        return $this;
    }

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    public function setHelpText(?string $helpText): self
    {
        $this->helpText = $helpText;
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

    // ==================== CHANNEL TYPE ====================

    public function getChannelType(): string
    {
        return $this->channelType;
    }

    public function setChannelType(string $channelType): self
    {
        $this->channelType = $channelType;
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

    public function getSubCategory(): ?string
    {
        return $this->subCategory;
    }

    public function setSubCategory(?string $subCategory): self
    {
        $this->subCategory = $subCategory;
        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getProviderServiceType(): ?string
    {
        return $this->providerServiceType;
    }

    public function setProviderServiceType(?string $providerServiceType): self
    {
        $this->providerServiceType = $providerServiceType;
        return $this;
    }

    public function getProtocol(): ?string
    {
        return $this->protocol;
    }

    public function setProtocol(?string $protocol): self
    {
        $this->protocol = $protocol;
        return $this;
    }

    // ==================== VISUAL IDENTIFICATION ====================

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

    public function getEmoji(): ?string
    {
        return $this->emoji;
    }

    public function setEmoji(?string $emoji): self
    {
        $this->emoji = $emoji;
        return $this;
    }

    // ==================== STATUS & CONFIGURATION ====================

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

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;
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

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    // ==================== CAPABILITIES & FEATURES ====================

    public function supportsTwoWay(): bool
    {
        return $this->supportsTwoWay;
    }

    public function setSupportsTwoWay(bool $supportsTwoWay): self
    {
        $this->supportsTwoWay = $supportsTwoWay;
        return $this;
    }

    public function supportsRealtime(): bool
    {
        return $this->supportsRealtime;
    }

    public function setSupportsRealtime(bool $supportsRealtime): self
    {
        $this->supportsRealtime = $supportsRealtime;
        return $this;
    }

    public function supportsAttachments(): bool
    {
        return $this->supportsAttachments;
    }

    public function setSupportsAttachments(bool $supportsAttachments): self
    {
        $this->supportsAttachments = $supportsAttachments;
        return $this;
    }

    public function supportsRichMedia(): bool
    {
        return $this->supportsRichMedia;
    }

    public function setSupportsRichMedia(bool $supportsRichMedia): self
    {
        $this->supportsRichMedia = $supportsRichMedia;
        return $this;
    }

    public function supportsFormatting(): bool
    {
        return $this->supportsFormatting;
    }

    public function setSupportsFormatting(bool $supportsFormatting): self
    {
        $this->supportsFormatting = $supportsFormatting;
        return $this;
    }

    public function supportsScheduling(): bool
    {
        return $this->supportsScheduling;
    }

    public function setSupportsScheduling(bool $supportsScheduling): self
    {
        $this->supportsScheduling = $supportsScheduling;
        return $this;
    }

    public function supportsBulkSending(): bool
    {
        return $this->supportsBulkSending;
    }

    public function setSupportsBulkSending(bool $supportsBulkSending): self
    {
        $this->supportsBulkSending = $supportsBulkSending;
        return $this;
    }

    public function supportsTemplates(): bool
    {
        return $this->supportsTemplates;
    }

    public function setSupportsTemplates(bool $supportsTemplates): self
    {
        $this->supportsTemplates = $supportsTemplates;
        return $this;
    }

    public function supportsTracking(): bool
    {
        return $this->supportsTracking;
    }

    public function setSupportsTracking(bool $supportsTracking): self
    {
        $this->supportsTracking = $supportsTracking;
        return $this;
    }

    public function supportsEncryption(): bool
    {
        return $this->supportsEncryption;
    }

    public function setSupportsEncryption(bool $supportsEncryption): self
    {
        $this->supportsEncryption = $supportsEncryption;
        return $this;
    }

    public function supportsDeliveryReceipts(): bool
    {
        return $this->supportsDeliveryReceipts;
    }

    public function setSupportsDeliveryReceipts(bool $supportsDeliveryReceipts): self
    {
        $this->supportsDeliveryReceipts = $supportsDeliveryReceipts;
        return $this;
    }

    public function supportsReadReceipts(): bool
    {
        return $this->supportsReadReceipts;
    }

    public function setSupportsReadReceipts(bool $supportsReadReceipts): self
    {
        $this->supportsReadReceipts = $supportsReadReceipts;
        return $this;
    }

    // ==================== BEHAVIOR & AUTOMATION ====================

    public function isAutomated(): bool
    {
        return $this->automated;
    }

    public function setAutomated(bool $automated): self
    {
        $this->automated = $automated;
        return $this;
    }

    public function requiresApproval(): bool
    {
        return $this->requiresApproval;
    }

    public function setRequiresApproval(bool $requiresApproval): self
    {
        $this->requiresApproval = $requiresApproval;
        return $this;
    }

    public function requiresVerification(): bool
    {
        return $this->requiresVerification;
    }

    public function setRequiresVerification(bool $requiresVerification): self
    {
        $this->requiresVerification = $requiresVerification;
        return $this;
    }

    public function isAutoRetry(): bool
    {
        return $this->autoRetry;
    }

    public function setAutoRetry(bool $autoRetry): self
    {
        $this->autoRetry = $autoRetry;
        return $this;
    }

    public function getMaxRetries(): ?int
    {
        return $this->maxRetries;
    }

    public function setMaxRetries(?int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;
        return $this;
    }

    public function getRetryDelaySeconds(): ?int
    {
        return $this->retryDelaySeconds;
    }

    public function setRetryDelaySeconds(?int $retryDelaySeconds): self
    {
        $this->retryDelaySeconds = $retryDelaySeconds;
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

    public function isLoggingEnabled(): bool
    {
        return $this->loggingEnabled;
    }

    public function setLoggingEnabled(bool $loggingEnabled): self
    {
        $this->loggingEnabled = $loggingEnabled;
        return $this;
    }

    // ==================== LIMITS & CONSTRAINTS ====================

    public function getMaxMessageLength(): ?int
    {
        return $this->maxMessageLength;
    }

    public function setMaxMessageLength(?int $maxMessageLength): self
    {
        $this->maxMessageLength = $maxMessageLength;
        return $this;
    }

    public function getMaxAttachmentSize(): ?int
    {
        return $this->maxAttachmentSize;
    }

    public function setMaxAttachmentSize(?int $maxAttachmentSize): self
    {
        $this->maxAttachmentSize = $maxAttachmentSize;
        return $this;
    }

    public function getMaxRecipients(): ?int
    {
        return $this->maxRecipients;
    }

    public function setMaxRecipients(?int $maxRecipients): self
    {
        $this->maxRecipients = $maxRecipients;
        return $this;
    }

    public function getDailyLimit(): ?int
    {
        return $this->dailyLimit;
    }

    public function setDailyLimit(?int $dailyLimit): self
    {
        $this->dailyLimit = $dailyLimit;
        return $this;
    }

    public function getHourlyLimit(): ?int
    {
        return $this->hourlyLimit;
    }

    public function setHourlyLimit(?int $hourlyLimit): self
    {
        $this->hourlyLimit = $hourlyLimit;
        return $this;
    }

    public function getRateLimit(): ?int
    {
        return $this->rateLimit;
    }

    public function setRateLimit(?int $rateLimit): self
    {
        $this->rateLimit = $rateLimit;
        return $this;
    }

    public function getAllowedFileTypes(): ?array
    {
        return $this->allowedFileTypes;
    }

    public function setAllowedFileTypes(?array $allowedFileTypes): self
    {
        $this->allowedFileTypes = $allowedFileTypes;
        return $this;
    }

    // ==================== DELIVERABILITY & METRICS ====================

    public function getExpectedDeliveryRate(): ?int
    {
        return $this->expectedDeliveryRate;
    }

    public function setExpectedDeliveryRate(?int $expectedDeliveryRate): self
    {
        $this->expectedDeliveryRate = $expectedDeliveryRate;
        return $this;
    }

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

    public function getAvgDeliveryTimeSeconds(): ?int
    {
        return $this->avgDeliveryTimeSeconds;
    }

    public function setAvgDeliveryTimeSeconds(?int $avgDeliveryTimeSeconds): self
    {
        $this->avgDeliveryTimeSeconds = $avgDeliveryTimeSeconds;
        return $this;
    }

    public function getAvgResponseTimeMinutes(): ?int
    {
        return $this->avgResponseTimeMinutes;
    }

    public function setAvgResponseTimeMinutes(?int $avgResponseTimeMinutes): self
    {
        $this->avgResponseTimeMinutes = $avgResponseTimeMinutes;
        return $this;
    }

    public function getReliabilityScore(): ?int
    {
        return $this->reliabilityScore;
    }

    public function setReliabilityScore(?int $reliabilityScore): self
    {
        $this->reliabilityScore = $reliabilityScore;
        return $this;
    }

    public function getUptimePercentage(): ?int
    {
        return $this->uptimePercentage;
    }

    public function setUptimePercentage(?int $uptimePercentage): self
    {
        $this->uptimePercentage = $uptimePercentage;
        return $this;
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

    public function supportsOptOut(): bool
    {
        return $this->supportsOptOut;
    }

    public function setSupportsOptOut(bool $supportsOptOut): self
    {
        $this->supportsOptOut = $supportsOptOut;
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

    public function requiresConsentForm(): bool
    {
        return $this->requiresConsentForm;
    }

    public function setRequiresConsentForm(bool $requiresConsentForm): self
    {
        $this->requiresConsentForm = $requiresConsentForm;
        return $this;
    }

    public function getConsentFormUrl(): ?string
    {
        return $this->consentFormUrl;
    }

    public function setConsentFormUrl(?string $consentFormUrl): self
    {
        $this->consentFormUrl = $consentFormUrl;
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

    public function getMonthlySubscriptionCost(): ?int
    {
        return $this->monthlySubscriptionCost;
    }

    public function setMonthlySubscriptionCost(?int $monthlySubscriptionCost): self
    {
        $this->monthlySubscriptionCost = $monthlySubscriptionCost;
        return $this;
    }

    public function getSetupCost(): ?int
    {
        return $this->setupCost;
    }

    public function setSetupCost(?int $setupCost): self
    {
        $this->setupCost = $setupCost;
        return $this;
    }

    public function getFreeTierLimit(): ?int
    {
        return $this->freeTierLimit;
    }

    public function setFreeTierLimit(?int $freeTierLimit): self
    {
        $this->freeTierLimit = $freeTierLimit;
        return $this;
    }

    // ==================== CONFIGURATION & CREDENTIALS ====================

    public function getApiEndpoint(): ?string
    {
        return $this->apiEndpoint;
    }

    public function setApiEndpoint(?string $apiEndpoint): self
    {
        $this->apiEndpoint = $apiEndpoint;
        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getApiSecret(): ?string
    {
        return $this->apiSecret;
    }

    public function setApiSecret(?string $apiSecret): self
    {
        $this->apiSecret = $apiSecret;
        return $this;
    }

    public function getAccountIdentifier(): ?string
    {
        return $this->accountIdentifier;
    }

    public function setAccountIdentifier(?string $accountIdentifier): self
    {
        $this->accountIdentifier = $accountIdentifier;
        return $this;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(?string $senderName): self
    {
        $this->senderName = $senderName;
        return $this;
    }

    public function getReplyToAddress(): ?string
    {
        return $this->replyToAddress;
    }

    public function setReplyToAddress(?string $replyToAddress): self
    {
        $this->replyToAddress = $replyToAddress;
        return $this;
    }

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl(?string $webhookUrl): self
    {
        $this->webhookUrl = $webhookUrl;
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

    // ==================== TEMPLATES & DEFAULTS ====================

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

    public function getDefaultSignature(): ?string
    {
        return $this->defaultSignature;
    }

    public function setDefaultSignature(?string $defaultSignature): self
    {
        $this->defaultSignature = $defaultSignature;
        return $this;
    }

    // ==================== VERIFICATION & VALIDATION ====================

    public function getVerificationStatus(): ?string
    {
        return $this->verificationStatus;
    }

    public function setVerificationStatus(?string $verificationStatus): self
    {
        $this->verificationStatus = $verificationStatus;
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

    public function getVerificationExpiresAt(): ?\DateTimeImmutable
    {
        return $this->verificationExpiresAt;
    }

    public function setVerificationExpiresAt(?\DateTimeImmutable $verificationExpiresAt): self
    {
        $this->verificationExpiresAt = $verificationExpiresAt;
        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $verificationCode): self
    {
        $this->verificationCode = $verificationCode;
        return $this;
    }

    public function getLastVerificationCheck(): ?\DateTimeImmutable
    {
        return $this->lastVerificationCheck;
    }

    public function setLastVerificationCheck(?\DateTimeImmutable $lastVerificationCheck): self
    {
        $this->lastVerificationCheck = $lastVerificationCheck;
        return $this;
    }

    public function getHealthCheckUrl(): ?string
    {
        return $this->healthCheckUrl;
    }

    public function setHealthCheckUrl(?string $healthCheckUrl): self
    {
        $this->healthCheckUrl = $healthCheckUrl;
        return $this;
    }

    // ==================== STATISTICS & ANALYTICS ====================

    public function getTotalSent(): int
    {
        return $this->totalSent;
    }

    public function incrementTotalSent(int $count = 1): self
    {
        $this->totalSent += $count;
        return $this;
    }

    public function getTotalDelivered(): int
    {
        return $this->totalDelivered;
    }

    public function incrementTotalDelivered(int $count = 1): self
    {
        $this->totalDelivered += $count;
        $this->lastSuccessAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTotalFailed(): int
    {
        return $this->totalFailed;
    }

    public function incrementTotalFailed(int $count = 1): self
    {
        $this->totalFailed += $count;
        return $this;
    }

    public function getTotalCost(): int
    {
        return $this->totalCost;
    }

    public function addCost(int $cost): self
    {
        $this->totalCost += $cost;
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

    public function getLastSuccessAt(): ?\DateTimeImmutable
    {
        return $this->lastSuccessAt;
    }

    // ==================== INTEGRATION & WORKFLOW ====================

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

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    // ==================== UTILITY METHODS ====================

    public function __toString(): string
    {
        return $this->methodName;
    }

    /**
     * Get full classification path for display
     */
    public function getClassificationPath(): string
    {
        $parts = [$this->channelType, $this->category];
        if ($this->subCategory) {
            $parts[] = $this->subCategory;
        }
        if ($this->provider) {
            $parts[] = $this->provider;
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
     * Get default color based on channel type
     */
    private function getDefaultChannelColor(): string
    {
        return match ($this->channelType) {
            'phone' => '#3498db',        // Blue
            'email' => '#e74c3c',        // Red
            'sms' => '#2ecc71',          // Green
            'whatsapp' => '#25d366',     // WhatsApp green
            'chat' => '#9b59b6',         // Purple
            'video' => '#1abc9c',        // Turquoise
            'social' => '#3b5998',       // Facebook blue
            'push_notification' => '#e67e22', // Orange
            'voice_message' => '#34495e', // Dark gray
            'postal_mail' => '#95a5a6',  // Gray
            'fax' => '#7f8c8d',          // Darker gray
            'messaging_app' => '#16a085', // Teal
            default => '#95a5a6',        // Gray
        };
    }

    /**
     * Get default icon based on channel type
     */
    public function getDefaultIcon(): string
    {
        return $this->icon ?? match ($this->channelType) {
            'phone' => 'bi-telephone',
            'email' => 'bi-envelope',
            'sms' => 'bi-chat-text',
            'whatsapp' => 'bi-whatsapp',
            'chat' => 'bi-chat-dots',
            'video' => 'bi-camera-video',
            'social' => 'bi-share',
            'push_notification' => 'bi-bell',
            'voice_message' => 'bi-mic',
            'postal_mail' => 'bi-mailbox',
            'fax' => 'bi-file-earmark-text',
            'messaging_app' => 'bi-chat-left-dots',
            default => 'bi-chat-square',
        };
    }

    /**
     * Check if communication method is configurable (not system type)
     */
    public function isConfigurable(): bool
    {
        return !$this->system;
    }

    /**
     * Check if verification is valid (not expired)
     */
    public function isVerificationValid(): bool
    {
        if (!$this->verified) {
            return false;
        }
        if ($this->verificationExpiresAt === null) {
            return true; // No expiration
        }
        return $this->verificationExpiresAt > new \DateTimeImmutable();
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

    /**
     * Get formatted total cost
     */
    public function getFormattedTotalCost(): string
    {
        return number_format($this->totalCost / 100, 2) . ' ' . $this->currency;
    }

    /**
     * Calculate delivery success rate percentage
     */
    public function getDeliverySuccessRate(): float
    {
        if ($this->totalSent === 0) {
            return 0.0;
        }
        return round(($this->totalDelivered / $this->totalSent) * 100, 2);
    }

    /**
     * Calculate failure rate percentage
     */
    public function getFailureRate(): float
    {
        if ($this->totalSent === 0) {
            return 0.0;
        }
        return round(($this->totalFailed / $this->totalSent) * 100, 2);
    }

    /**
     * Check if method is within daily limit
     */
    public function isWithinDailyLimit(int $currentDailyUsage): bool
    {
        if ($this->dailyLimit === null) {
            return true; // No limit
        }
        return $currentDailyUsage < $this->dailyLimit;
    }

    /**
     * Check if method is within hourly limit
     */
    public function isWithinHourlyLimit(int $currentHourlyUsage): bool
    {
        if ($this->hourlyLimit === null) {
            return true; // No limit
        }
        return $currentHourlyUsage < $this->hourlyLimit;
    }

    /**
     * Check if method is ready for use (active, verified if required)
     */
    public function isReady(): bool
    {
        if (!$this->active) {
            return false;
        }
        if ($this->requiresVerification && !$this->isVerificationValid()) {
            return false;
        }
        return true;
    }

    /**
     * Get health status summary
     */
    public function getHealthStatus(): array
    {
        return [
            'active' => $this->active,
            'verified' => $this->isVerificationValid(),
            'ready' => $this->isReady(),
            'uptime_percentage' => $this->uptimePercentage,
            'reliability_score' => $this->reliabilityScore,
            'delivery_success_rate' => $this->getDeliverySuccessRate(),
            'failure_rate' => $this->getFailureRate(),
            'last_used' => $this->lastUsedAt?->format('Y-m-d H:i:s'),
            'last_success' => $this->lastSuccessAt?->format('Y-m-d H:i:s'),
        ];
    }
}
