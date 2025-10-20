<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TalkTypeTemplateRepository;
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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * TalkTypeTemplate Entity - Enterprise CRM Communication Template System
 *
 * Modern multi-channel communication template management following 2025 CRM best practices.
 * Provides reusable, personalized message templates for Email, SMS, WhatsApp, Phone scripts,
 * and all communication channels defined in TalkType.
 *
 * === 2025 CRM Template Best Practices ===
 *
 * PERSONALIZATION & DYNAMIC CONTENT:
 * - Template variables/merge tags ({{first_name}}, {{company}}, {{order_id}})
 * - Conditional content blocks (if/else logic)
 * - Dynamic field insertion from CRM data
 * - Personalization tokens for names, dates, products
 * - Emoji support for modern messaging
 * - Rich text formatting (HTML for email, plain text for SMS)
 * - Signature management (user-specific, organization-specific)
 * - Localization support (multi-language templates)
 *
 * COMPLIANCE & GOVERNANCE:
 * - GDPR compliance tracking
 * - Opt-in/opt-out management
 * - Unsubscribe link enforcement (required for marketing emails)
 * - Legal disclaimer requirements
 * - Approval workflows for marketing templates
 * - Version control and audit trails
 * - Data retention policies
 * - TCPA compliance for SMS/calls
 *
 * MULTI-CHANNEL OPTIMIZATION:
 * - Channel-specific content (SMS: 160 chars, Email: unlimited)
 * - WhatsApp Business template approval status
 * - Phone script formatting (greetings, objection handling, closings)
 * - Social media character limits
 * - A/B testing variants
 * - Mobile-responsive email design
 * - Link tracking and UTM parameters
 * - Open rate tracking pixels
 *
 * AUTOMATION & WORKFLOWS:
 * - Trigger-based automation (new lead, abandoned cart, follow-up)
 * - Drip campaign sequences
 * - Scheduled delivery times
 * - Send time optimization
 * - Auto-response templates
 * - Chatbot message templates
 * - Workflow integration (CRM events trigger templates)
 * - Smart scheduling (timezone-aware)
 *
 * ANALYTICS & PERFORMANCE:
 * - Template usage statistics
 * - Performance metrics (open rate, click rate, response rate, conversion)
 * - A/B test results tracking
 * - Engagement scoring
 * - Best-performing template identification
 * - Heatmap tracking for email links
 * - Response time analytics
 * - ROI measurement
 *
 * TEMPLATE ORGANIZATION:
 * - Category-based classification (sales, support, marketing, internal)
 * - Purpose/use case tagging (welcome, follow-up, reminder, promotion)
 * - Industry-specific templates (tech, retail, healthcare, finance)
 * - Channel-based grouping (email templates, SMS templates, etc.)
 * - Folder/collection organization
 * - Search and filter capabilities
 * - Template library management
 * - Team collaboration (shared vs personal templates)
 *
 * CONTENT MANAGEMENT:
 * - Subject line optimization (email)
 * - Preview text (email pre-header)
 * - Body content (HTML, plain text, markdown)
 * - Call-to-action buttons
 * - Attachment support
 * - Image embedding
 * - Signature placement
 * - Footer customization
 *
 * === Key Statistics (2025 Benchmarks) ===
 * - SMS: 98% open rate, 3-minute average read time
 * - Email: 20% open rate (varies by industry)
 * - WhatsApp: 70-90% open rate, instant deliverability
 * - Personalized templates: 26% higher open rates
 * - Segmented campaigns: 14.31% higher open rates
 * - A/B tested subject lines: 49% increase in open rates
 *
 * === Integration Features ===
 * - CRM field mapping
 * - Third-party template editors (Unlayer, BEE, MJML)
 * - Email service provider sync (SendGrid, Mailgun, AWS SES)
 * - SMS gateway integration (Twilio, MessageBird)
 * - WhatsApp Business API templates
 * - Calendar integration for scheduling
 * - Document merge (generate PDFs from templates)
 * - External content libraries
 *
 * @see https://www.leadsquared.com/learn/sales/whatsapp-message-templates/
 * @see https://www.kommo.com/blog/templates-for-whatsapp-crm/
 * @see https://go.laylo.com/blog/integrating-crm-with-sms-and-email-for-effective-communication
 * @see https://timelines.ai/10-tips-for-effective-whatsapp-campaign-templates/
 * @see TalkType - Communication channel definitions
 *
 * @author Luminai CRM Team
 */
