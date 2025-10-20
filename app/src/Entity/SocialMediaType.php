<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SocialMediaTypeRepository;
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
 * SocialMediaType Entity - Modern Social Media Platform Classification System
 *
 * Implements enterprise-grade social media platform taxonomy following 2025 CRM best practices:
 * - Comprehensive social media platform tracking (Facebook, Instagram, LinkedIn, TikTok, Twitter/X, YouTube, etc.)
 * - Platform-specific configuration (URLs, API endpoints, authentication)
 * - Visual identification system (platform icons, brand colors, badges)
 * - Behavior configuration (posting capabilities, engagement tracking, analytics)
 * - Integration management (API keys, OAuth tokens, webhooks)
 * - Analytics and engagement metrics (reach, impressions, engagement rates)
 * - Multi-tenant organization isolation
 * - API Platform integration with comprehensive normalization groups
 *
 * Top Social Media Platforms (2025 CRM Integration Standards):
 * - Facebook (Local businesses, advertising, 65+ demographics, 83% marketer adoption)
 * - Instagram (E-commerce, organic engagement, 44% marketer priority, visual content)
 * - LinkedIn (B2B networking, long-form content, professional networking, 20% marketer priority)
 * - TikTok (B2C marketing, entertainment, 18% marketer priority, short-form video)
 * - YouTube (Video content, long-form educational content, 11% marketer priority)
 * - Twitter/X (Customer service, news, real-time engagement, brand monitoring)
 * - WhatsApp (Direct messaging, customer support, high engagement rates)
 * - Pinterest (E-commerce, visual discovery, product marketing)
 * - Snapchat (Young demographics, ephemeral content, AR filters)
 * - Reddit (Community engagement, niche marketing, authentic discussions)
 * - Discord (Community building, customer support, real-time communication)
 * - Tumblr (Blogging platform, niche communities, creative content)
 * - VKontakte (VK) (Russian market, Eastern Europe social networking)
 * - Threads (Meta's text-based platform, Twitter alternative)
 * - Mastodon (Decentralized social networking, open-source alternative)
 *
 * Platform Statistics (2025):
 * - Facebook: 2B+ users, 83% marketer usage, best for local businesses
 * - Instagram: 2B+ users, 78% marketer usage, 44% top priority
 * - LinkedIn: 69% marketer usage, 20% top priority, B2B leader
 * - TikTok: 18% marketer priority, fastest-growing platform
 * - YouTube: 2B+ users, video content dominance
 *
 * @see https://croclub.com/tools/best-crm-social-media-integration/
 * @see https://www.wordstream.com/blog/ws/2022/01/11/most-popular-social-media-platforms
 * @see https://www.statista.com/statistics/272014/global-social-networks-ranked-by-number-of-users/
 * @see https://datareportal.com/social-media-users
 * @see https://nalashaadigital.com/blog/list-of-best-crms-for-social-media-marketing/
 * @see https://socialbee.com/blog/social-media-platforms-for-business/
 *
 * @author Luminai CRM Team
 */
#[ORM\Entity(repositoryClass: SocialMediaTypeRepository::class)]
#[ORM\Table(name: 'social_media_type')]
#[ORM\Index(name: 'idx_social_media_type_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_social_media_type_code', columns: ['code'])]
#[ORM\Index(name: 'idx_social_media_type_platform', columns: ['platform_name'])]
#[ORM\Index(name: 'idx_social_media_type_category', columns: ['category'])]
#[ORM\Index(name: 'idx_social_media_type_active', columns: ['active'])]
#[ORM\Index(name: 'idx_social_media_type_default', columns: ['default_type'])]
#[ORM\Index(name: 'idx_social_media_type_priority', columns: ['marketer_priority'])]
#[ORM\Index(name: 'idx_social_media_type_integration', columns: ['integration_enabled'])]
#[ORM\Index(name: 'idx_social_media_type_analytics', columns: ['analytics_enabled'])]
#[ORM\UniqueConstraint(name: 'uniq_social_media_type_code_org', columns: ['code', 'organization_id'])]
#[UniqueEntity(fields: ['code', 'organization'], message: 'A social media type with this code already exists in your organization')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['social_media_type:read']],
    denormalizationContext: ['groups' => ['social_media_type:write']],
    operations: [
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['social_media_type:read', 'social_media_type:detail']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['social_media_type:read', 'social_media_type:list']]
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['social_media_type:write', 'social_media_type:create']]
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['social_media_type:write', 'social_media_type:update']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['social_media_type:write', 'social_media_type:patch']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        // Custom endpoint for active platforms only
        new GetCollection(
            uriTemplate: '/social-media-types/active',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['social_media_type:read', 'social_media_type:list']]
        ),
        // Custom endpoint for platforms by category
        new GetCollection(
            uriTemplate: '/social-media-types/category/{category}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['social_media_type:read', 'social_media_type:list']]
        ),
        // Custom endpoint for default platforms
        new GetCollection(
            uriTemplate: '/social-media-types/defaults',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['social_media_type:read', 'social_media_type:list']]
        ),
        // Custom endpoint for integrated platforms
        new GetCollection(
            uriTemplate: '/social-media-types/integrated',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['social_media_type:read', 'social_media_type:list']]
        )
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 50,
    order: ['sortOrder' => 'ASC', 'platformName' => 'ASC']
)]
class SocialMediaType extends EntityBase
{
    // ==================== CORE IDENTIFICATION FIELDS (5 fields) ====================

