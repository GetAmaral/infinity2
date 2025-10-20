<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\UuidV7Generator;
use App\Repository\AuditLogRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AuditLog stores complete historical record of all entity changes
 *
 * This entity provides comprehensive audit trail functionality:
 * - Records all entity creations, updates, and deletions
 * - Stores field-level changes (old value → new value)
 * - Tracks which user made the change
 * - Includes metadata (IP address, user agent, session ID)
 * - Enables queries like "What was the value 3 months ago?"
 * - Supports compliance reporting (GDPR, SOC2, ISO 27001, HIPAA)
 * - Tamper detection with SHA-256 checksums
 * - Multi-tenant organization isolation
 * - Full API Platform support with proper normalization groups
 */
#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(columns: ['entity_class', 'entity_id'], name: 'idx_audit_entity')]
#[ORM\Index(columns: ['user_id', 'created_at'], name: 'idx_audit_user')]
#[ORM\Index(columns: ['action', 'created_at'], name: 'idx_audit_action')]
#[ORM\Index(columns: ['created_at'], name: 'idx_audit_created')]
#[ORM\Index(columns: ['organization_id'], name: 'idx_audit_organization')]
#[ORM\Index(columns: ['sensitive'], name: 'idx_audit_sensitive')]
#[ORM\Index(columns: ['ip_address'], name: 'idx_audit_ip')]
#[ORM\Index(columns: ['session_id'], name: 'idx_audit_session')]
#[ORM\Index(columns: ['risk_level'], name: 'idx_audit_risk')]
#[ORM\Index(columns: ['exported'], name: 'idx_audit_exported')]
#[ApiResource(
    normalizationContext: ['groups' => ['audit_log:read']],
    denormalizationContext: ['groups' => ['audit_log:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/audit-logs',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['audit_log:read', 'audit_log:details']]
        ),
        new Get(
            uriTemplate: '/admin/audit-logs/{id}',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['audit_log:read', 'audit_log:details', 'audit_log:full']]
        ),
        new Post(
            uriTemplate: '/admin/audit-logs',
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['audit_log:write']]
        ),
        new GetCollection(
            uriTemplate: '/audit-logs/entity/{entityClass}/{entityId}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['audit_log:read']]
        ),
        new GetCollection(
            uriTemplate: '/audit-logs/user/{userId}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['audit_log:read']]
        )
    ]
)]
class AuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    #[Groups(['audit_log:read'])]
    private Uuid $id;

    /**
     * Action performed: entity_created, entity_updated, entity_deleted,
     * login_success, login_failed, password_changed, permission_changed, etc.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['audit_log:read', 'audit_log:write'])]
    private string $action;

    /**
     * Fully qualified class name of the entity (e.g., App\Entity\User)
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['audit_log:read', 'audit_log:write'])]
    private string $entityClass;

    /**
     * UUID of the entity that was changed
     */
    #[ORM\Column(type: UuidType::NAME)]
    #[Assert\NotBlank]
    #[Groups(['audit_log:read', 'audit_log:write'])]
    private Uuid $entityId;

    /**
     * Human-readable entity type (User, Organization, Course, etc.)
     */
    #[ORM\Column(length: 100)]
    #[Groups(['audit_log:read', 'audit_log:write'])]
    private string $entityType;

    /**
     * User who made the change (nullable for system operations)
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['audit_log:read', 'audit_log:details'])]
    private ?User $user = null;

    /**
     * Organization context for multi-tenant isolation
     */
    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['audit_log:read', 'audit_log:details'])]
    private Organization $organization;

    /**
     * IP address of the client making the change
     */
    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['audit_log:read', 'audit_log:details'])]
    private ?string $ipAddress = null;

    /**
     * User agent string (browser/client information)
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['audit_log:details'])]
    private ?string $userAgent = null;

    /**
     * Session ID for tracking user sessions
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['audit_log:details'])]
    private ?string $sessionId = null;

    /**
     * HTTP method used (GET, POST, PUT, DELETE, PATCH)
     */
    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['audit_log:details'])]
    private ?string $httpMethod = null;

    /**
     * Request URI/endpoint
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['audit_log:details'])]
    private ?string $requestUri = null;

    /**
     * HTTP response status code (200, 201, 400, 403, 500, etc.)
     */
    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups(['audit_log:details'])]
    private ?int $responseStatus = null;

    /**
     * Field-level changes: ['field' => ['old_value', 'new_value']]
     */
    #[ORM\Column(type: 'json')]
    #[Groups(['audit_log:read', 'audit_log:write'])]
    private array $changes = [];

    /**
     * Additional metadata: location, device info, API version, etc.
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['audit_log:full'])]
    private ?array $metadata = null;

    /**
     * Reason or justification for the change
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['audit_log:read', 'audit_log:write'])]
    private ?string $reason = null;

    /**
     * Related ticket/approval reference (e.g., JIRA-1234)
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['audit_log:read', 'audit_log:write'])]
    private ?string $ticketReference = null;

    /**
     * Risk level: low, medium, high, critical
     */
    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['audit_log:read', 'audit_log:details'])]
    private ?string $riskLevel = null;

    /**
     * Whether this action contains sensitive data (PII, financial, health)
     * CONVENTION: Use "sensitive" NOT "isSensitive"
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['audit_log:read', 'audit_log:write'])]
    private bool $sensitive = false;

    /**
     * Whether this audit record has been exported for compliance
     * CONVENTION: Use "exported" NOT "isExported"
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['audit_log:read', 'audit_log:write'])]
    private bool $exported = false;

    /**
     * Timestamp when exported (for retention tracking)
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['audit_log:details'])]
    private ?\DateTimeImmutable $exportedAt = null;

    /**
     * Compliance tags (GDPR, SOC2, HIPAA, ISO27001, etc.)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['audit_log:details'])]
    private ?array $complianceTags = null;

    /**
     * SHA-256 checksum for tamper detection
     * Generated from: action + entityClass + entityId + changes + createdAt + salt
     */
    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['audit_log:full'])]
    private ?string $checksum = null;

    /**
     * When this audit event occurred
     */
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['audit_log:read'])]
    private \DateTimeImmutable $createdAt;

    /**
     * Geolocation data (country, city) for compliance
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['audit_log:full'])]
    private ?array $geolocation = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;

        // Auto-set entityType from class name
        if (!isset($this->entityType)) {
            $parts = explode('\\', $entityClass);
            $this->entityType = end($parts);
        }

        return $this;
    }

    public function getEntityId(): Uuid
    {
        return $this->entityId;
    }

    public function setEntityId(Uuid $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): self
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
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

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function getHttpMethod(): ?string
    {
        return $this->httpMethod;
    }

    public function setHttpMethod(?string $httpMethod): self
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    public function getRequestUri(): ?string
    {
        return $this->requestUri;
    }

    public function setRequestUri(?string $requestUri): self
    {
        $this->requestUri = $requestUri;
        return $this;
    }

    public function getResponseStatus(): ?int
    {
        return $this->responseStatus;
    }

    public function setResponseStatus(?int $responseStatus): self
    {
        $this->responseStatus = $responseStatus;
        return $this;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function setChanges(array $changes): self
    {
        $this->changes = $changes;
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

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getTicketReference(): ?string
    {
        return $this->ticketReference;
    }

    public function setTicketReference(?string $ticketReference): self
    {
        $this->ticketReference = $ticketReference;
        return $this;
    }

    public function getRiskLevel(): ?string
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(?string $riskLevel): self
    {
        $this->riskLevel = $riskLevel;
        return $this;
    }

    public function getSensitive(): bool
    {
        return $this->sensitive;
    }

    public function setSensitive(bool $sensitive): self
    {
        $this->sensitive = $sensitive;
        return $this;
    }

    public function getExported(): bool
    {
        return $this->exported;
    }

    public function setExported(bool $exported): self
    {
        $this->exported = $exported;

        if ($exported && $this->exportedAt === null) {
            $this->exportedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getExportedAt(): ?\DateTimeImmutable
    {
        return $this->exportedAt;
    }

    public function setExportedAt(?\DateTimeImmutable $exportedAt): self
    {
        $this->exportedAt = $exportedAt;
        return $this;
    }

    public function getComplianceTags(): ?array
    {
        return $this->complianceTags;
    }

    public function setComplianceTags(?array $complianceTags): self
    {
        $this->complianceTags = $complianceTags;
        return $this;
    }

    public function addComplianceTag(string $tag): self
    {
        if ($this->complianceTags === null) {
            $this->complianceTags = [];
        }

        if (!in_array($tag, $this->complianceTags, true)) {
            $this->complianceTags[] = $tag;
        }

        return $this;
    }

    public function getGeolocation(): ?array
    {
        return $this->geolocation;
    }

    public function setGeolocation(?array $geolocation): self
    {
        $this->geolocation = $geolocation;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get a specific change by field name
     */
    public function getChangeForField(string $fieldName): ?array
    {
        return $this->changes[$fieldName] ?? null;
    }

    /**
     * Check if a specific field was changed
     */
    public function hasChangeForField(string $fieldName): bool
    {
        return isset($this->changes[$fieldName]);
    }

    /**
     * Get old value for a field
     */
    public function getOldValue(string $fieldName): mixed
    {
        return $this->changes[$fieldName][0] ?? null;
    }

    /**
     * Get new value for a field
     */
    public function getNewValue(string $fieldName): mixed
    {
        return $this->changes[$fieldName][1] ?? null;
    }

    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    public function setChecksum(?string $checksum): self
    {
        $this->checksum = $checksum;
        return $this;
    }

    /**
     * Generate checksum for tamper detection
     *
     * Creates a SHA-256 hash of critical audit log fields with a salt.
     * The salt should be defined in the AUDIT_INTEGRITY_SALT environment variable.
     *
     * @param string $salt Secret salt for checksum generation
     */
    public function generateChecksum(string $salt): void
    {
        $data = json_encode([
            'action' => $this->action,
            'entity_class' => $this->entityClass,
            'entity_id' => $this->entityId->toString(),
            'entity_type' => $this->entityType,
            'user_id' => $this->user?->getId()?->toString(),
            'organization_id' => $this->organization->getId()->toString(),
            'changes' => $this->changes,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'ip_address' => $this->ipAddress,
        ]);

        $this->checksum = hash('sha256', $data . $salt);
    }

    /**
     * Verify audit log integrity
     *
     * Recomputes the checksum and compares it with the stored value.
     * Returns false if the audit log has been tampered with.
     *
     * @param string $salt Secret salt used for checksum generation
     * @return bool True if integrity is intact, false if tampered
     */
    public function verifyIntegrity(string $salt): bool
    {
        if ($this->checksum === null) {
            return false; // No checksum means can't verify
        }

        $data = json_encode([
            'action' => $this->action,
            'entity_class' => $this->entityClass,
            'entity_id' => $this->entityId->toString(),
            'entity_type' => $this->entityType,
            'user_id' => $this->user?->getId()?->toString(),
            'organization_id' => $this->organization->getId()->toString(),
            'changes' => $this->changes,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'ip_address' => $this->ipAddress,
        ]);

        $expectedChecksum = hash('sha256', $data . $salt);

        return hash_equals($expectedChecksum, $this->checksum);
    }

    /**
     * Check if this audit entry should be retained based on compliance
     */
    public function shouldRetain(\DateTimeImmutable $retentionDate): bool
    {
        // Always retain sensitive or compliance-tagged records
        if ($this->sensitive || !empty($this->complianceTags)) {
            return true;
        }

        // Retain high-risk actions
        if (in_array($this->riskLevel, ['high', 'critical'], true)) {
            return true;
        }

        // Retain based on retention date
        return $this->createdAt > $retentionDate;
    }

    /**
     * Anonymize personally identifiable information
     */
    public function anonymize(): self
    {
        $this->user = null;
        $this->ipAddress = '0.0.0.0';
        $this->userAgent = '[ANONYMIZED]';
        $this->sessionId = null;

        if ($this->metadata) {
            $this->metadata = array_map(fn() => '[ANONYMIZED]', $this->metadata);
        }

        if ($this->geolocation) {
            $this->geolocation = ['country' => 'XX', 'city' => 'ANONYMIZED'];
        }

        // Mark changes as anonymized but preserve field names for audit trail
        foreach ($this->changes as $field => $values) {
            if ($this->isPersonallyIdentifiable($field)) {
                $this->changes[$field] = ['[ANONYMIZED]', '[ANONYMIZED]'];
            }
        }

        return $this;
    }

    /**
     * Check if a field contains personally identifiable information
     */
    private function isPersonallyIdentifiable(string $fieldName): bool
    {
        $piiFields = [
            'email', 'phone', 'firstName', 'lastName', 'fullName',
            'address', 'ssn', 'taxId', 'passport', 'driverLicense',
            'creditCard', 'bankAccount', 'healthRecord'
        ];

        return in_array($fieldName, $piiFields, true);
    }
}