#[ORM\Entity(repositoryClass: TalkTypeTemplateRepository::class)]
#[ORM\Table(name: 'talk_type_template')]
#[ORM\Index(name: 'idx_template_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_template_talk_type', columns: ['talk_type_id'])]
#[ORM\Index(name: 'idx_template_name', columns: ['template_name'])]
#[ORM\Index(name: 'idx_template_code', columns: ['template_code'])]
#[ORM\Index(name: 'idx_template_category', columns: ['category'])]
#[ORM\Index(name: 'idx_template_channel', columns: ['channel'])]
#[ORM\Index(name: 'idx_template_purpose', columns: ['purpose'])]
#[ORM\Index(name: 'idx_template_active', columns: ['active'])]
#[ORM\Index(name: 'idx_template_default', columns: ['default_template'])]
#[ORM\Index(name: 'idx_template_system', columns: ['system'])]
#[ORM\Index(name: 'idx_template_published', columns: ['published'])]
#[ORM\Index(name: 'idx_template_approved', columns: ['approved'])]
#[ORM\Index(name: 'idx_template_language', columns: ['language'])]
#[ORM\Index(name: 'idx_template_created', columns: ['created_at'])]
#[ORM\UniqueConstraint(name: 'uniq_template_code_org', columns: ['template_code', 'organization_id'])]
#[UniqueEntity(
    fields: ['templateCode', 'organization'],
    message: 'A template with this code already exists in your organization'
)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    shortName: 'TalkTypeTemplate',
    description: 'CRM Communication Template for multi-channel messaging (Email, SMS, WhatsApp, Phone scripts)',
    normalizationContext: [
        'groups' => ['talk_type_template:read'],
        'swagger_definition_name' => 'Read',
        'enable_max_depth' => true
    ],
    denormalizationContext: [
        'groups' => ['talk_type_template:write'],
        'swagger_definition_name' => 'Write'
    ],
    operations: [
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type_template:read', 'talk_type_template:detail']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type_template:read', 'talk_type_template:list']],
            paginationEnabled: true,
            paginationItemsPerPage: 30
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['talk_type_template:write', 'talk_type_template:create']],
            validationContext: ['groups' => ['Default', 'talk_type_template:create']]
        ),
        new Put(
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['talk_type_template:write']],
            validationContext: ['groups' => ['Default', 'talk_type_template:update']]
        ),
        new Patch(
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['talk_type_template:write']],
            validationContext: ['groups' => ['Default', 'talk_type_template:update']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        // Custom operations
        new Get(
            uriTemplate: '/talk-type-templates/{id}/clone',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type_template:read', 'talk_type_template:detail']],
            description: 'Clone a template with a new name'
        ),
        new GetCollection(
            uriTemplate: '/talk-type-templates/active',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type_template:read', 'talk_type_template:list']],
            description: 'Get all active templates'
        ),
        new GetCollection(
            uriTemplate: '/talk-type-templates/by-channel/{channel}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type_template:read', 'talk_type_template:list']],
            description: 'Get templates by communication channel'
        ),
        new GetCollection(
            uriTemplate: '/talk-type-templates/by-category/{category}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type_template:read', 'talk_type_template:list']],
            description: 'Get templates by category'
        ),
        new GetCollection(
            uriTemplate: '/talk-type-templates/defaults',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type_template:read', 'talk_type_template:list']],
            description: 'Get all default templates'
        ),
        new GetCollection(
            uriTemplate: '/talk-type-templates/top-performing',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['talk_type_template:read', 'talk_type_template:list']],
            description: 'Get best-performing templates by engagement metrics'
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
    'templateCode' => 'exact',
    'category' => 'exact',
    'channel' => 'exact',
    'purpose' => 'exact',
    'language' => 'exact'
])]
#[ApiFilter(BooleanFilter::class, properties: [
    'active',
    'defaultTemplate',
    'system',
    'published',
    'approved',
    'requiresApproval',
    'gdprCompliant'
])]
#[ApiFilter(DateFilter::class, properties: ['createdAt', 'updatedAt', 'lastUsedAt'])]
#[ApiFilter(OrderFilter::class, properties: [
    'templateName',
    'category',
    'usageCount',
    'openRate',
    'responseRate',
    'conversionRate',
    'createdAt',
    'updatedAt'
], arguments: ['orderParameterName' => 'order'])]
class TalkTypeTemplate extends EntityBase
{
    // ==================== CORE IDENTIFICATION (5 fields) ====================