    /**
     * Platform name (e.g., "Facebook", "Instagram", "LinkedIn", "TikTok")
     */
    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Platform name is required')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Name must be at least 2 characters', maxMessage: 'Name cannot exceed 100 characters')]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private string $platformName = '';

    /**
     * Unique code identifier (e.g., "FACEBOOK", "INSTAGRAM", "LINKEDIN", "TIKTOK")
     */
    #[ORM\Column(type: 'string', length: 50, unique: false)]
    #[Assert\NotBlank(message: 'Platform code is required')]
    #[Assert\Length(min: 2, max: 50)]
    #[Assert\Regex(pattern: '/^[A-Z0-9_]+$/', message: 'Code must contain only uppercase letters, numbers, and underscores')]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private string $code = '';

    /**
     * Detailed description of the platform
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'Description cannot exceed 2000 characters')]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $description = null;

    /**
     * Display label for UI (can be different from platformName)
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private ?string $displayLabel = null;

    /**
     * Official platform website URL
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Url(message: 'Platform URL must be a valid URL')]
    #[Assert\Length(max: 500)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $platformUrl = null;

    // ==================== ORGANIZATION & MULTI-TENANCY (1 field) ====================

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Organization is required')]
    #[Groups(['social_media_type:read', 'social_media_type:detail'])]
    private ?Organization $organization = null;

    // ==================== PLATFORM CLASSIFICATION (4 fields) ====================

    /**
     * Platform category
     * Choices: social_network, professional_network, video_platform, messaging, community, microblogging, content_sharing, other
     */
    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Category is required')]
    #[Assert\Choice(
        choices: ['social_network', 'professional_network', 'video_platform', 'messaging', 'community', 'microblogging', 'content_sharing', 'ephemeral_content', 'other'],
        message: 'Invalid category'
    )]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private string $category = 'social_network';

    /**
     * Primary use case
     * Choices: b2b, b2c, ecommerce, customer_service, brand_awareness, content_marketing, advertising, community_building, other
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ['b2b', 'b2c', 'ecommerce', 'customer_service', 'brand_awareness', 'content_marketing', 'advertising', 'community_building', 'lead_generation', 'other'],
        message: 'Invalid use case'
    )]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $primaryUseCase = null;

    /**
     * Target demographics (e.g., "18-34", "25-54", "35-65+", "B2B professionals")
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $targetDemographics = null;

    /**
     * Geographic focus (e.g., "Global", "US/Europe", "China", "Russia/Eastern Europe")
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $geographicFocus = 'Global';

    // ==================== VISUAL IDENTIFICATION (5 fields) ====================

    /**
     * Platform icon class (e.g., "bi-facebook", "bi-instagram", "bi-linkedin")
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private ?string $icon = null;

    /**
     * Platform brand color (hex code, e.g., "#1877F2" for Facebook blue)
     */
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Color must be a valid hex color code (e.g., #1877F2)')]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private ?string $color = null;

    /**
     * Badge color for UI display
     */
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Badge color must be a valid hex color code')]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $badgeColor = null;

    /**
     * Background color for visual distinction
     */
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Background color must be a valid hex color code')]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $backgroundColor = null;

    /**
     * Platform logo URL (for custom branding)
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Url]
    #[Assert\Length(max: 500)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $logoUrl = null;

    // ==================== STATUS & CONFIGURATION (6 fields) ====================

    /**
     * Platform is active and can be used
     * Convention: "active" NOT "isActive"
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private bool $active = true;

    /**
     * Default platform for social media campaigns
     * Convention: "default" NOT "isDefault"
     */
    #[ORM\Column(type: 'boolean', name: 'default_type', options: ['default' => false])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private bool $default = false;

    /**
     * System-defined platform (cannot be modified/deleted)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['social_media_type:read', 'social_media_type:detail'])]
    private bool $system = false;

    /**
     * Display sort order (lower = higher priority)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    #[Assert\Range(min: 0, max: 9999)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private int $sortOrder = 100;

    /**
     * Visible in UI selections
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private bool $visible = true;

    /**
     * Platform featured status (highlighted in UI)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private bool $featured = false;

    // ==================== PLATFORM CAPABILITIES (8 fields) ====================

    /**
     * Supports text posts
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private bool $supportsTextPosts = true;

    /**
     * Supports image posts
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private bool $supportsImages = true;

    /**
     * Supports video posts
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private bool $supportsVideos = true;

    /**
     * Supports stories/ephemeral content
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private bool $supportsStories = false;

    /**
     * Supports live streaming
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private bool $supportsLiveStreaming = false;

    /**
     * Supports direct messaging
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private bool $supportsDirectMessaging = true;

    /**
     * Supports paid advertising
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private bool $supportsPaidAdvertising = true;

    /**
     * Supports scheduled posting
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private bool $supportsScheduledPosts = true;

    // ==================== INTEGRATION & API (7 fields) ====================

    /**
     * API integration enabled
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:list'])]
    private bool $integrationEnabled = false;

    /**
     * API endpoint URL
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Url]
    #[Assert\Length(max: 500)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $apiEndpoint = null;

    /**
     * API version (e.g., "v2.0", "v1", "2023-11")
     */
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $apiVersion = null;

    /**
     * OAuth authentication enabled
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private bool $oauthEnabled = false;

    /**
     * Webhook support enabled
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private bool $webhookEnabled = false;

    /**
     * Webhook URL for receiving platform events
     */
    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Url]
    #[Assert\Length(max: 500)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $webhookUrl = null;

    /**
     * Integration configuration (API keys, tokens, secrets - encrypted)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['social_media_type:write', 'social_media_type:detail'])]
    private ?array $integrationConfig = null;

    // ==================== ANALYTICS & METRICS (8 fields) ====================

    /**
     * Analytics tracking enabled
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private bool $analyticsEnabled = true;

    /**
     * Marketer adoption percentage (e.g., 83 for Facebook, 78 for Instagram)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?int $marketerAdoption = null;

    /**
     * Marketer priority ranking (1-100, lower is higher priority)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?int $marketerPriority = null;

    /**
     * Estimated active users (in millions)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?int $activeUsersMillions = null;

    /**
     * Average engagement rate percentage
     */
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $avgEngagementRate = null;

    /**
     * Average reach per post (percentage of followers)
     */
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?string $avgReachPercentage = null;

    /**
     * Best posting times (JSON array of time ranges)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?array $bestPostingTimes = null;

    /**
     * Performance benchmarks (JSON object with various metrics)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?array $performanceBenchmarks = null;

    // ==================== POSTING & CONTENT RULES (6 fields) ====================

    /**
     * Maximum post character limit
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?int $maxCharacterLimit = null;

    /**
     * Maximum hashtags per post
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?int $maxHashtags = null;

    /**
     * Recommended hashtags per post
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?int $recommendedHashtags = null;

    /**
     * Maximum images per post
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?int $maxImagesPerPost = null;

    /**
     * Maximum video duration in seconds
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?int $maxVideoDuration = null;

    /**
     * Content guidelines and restrictions (JSON array)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?array $contentGuidelines = null;

    // ==================== USAGE STATISTICS (3 fields) ====================

    /**
     * Total usage count (number of posts/interactions)
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    #[Groups(['social_media_type:read', 'social_media_type:detail'])]
    private int $usageCount = 0;

    /**
     * Last time this platform was used
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['social_media_type:read', 'social_media_type:detail'])]
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * Last integration sync timestamp
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['social_media_type:read', 'social_media_type:detail'])]
    private ?\DateTimeImmutable $lastSyncAt = null;

    // ==================== ADDITIONAL METADATA (2 fields) ====================

    /**
     * Custom metadata (JSON for additional platform-specific data)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?array $metadata = null;

    /**
     * Tags for categorization (JSON array)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['social_media_type:read', 'social_media_type:write', 'social_media_type:detail'])]
    private ?array $tags = null;

    // ==================== CONSTRUCTOR ====================

    public function __construct()
    {
        parent::__construct();
    }

    // ==================== CORE GETTERS/SETTERS ====================

    public function getPlatformName(): string
    {
        return $this->platformName;
    }

    public function setPlatformName(string $platformName): self
    {
        $this->platformName = $platformName;
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
        return $this->displayLabel ?? $this->platformName;
    }

    public function setDisplayLabel(?string $displayLabel): self
    {
        $this->displayLabel = $displayLabel;
        return $this;
    }

    public function getPlatformUrl(): ?string
    {
        return $this->platformUrl;
    }

    public function setPlatformUrl(?string $platformUrl): self
    {
        $this->platformUrl = $platformUrl;
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

    public function getPrimaryUseCase(): ?string
    {
        return $this->primaryUseCase;
    }

    public function setPrimaryUseCase(?string $primaryUseCase): self
    {
        $this->primaryUseCase = $primaryUseCase;
        return $this;
    }

    public function getTargetDemographics(): ?string
    {
        return $this->targetDemographics;
    }

    public function setTargetDemographics(?string $targetDemographics): self
    {
        $this->targetDemographics = $targetDemographics;
        return $this;
    }

    public function getGeographicFocus(): ?string
    {
        return $this->geographicFocus;
    }

    public function setGeographicFocus(?string $geographicFocus): self
    {
        $this->geographicFocus = $geographicFocus;
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

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(?string $logoUrl): self
    {
        $this->logoUrl = $logoUrl;
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

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): self
    {
        $this->featured = $featured;
        return $this;
    }

    // ==================== CAPABILITIES ====================

    public function supportsTextPosts(): bool
    {
        return $this->supportsTextPosts;
    }

    public function setSupportsTextPosts(bool $supportsTextPosts): self
    {
        $this->supportsTextPosts = $supportsTextPosts;
        return $this;
    }

    public function supportsImages(): bool
    {
        return $this->supportsImages;
    }

    public function setSupportsImages(bool $supportsImages): self
    {
        $this->supportsImages = $supportsImages;
        return $this;
    }

    public function supportsVideos(): bool
    {
        return $this->supportsVideos;
    }

    public function setSupportsVideos(bool $supportsVideos): self
    {
        $this->supportsVideos = $supportsVideos;
        return $this;
    }

    public function supportsStories(): bool
    {
        return $this->supportsStories;
    }

    public function setSupportsStories(bool $supportsStories): self
    {
        $this->supportsStories = $supportsStories;
        return $this;
    }

    public function supportsLiveStreaming(): bool
    {
        return $this->supportsLiveStreaming;
    }

    public function setSupportsLiveStreaming(bool $supportsLiveStreaming): self
    {
        $this->supportsLiveStreaming = $supportsLiveStreaming;
        return $this;
    }

    public function supportsDirectMessaging(): bool
    {
        return $this->supportsDirectMessaging;
    }

    public function setSupportsDirectMessaging(bool $supportsDirectMessaging): self
    {
        $this->supportsDirectMessaging = $supportsDirectMessaging;
        return $this;
    }

    public function supportsPaidAdvertising(): bool
    {
        return $this->supportsPaidAdvertising;
    }

    public function setSupportsPaidAdvertising(bool $supportsPaidAdvertising): self
    {
        $this->supportsPaidAdvertising = $supportsPaidAdvertising;
        return $this;
    }

    public function supportsScheduledPosts(): bool
    {
        return $this->supportsScheduledPosts;
    }

    public function setSupportsScheduledPosts(bool $supportsScheduledPosts): self
    {
        $this->supportsScheduledPosts = $supportsScheduledPosts;
        return $this;
    }

    // ==================== INTEGRATION ====================

    public function isIntegrationEnabled(): bool
    {
        return $this->integrationEnabled;
    }

    public function setIntegrationEnabled(bool $integrationEnabled): self
    {
        $this->integrationEnabled = $integrationEnabled;
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

    public function getApiVersion(): ?string
    {
        return $this->apiVersion;
    }

    public function setApiVersion(?string $apiVersion): self
    {
        $this->apiVersion = $apiVersion;
        return $this;
    }

    public function isOauthEnabled(): bool
    {
        return $this->oauthEnabled;
    }

    public function setOauthEnabled(bool $oauthEnabled): self
    {
        $this->oauthEnabled = $oauthEnabled;
        return $this;
    }

    public function isWebhookEnabled(): bool
    {
        return $this->webhookEnabled;
    }

    public function setWebhookEnabled(bool $webhookEnabled): self
    {
        $this->webhookEnabled = $webhookEnabled;
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

    public function getIntegrationConfig(): ?array
    {
        return $this->integrationConfig;
    }

    public function setIntegrationConfig(?array $integrationConfig): self
    {
        $this->integrationConfig = $integrationConfig;
        return $this;
    }

    // ==================== ANALYTICS ====================

    public function isAnalyticsEnabled(): bool
    {
        return $this->analyticsEnabled;
    }

    public function setAnalyticsEnabled(bool $analyticsEnabled): self
    {
        $this->analyticsEnabled = $analyticsEnabled;
        return $this;
    }

    public function getMarketerAdoption(): ?int
    {
        return $this->marketerAdoption;
    }

    public function setMarketerAdoption(?int $marketerAdoption): self
    {
        $this->marketerAdoption = $marketerAdoption;
        return $this;
    }

    public function getMarketerPriority(): ?int
    {
        return $this->marketerPriority;
    }

    public function setMarketerPriority(?int $marketerPriority): self
    {
        $this->marketerPriority = $marketerPriority;
        return $this;
    }

    public function getActiveUsersMillions(): ?int
    {
        return $this->activeUsersMillions;
    }

    public function setActiveUsersMillions(?int $activeUsersMillions): self
    {
        $this->activeUsersMillions = $activeUsersMillions;
        return $this;
    }

    public function getAvgEngagementRate(): ?string
    {
        return $this->avgEngagementRate;
    }

    public function setAvgEngagementRate(?string $avgEngagementRate): self
    {
        $this->avgEngagementRate = $avgEngagementRate;
        return $this;
    }

    public function getAvgReachPercentage(): ?string
    {
        return $this->avgReachPercentage;
    }

    public function setAvgReachPercentage(?string $avgReachPercentage): self
    {
        $this->avgReachPercentage = $avgReachPercentage;
        return $this;
    }

    public function getBestPostingTimes(): ?array
    {
        return $this->bestPostingTimes;
    }

    public function setBestPostingTimes(?array $bestPostingTimes): self
    {
        $this->bestPostingTimes = $bestPostingTimes;
        return $this;
    }

    public function getPerformanceBenchmarks(): ?array
    {
        return $this->performanceBenchmarks;
    }

    public function setPerformanceBenchmarks(?array $performanceBenchmarks): self
    {
        $this->performanceBenchmarks = $performanceBenchmarks;
        return $this;
    }

    // ==================== CONTENT RULES ====================

    public function getMaxCharacterLimit(): ?int
    {
        return $this->maxCharacterLimit;
    }

    public function setMaxCharacterLimit(?int $maxCharacterLimit): self
    {
        $this->maxCharacterLimit = $maxCharacterLimit;
        return $this;
    }

    public function getMaxHashtags(): ?int
    {
        return $this->maxHashtags;
    }

    public function setMaxHashtags(?int $maxHashtags): self
    {
        $this->maxHashtags = $maxHashtags;
        return $this;
    }

    public function getRecommendedHashtags(): ?int
    {
        return $this->recommendedHashtags;
    }

    public function setRecommendedHashtags(?int $recommendedHashtags): self
    {
        $this->recommendedHashtags = $recommendedHashtags;
        return $this;
    }

    public function getMaxImagesPerPost(): ?int
    {
        return $this->maxImagesPerPost;
    }

    public function setMaxImagesPerPost(?int $maxImagesPerPost): self
    {
        $this->maxImagesPerPost = $maxImagesPerPost;
        return $this;
    }

    public function getMaxVideoDuration(): ?int
    {
        return $this->maxVideoDuration;
    }

    public function setMaxVideoDuration(?int $maxVideoDuration): self
    {
        $this->maxVideoDuration = $maxVideoDuration;
        return $this;
    }

    public function getContentGuidelines(): ?array
    {
        return $this->contentGuidelines;
    }

    public function setContentGuidelines(?array $contentGuidelines): self
    {
        $this->contentGuidelines = $contentGuidelines;
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

    public function getLastSyncAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(?\DateTimeImmutable $lastSyncAt): self
    {
        $this->lastSyncAt = $lastSyncAt;
        return $this;
    }

    public function syncNow(): self
    {
        $this->lastSyncAt = new \DateTimeImmutable();
        return $this;
    }

    // ==================== METADATA ====================

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
        return $this->platformName;
    }

    /**
     * Get display color with fallback to platform defaults
     */
    public function getDisplayColor(): string
    {
        return $this->color ?? $this->getDefaultPlatformColor();
    }

    /**
     * Get default color based on popular platforms
     */
    private function getDefaultPlatformColor(): string
    {
        return match (strtoupper($this->code)) {
            'FACEBOOK' => '#1877F2',        // Facebook blue
            'INSTAGRAM' => '#E4405F',       // Instagram pink/red
            'LINKEDIN' => '#0A66C2',        // LinkedIn blue
            'TIKTOK' => '#000000',          // TikTok black
            'TWITTER', 'X' => '#1DA1F2',    // Twitter/X blue
            'YOUTUBE' => '#FF0000',         // YouTube red
            'WHATSAPP' => '#25D366',        // WhatsApp green
            'PINTEREST' => '#E60023',       // Pinterest red
            'SNAPCHAT' => '#FFFC00',        // Snapchat yellow
            'REDDIT' => '#FF4500',          // Reddit orange
            'DISCORD' => '#5865F2',         // Discord blurple
            'TUMBLR' => '#35465C',          // Tumblr dark blue
            'VK', 'VKONTAKTE' => '#0077FF', // VK blue
            'THREADS' => '#000000',         // Threads black
            'MASTODON' => '#6364FF',        // Mastodon purple
            default => '#95a5a6',           // Gray
        };
    }

    /**
     * Get default icon based on platform
     */
    public function getDefaultIcon(): string
    {
        return $this->icon ?? match (strtoupper($this->code)) {
            'FACEBOOK' => 'bi-facebook',
            'INSTAGRAM' => 'bi-instagram',
            'LINKEDIN' => 'bi-linkedin',
            'TIKTOK' => 'bi-tiktok',
            'TWITTER', 'X' => 'bi-twitter-x',
            'YOUTUBE' => 'bi-youtube',
            'WHATSAPP' => 'bi-whatsapp',
            'PINTEREST' => 'bi-pinterest',
            'SNAPCHAT' => 'bi-snapchat',
            'REDDIT' => 'bi-reddit',
            'DISCORD' => 'bi-discord',
            'TUMBLR' => 'bi-tumblr',
            'THREADS' => 'bi-threads',
            default => 'bi-share',
        };
    }

    /**
     * Check if platform is configurable (not system type)
     */
    public function isConfigurable(): bool
    {
        return !$this->system;
    }

    /**
     * Check if platform has active integration
     */
    public function hasActiveIntegration(): bool
    {
        return $this->integrationEnabled && $this->apiEndpoint !== null;
    }

    /**
     * Get platform capabilities summary
     */
    public function getCapabilitiesSummary(): array
    {
        return [
            'text' => $this->supportsTextPosts,
            'images' => $this->supportsImages,
            'videos' => $this->supportsVideos,
            'stories' => $this->supportsStories,
            'live' => $this->supportsLiveStreaming,
            'messaging' => $this->supportsDirectMessaging,
            'ads' => $this->supportsPaidAdvertising,
            'scheduling' => $this->supportsScheduledPosts,
        ];
    }

    /**
     * Check if platform is high priority for marketers (priority <= 25)
     */
    public function isHighPriority(): bool
    {
        return $this->marketerPriority !== null && $this->marketerPriority <= 25;
    }

    /**
     * Check if platform has high marketer adoption (>= 70%)
     */
    public function hasHighAdoption(): bool
    {
        return $this->marketerAdoption !== null && $this->marketerAdoption >= 70;
    }
}