    /**
     * Template name (e.g., "Welcome Email", "Follow-up SMS", "Cold Call Script")
     */
    #[ORM\Column(type: Types::STRING, length: 150)]
    #[Assert\NotBlank(message: 'Template name is required', groups: ['talk_type_template:create'])]
    #[Assert\Length(
        min: 3,
        max: 150,
        minMessage: 'Template name must be at least {{ limit }} characters',
        maxMessage: 'Template name cannot exceed {{ limit }} characters'
    )]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(
        description: 'Template name',
        example: 'Welcome Email - New Customer',
        openapiContext: ['minLength' => 3, 'maxLength' => 150]
    )]
    private string $templateName = '';

    /**
     * Unique template code (slug format)
     */
    #[ORM\Column(type: Types::STRING, length: 100, unique: false)]
    #[Assert\NotBlank(message: 'Template code is required', groups: ['talk_type_template:create'])]
    #[Assert\Regex(
        pattern: '/^[a-z0-9_-]+$/',
        message: 'Template code must contain only lowercase letters, numbers, hyphens, and underscores'
    )]
    #[Assert\Length(min: 3, max: 100)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(
        description: 'Unique template code (slug format)',
        example: 'welcome-email-new-customer',
        openapiContext: ['minLength' => 3, 'maxLength' => 100, 'pattern' => '^[a-z0-9_-]+$']
    )]
    private string $templateCode = '';

    /**
     * Template description/purpose
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'Description cannot exceed {{ limit }} characters')]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Template description and usage instructions',
        example: 'Sent to new customers after successful registration to welcome them and provide onboarding resources',
        openapiContext: ['maxLength' => 2000]
    )]
    private ?string $description = null;

    /**
     * Display label for UI (alternative to name)
     */
    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(
        description: 'Display label for UI selection',
        example: 'New Customer Welcome',
        openapiContext: ['maxLength' => 150]
    )]
    private ?string $displayLabel = null;

    /**
     * Template version (semantic versioning)
     */
    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => '1.0.0'])]
    #[Assert\Regex(
        pattern: '/^\d+\.\d+\.\d+$/',
        message: 'Version must follow semantic versioning (e.g., 1.0.0)'
    )]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Template version (semantic versioning)',
        example: '1.2.0',
        openapiContext: ['pattern' => '^\d+\.\d+\.\d+$']
    )]
    private string $version = '1.0.0';

    // ==================== ORGANIZATION & RELATIONSHIPS (2 fields) ====================

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Organization is required')]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Organization this template belongs to',
        readableLink: true,
        writableLink: false
    )]
    private ?Organization $organization = null;

    /**
     * Associated TalkType (communication channel)
     */
    #[ORM\ManyToOne(targetEntity: TalkType::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(
        description: 'Associated communication channel/type',
        readableLink: true,
        writableLink: true
    )]
    private ?TalkType $talkType = null;

    // ==================== CLASSIFICATION (5 fields) ====================

    /**
     * Communication channel
     * Choices: phone, email, sms, whatsapp, chat, video, social, meeting, voice_message, push_notification, other
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank(message: 'Channel is required')]
    #[Assert\Choice(
        choices: ['phone', 'email', 'sms', 'whatsapp', 'chat', 'video', 'social', 'meeting', 'voice_message', 'push_notification', 'other'],
        message: 'Invalid channel'
    )]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(
        description: 'Communication channel',
        example: 'email',
        openapiContext: ['enum' => ['phone', 'email', 'sms', 'whatsapp', 'chat', 'video', 'social', 'meeting', 'voice_message', 'push_notification', 'other']]
    )]
    private string $channel = 'email';

    /**
     * Template category
     * Choices: sales, support, marketing, internal, customer_service, technical, administrative, outreach, other
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank(message: 'Category is required')]
    #[Assert\Choice(
        choices: ['sales', 'support', 'marketing', 'internal', 'customer_service', 'technical', 'administrative', 'outreach', 'other'],
        message: 'Invalid category'
    )]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(
        description: 'Template category',
        example: 'marketing',
        openapiContext: ['enum' => ['sales', 'support', 'marketing', 'internal', 'customer_service', 'technical', 'administrative', 'outreach', 'other']]
    )]
    private string $category = 'other';

    /**
     * Template purpose/use case
     * Choices: welcome, follow_up, reminder, promotion, notification, confirmation, survey, feedback, newsletter, announcement, other
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank(message: 'Purpose is required')]
    #[Assert\Choice(
        choices: ['welcome', 'follow_up', 'reminder', 'promotion', 'notification', 'confirmation', 'survey', 'feedback', 'newsletter', 'announcement', 'thank_you', 'apology', 'invitation', 'update', 'alert', 'other'],
        message: 'Invalid purpose'
    )]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(
        description: 'Template purpose/use case',
        example: 'welcome',
        openapiContext: ['enum' => ['welcome', 'follow_up', 'reminder', 'promotion', 'notification', 'confirmation', 'survey', 'feedback', 'newsletter', 'announcement', 'thank_you', 'apology', 'invitation', 'update', 'alert', 'other']]
    )]
    private string $purpose = 'other';

    /**
     * Language code (ISO 639-1)
     */
    #[ORM\Column(type: Types::STRING, length: 5, options: ['default' => 'en'])]
    #[Assert\Length(exactly: 2, exactMessage: 'Language code must be 2 characters (ISO 639-1)')]
    #[Assert\Regex(pattern: '/^[a-z]{2}$/', message: 'Language code must be lowercase ISO 639-1 format (e.g., en, es, fr)')]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(
        description: 'Language code (ISO 639-1)',
        example: 'en',
        openapiContext: ['minLength' => 2, 'maxLength' => 2, 'pattern' => '^[a-z]{2}$']
    )]
    private string $language = 'en';

    /**
     * Industry targeting
     */
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ['technology', 'finance', 'healthcare', 'retail', 'manufacturing', 'education', 'real-estate', 'hospitality', 'professional-services', 'other'],
        message: 'Invalid industry'
    )]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Target industry',
        example: 'technology',
        openapiContext: ['enum' => ['technology', 'finance', 'healthcare', 'retail', 'manufacturing', 'education', 'real-estate', 'hospitality', 'professional-services', 'other']]
    )]
    private ?string $industry = null;

    // ==================== TEMPLATE CONTENT (7 fields) ====================

    /**
     * Subject line (for email, SMS preview, notification title)
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'Subject cannot exceed {{ limit }} characters')]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Email subject line or message title (supports variables like {{first_name}})',
        example: 'Welcome to {{company_name}}, {{first_name}}!',
        openapiContext: ['maxLength' => 500]
    )]
    private ?string $subject = null;

    /**
     * Preview text / pre-header (email only)
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Email preview text shown in inbox (pre-header)',
        example: 'Get started with your new account in just 3 easy steps',
        openapiContext: ['maxLength' => 500]
    )]
    private ?string $previewText = null;

    /**
     * Template body content (HTML for email, plain text for SMS/chat)
     */
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Content is required')]
    #[Assert\Length(max: 50000, maxMessage: 'Content cannot exceed {{ limit }} characters')]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Template body content (HTML for email, plain text for SMS, supports {{variables}})',
        example: 'Hi {{first_name}},\n\nWelcome to {{company_name}}! We are excited to have you on board.\n\nBest regards,\n{{sender_name}}',
        openapiContext: ['maxLength' => 50000]
    )]
    private string $content = '';

    /**
     * Plain text version (for email fallback)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 50000)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Plain text version for email clients that do not support HTML',
        example: 'Hi {{first_name}}, Welcome to {{company_name}}!',
        openapiContext: ['maxLength' => 50000]
    )]
    private ?string $plainTextContent = null;

    /**
     * Footer content (signature, legal disclaimers, unsubscribe link)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Footer content (signature, disclaimers, unsubscribe link)',
        example: 'Best regards,\n{{sender_name}}\n{{company_name}}\n\nUnsubscribe: {{unsubscribe_link}}',
        openapiContext: ['maxLength' => 5000]
    )]
    private ?string $footer = null;

    /**
     * Call-to-action (CTA) text
     */
    #[ORM\Column(type: Types::STRING, length: 200, nullable: true)]
    #[Assert\Length(max: 200)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Call-to-action button text',
        example: 'Get Started Now',
        openapiContext: ['maxLength' => 200]
    )]
    private ?string $ctaText = null;

    /**
     * Call-to-action URL
     */
    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    #[Assert\Length(max: 1000)]
    #[Assert\Url(message: 'CTA URL must be a valid URL')]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Call-to-action URL (supports {{variables}})',
        example: 'https://example.com/welcome?user={{user_id}}',
        openapiContext: ['maxLength' => 1000]
    )]
    private ?string $ctaUrl = null;

    // ==================== PERSONALIZATION & VARIABLES (3 fields) ====================

    /**
     * Available template variables (JSON array)
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Available merge tags/variables for personalization',
        example: '["first_name", "last_name", "company_name", "email", "order_id", "appointment_date"]',
        openapiContext: ['type' => 'array', 'items' => ['type' => 'string']]
    )]
    private ?array $variables = null;

    /**
     * Personalization rules (conditional content)
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Conditional content rules and logic',
        example: '{"if_premium": "Special VIP content here", "if_new_user": "Welcome bonus code"}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $personalizationRules = null;

    /**
     * Localization data (multi-language support)
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Localization data for multi-language support',
        example: '{"es": {"subject": "Bienvenido a {{company_name}}"}, "fr": {"subject": "Bienvenue chez {{company_name}}"}}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $localizationData = null;

    // ==================== STATUS & CONFIGURATION (8 fields) ====================

    /**
     * Template is active
     * Convention: "active" NOT "isActive"
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(description: 'Template is active and available for use', example: true)]
    private bool $active = true;

    /**
     * Default template for this channel/category
     * Convention: "default" NOT "isDefault"
     */
    #[ORM\Column(type: Types::BOOLEAN, name: 'default_template', options: ['default' => false])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(description: 'This is the default template for the channel/category', example: false)]
    private bool $defaultTemplate = false;

    /**
     * System template (cannot be deleted)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'System template (cannot be modified/deleted)', example: false, readable: true, writable: false)]
    private bool $system = false;

    /**
     * Published and visible to users
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(description: 'Template is published and visible', example: true)]
    private bool $published = false;

    /**
     * Requires approval before use (for marketing/compliance)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Template requires approval before sending', example: false)]
    private bool $requiresApproval = false;

    /**
     * Template approved status
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Template is approved for use', example: true)]
    private bool $approved = false;

    /**
     * Sort order for display
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 100])]
    #[Assert\Range(min: 0, max: 9999)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Display sort order (lower = higher priority)', example: 10)]
    private int $sortOrder = 100;

    /**
     * Visible in template library
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Visible in template selection UI', example: true)]
    private bool $visible = true;

    // ==================== VISUAL DESIGN (4 fields) ====================

    /**
     * Bootstrap icon class
     */
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Regex(pattern: '/^bi-[a-z0-9-]+$/', message: 'Icon must be a valid Bootstrap icon (e.g., bi-envelope)')]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(
        description: 'Bootstrap icon class',
        example: 'bi-envelope-fill',
        openapiContext: ['pattern' => '^bi-[a-z0-9-]+$']
    )]
    private ?string $icon = null;

    /**
     * Template color (hex)
     */
    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Color must be a valid hex color (e.g., #3498db)')]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:list'])]
    #[ApiProperty(
        description: 'Template color for UI visualization',
        example: '#3498db',
        openapiContext: ['pattern' => '^#[0-9A-Fa-f]{6}$']
    )]
    private ?string $color = null;

    /**
     * Thumbnail/preview image URL
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Assert\Url]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Preview image URL for template gallery',
        example: 'https://cdn.example.com/templates/welcome-email-preview.png',
        openapiContext: ['maxLength' => 500]
    )]
    private ?string $thumbnailUrl = null;

    /**
     * Tags for organization and search
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Tags for template organization',
        example: '["onboarding", "automated", "high-priority"]',
        openapiContext: ['type' => 'array', 'items' => ['type' => 'string']]
    )]
    private ?array $tags = null;

    // ==================== COMPLIANCE & GOVERNANCE (6 fields) ====================

    /**
     * GDPR compliant
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Template complies with GDPR regulations', example: true)]
    private bool $gdprCompliant = false;

    /**
     * Requires opt-in consent
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Requires recipient opt-in consent', example: false)]
    private bool $requiresOptIn = false;

    /**
     * Includes unsubscribe link (required for marketing emails)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Template includes unsubscribe link', example: true)]
    private bool $includesUnsubscribe = false;

    /**
     * Legal disclaimers
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Legal disclaimers and compliance text',
        example: 'This email was sent to {{email}} because you signed up for our service.',
        openapiContext: ['maxLength' => 2000]
    )]
    private ?string $legalDisclaimer = null;

    /**
     * Data retention period (days)
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 365])]
    #[Assert\PositiveOrZero]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Data retention period in days (0 = indefinite)', example: 365)]
    private int $dataRetentionDays = 365;

    /**
     * Privacy settings
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Privacy settings and compliance configuration',
        example: '{"allow_tracking": true, "anonymize_data": false, "retention_policy": "standard"}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $privacySettings = null;

    // ==================== AUTOMATION & WORKFLOWS (5 fields) ====================

    /**
     * Supports scheduled sending
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Template can be scheduled for future delivery', example: true)]
    private bool $allowsScheduling = true;

    /**
     * Supports A/B testing
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Template supports A/B variant testing', example: false)]
    private bool $allowsAbTesting = false;

    /**
     * Automation trigger events
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'CRM events that trigger this template',
        example: '["user_registered", "cart_abandoned", "trial_ending"]',
        openapiContext: ['type' => 'array', 'items' => ['type' => 'string']]
    )]
    private ?array $automationTriggers = null;

    /**
     * Workflow configuration
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Workflow automation rules and sequencing',
        example: '{"delay_hours": 24, "next_template": "follow-up-email", "condition": "if_no_response"}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $workflowConfig = null;

    /**
     * Send time optimization enabled
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'AI-powered send time optimization', example: false)]
    private bool $sendTimeOptimization = false;

    // ==================== ANALYTICS & PERFORMANCE (10 fields) ====================

    /**
     * Total usage count
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Number of times template has been used', example: 150, readable: true, writable: false)]
    private int $usageCount = 0;

    /**
     * Last time template was used
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Last time template was sent', readable: true, writable: false)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * Open rate percentage (email/SMS tracking)
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Open rate percentage (0-100)', example: 42, readable: true, writable: false)]
    private ?int $openRate = null;

    /**
     * Click-through rate percentage
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Click-through rate percentage (0-100)', example: 12, readable: true, writable: false)]
    private ?int $clickRate = null;

    /**
     * Response rate percentage
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Response rate percentage (0-100)', example: 8, readable: true, writable: false)]
    private ?int $responseRate = null;

    /**
     * Conversion rate percentage
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Conversion rate percentage (0-100)', example: 5, readable: true, writable: false)]
    private ?int $conversionRate = null;

    /**
     * Bounce rate percentage
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Bounce rate percentage (0-100)', example: 2, readable: true, writable: false)]
    private ?int $bounceRate = null;

    /**
     * Unsubscribe rate percentage
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Unsubscribe rate percentage (0-100)', example: 1, readable: true, writable: false)]
    private ?int $unsubscribeRate = null;

    /**
     * Engagement score (0-100 composite metric)
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(description: 'Overall engagement score (0-100)', example: 75, readable: true, writable: false)]
    private ?int $engagementScore = null;

    /**
     * Performance metrics (detailed analytics)
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Detailed performance metrics and analytics',
        example: '{"avg_read_time": 45, "device_stats": {"mobile": 60, "desktop": 40}, "peak_send_time": "10:00"}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $performanceMetrics = null;

    // ==================== INTEGRATION & TECHNICAL (5 fields) ====================

    /**
     * External template ID (for third-party integrations)
     */
    #[ORM\Column(type: Types::STRING, length: 200, nullable: true)]
    #[Assert\Length(max: 200)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'External template ID (SendGrid, Mailgun, etc.)',
        example: 'd-abc123def456',
        openapiContext: ['maxLength' => 200]
    )]
    private ?string $externalTemplateId = null;

    /**
     * Integration provider
     */
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ['sendgrid', 'mailgun', 'aws-ses', 'twilio', 'messagebird', 'whatsapp-business', 'custom', 'internal'],
        message: 'Invalid integration provider'
    )]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Email/SMS service provider',
        example: 'sendgrid',
        openapiContext: ['enum' => ['sendgrid', 'mailgun', 'aws-ses', 'twilio', 'messagebird', 'whatsapp-business', 'custom', 'internal']]
    )]
    private ?string $integrationProvider = null;

    /**
     * Integration configuration
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Provider-specific integration settings',
        example: '{"api_key": "sk_xxx", "from_email": "noreply@example.com", "tracking_enabled": true}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $integrationConfig = null;

    /**
     * Webhook URL for callbacks
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Assert\Url]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Webhook URL for delivery/engagement callbacks',
        example: 'https://api.example.com/webhooks/email-events',
        openapiContext: ['maxLength' => 500]
    )]
    private ?string $webhookUrl = null;

    /**
     * Template metadata
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['talk_type_template:read', 'talk_type_template:write', 'talk_type_template:detail'])]
    #[ApiProperty(
        description: 'Custom metadata for extensibility',
        example: '{"created_by": "John Doe", "department": "Marketing", "campaign_id": "summer-2025"}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $metadata = null;

    // ==================== CONSTRUCTOR ====================

    public function __construct()
    {
        parent::__construct();
        $this->active = true;
        $this->defaultTemplate = false;
        $this->system = false;
        $this->published = false;
        $this->requiresApproval = false;
        $this->approved = false;
        $this->sortOrder = 100;
        $this->visible = true;
        $this->gdprCompliant = false;
        $this->requiresOptIn = false;
        $this->includesUnsubscribe = false;
        $this->dataRetentionDays = 365;
        $this->allowsScheduling = true;
        $this->allowsAbTesting = false;
        $this->sendTimeOptimization = false;
        $this->usageCount = 0;
        $this->version = '1.0.0';
        $this->channel = 'email';
        $this->category = 'other';
        $this->purpose = 'other';
        $this->language = 'en';
    }

    // ==================== DOMAIN LOGIC METHODS ====================

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
     * Check if template can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->system;
    }

    /**
     * Check if template is in use
     */
    public function isInUse(): bool
    {
        return $this->usageCount > 0;
    }

    /**
     * Clone template with new name and code
     */
    public function cloneTemplate(string $newName, string $newCode): self
    {
        $clone = new self();
        $clone->setTemplateName($newName);
        $clone->setTemplateCode($newCode);
        $clone->setDescription($this->description);
        $clone->setDisplayLabel($this->displayLabel);
        $clone->setChannel($this->channel);
        $clone->setCategory($this->category);
        $clone->setPurpose($this->purpose);
        $clone->setLanguage($this->language);
        $clone->setIndustry($this->industry);
        $clone->setSubject($this->subject);
        $clone->setPreviewText($this->previewText);
        $clone->setContent($this->content);
        $clone->setPlainTextContent($this->plainTextContent);
        $clone->setFooter($this->footer);
        $clone->setCtaText($this->ctaText);
        $clone->setCtaUrl($this->ctaUrl);
        $clone->setVariables($this->variables);
        $clone->setPersonalizationRules($this->personalizationRules);
        $clone->setIcon($this->icon);
        $clone->setColor($this->color);
        $clone->setTags($this->tags);
        $clone->setOrganization($this->organization);
        $clone->setTalkType($this->talkType);
        $clone->setActive(false);
        $clone->setDefaultTemplate(false);
        $clone->setPublished(false);
        $clone->setApproved(false);
        $clone->setVersion('1.0.0');

        return $clone;
    }

    /**
     * Bump version (patch, minor, major)
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
     * Calculate engagement score from metrics
     */
    public function calculateEngagementScore(): int
    {
        if ($this->usageCount === 0) {
            return 0;
        }

        $score = 0;
        $weights = [
            'openRate' => 0.3,
            'clickRate' => 0.25,
            'responseRate' => 0.25,
            'conversionRate' => 0.2
        ];

        if ($this->openRate !== null) {
            $score += $this->openRate * $weights['openRate'];
        }
        if ($this->clickRate !== null) {
            $score += $this->clickRate * $weights['clickRate'];
        }
        if ($this->responseRate !== null) {
            $score += $this->responseRate * $weights['responseRate'];
        }
        if ($this->conversionRate !== null) {
            $score += $this->conversionRate * $weights['conversionRate'];
        }

        return (int) round($score);
    }

    /**
     * Update engagement score
     */
    public function updateEngagementScore(): self
    {
        $this->engagementScore = $this->calculateEngagementScore();
        return $this;
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

    /**
     * Get display label or fallback to template name
     */
    public function getDisplayLabelOrName(): string
    {
        return $this->displayLabel ?? $this->templateName;
    }

    // ==================== GETTERS & SETTERS ====================

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
        $this->templateCode = strtolower($templateCode);
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
        return $this->displayLabel;
    }

    public function setDisplayLabel(?string $displayLabel): self
    {
        $this->displayLabel = $displayLabel;
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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    public function getTalkType(): ?TalkType
    {
        return $this->talkType;
    }

    public function setTalkType(?TalkType $talkType): self
    {
        $this->talkType = $talkType;
        return $this;
    }

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

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function setPurpose(string $purpose): self
    {
        $this->purpose = $purpose;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = strtolower($language);
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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getPreviewText(): ?string
    {
        return $this->previewText;
    }

    public function setPreviewText(?string $previewText): self
    {
        $this->previewText = $previewText;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getPlainTextContent(): ?string
    {
        return $this->plainTextContent;
    }

    public function setPlainTextContent(?string $plainTextContent): self
    {
        $this->plainTextContent = $plainTextContent;
        return $this;
    }

    public function getFooter(): ?string
    {
        return $this->footer;
    }

    public function setFooter(?string $footer): self
    {
        $this->footer = $footer;
        return $this;
    }

    public function getCtaText(): ?string
    {
        return $this->ctaText;
    }

    public function setCtaText(?string $ctaText): self
    {
        $this->ctaText = $ctaText;
        return $this;
    }

    public function getCtaUrl(): ?string
    {
        return $this->ctaUrl;
    }

    public function setCtaUrl(?string $ctaUrl): self
    {
        $this->ctaUrl = $ctaUrl;
        return $this;
    }

    public function getVariables(): ?array
    {
        return $this->variables;
    }

    public function setVariables(?array $variables): self
    {
        $this->variables = $variables;
        return $this;
    }

    public function getPersonalizationRules(): ?array
    {
        return $this->personalizationRules;
    }

    public function setPersonalizationRules(?array $personalizationRules): self
    {
        $this->personalizationRules = $personalizationRules;
        return $this;
    }

    public function getLocalizationData(): ?array
    {
        return $this->localizationData;
    }

    public function setLocalizationData(?array $localizationData): self
    {
        $this->localizationData = $localizationData;
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

    public function requiresApproval(): bool
    {
        return $this->requiresApproval;
    }

    public function setRequiresApproval(bool $requiresApproval): self
    {
        $this->requiresApproval = $requiresApproval;
        return $this;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function setApproved(bool $approved): self
    {
        $this->approved = $approved;
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

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): self
    {
        $this->thumbnailUrl = $thumbnailUrl;
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

    public function isGdprCompliant(): bool
    {
        return $this->gdprCompliant;
    }

    public function setGdprCompliant(bool $gdprCompliant): self
    {
        $this->gdprCompliant = $gdprCompliant;
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

    public function includesUnsubscribe(): bool
    {
        return $this->includesUnsubscribe;
    }

    public function setIncludesUnsubscribe(bool $includesUnsubscribe): self
    {
        $this->includesUnsubscribe = $includesUnsubscribe;
        return $this;
    }

    public function getLegalDisclaimer(): ?string
    {
        return $this->legalDisclaimer;
    }

    public function setLegalDisclaimer(?string $legalDisclaimer): self
    {
        $this->legalDisclaimer = $legalDisclaimer;
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

    public function getPrivacySettings(): ?array
    {
        return $this->privacySettings;
    }

    public function setPrivacySettings(?array $privacySettings): self
    {
        $this->privacySettings = $privacySettings;
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

    public function allowsAbTesting(): bool
    {
        return $this->allowsAbTesting;
    }

    public function setAllowsAbTesting(bool $allowsAbTesting): self
    {
        $this->allowsAbTesting = $allowsAbTesting;
        return $this;
    }

    public function getAutomationTriggers(): ?array
    {
        return $this->automationTriggers;
    }

    public function setAutomationTriggers(?array $automationTriggers): self
    {
        $this->automationTriggers = $automationTriggers;
        return $this;
    }

    public function getWorkflowConfig(): ?array
    {
        return $this->workflowConfig;
    }

    public function setWorkflowConfig(?array $workflowConfig): self
    {
        $this->workflowConfig = $workflowConfig;
        return $this;
    }

    public function isSendTimeOptimization(): bool
    {
        return $this->sendTimeOptimization;
    }

    public function setSendTimeOptimization(bool $sendTimeOptimization): self
    {
        $this->sendTimeOptimization = $sendTimeOptimization;
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

    public function getOpenRate(): ?int
    {
        return $this->openRate;
    }

    public function setOpenRate(?int $openRate): self
    {
        $this->openRate = $openRate;
        return $this;
    }

    public function getClickRate(): ?int
    {
        return $this->clickRate;
    }

    public function setClickRate(?int $clickRate): self
    {
        $this->clickRate = $clickRate;
        return $this;
    }

    public function getResponseRate(): ?int
    {
        return $this->responseRate;
    }

    public function setResponseRate(?int $responseRate): self
    {
        $this->responseRate = $responseRate;
        return $this;
    }

    public function getConversionRate(): ?int
    {
        return $this->conversionRate;
    }

    public function setConversionRate(?int $conversionRate): self
    {
        $this->conversionRate = $conversionRate;
        return $this;
    }

    public function getBounceRate(): ?int
    {
        return $this->bounceRate;
    }

    public function setBounceRate(?int $bounceRate): self
    {
        $this->bounceRate = $bounceRate;
        return $this;
    }

    public function getUnsubscribeRate(): ?int
    {
        return $this->unsubscribeRate;
    }

    public function setUnsubscribeRate(?int $unsubscribeRate): self
    {
        $this->unsubscribeRate = $unsubscribeRate;
        return $this;
    }

    public function getEngagementScore(): ?int
    {
        return $this->engagementScore;
    }

    public function setEngagementScore(?int $engagementScore): self
    {
        $this->engagementScore = $engagementScore;
        return $this;
    }

    public function getPerformanceMetrics(): ?array
    {
        return $this->performanceMetrics;
    }

    public function setPerformanceMetrics(?array $performanceMetrics): self
    {
        $this->performanceMetrics = $performanceMetrics;
        return $this;
    }

    public function getExternalTemplateId(): ?string
    {
        return $this->externalTemplateId;
    }

    public function setExternalTemplateId(?string $externalTemplateId): self
    {
        $this->externalTemplateId = $externalTemplateId;
        return $this;
    }

    public function getIntegrationProvider(): ?string
    {
        return $this->integrationProvider;
    }

    public function setIntegrationProvider(?string $integrationProvider): self
    {
        $this->integrationProvider = $integrationProvider;
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

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl(?string $webhookUrl): self
    {
        $this->webhookUrl = $webhookUrl;
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

    public function __toString(): string
    {
        return $this->templateName;
    }
}
