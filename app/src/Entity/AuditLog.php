<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\UuidV7Generator;
use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * AuditLog stores complete historical record of all entity changes
 *
 * This entity provides comprehensive audit trail functionality:
 * - Records all entity creations, updates, and deletions
 * - Stores field-level changes (old value → new value)
 * - Tracks which user made the change
 * - Includes metadata (IP address, user agent)
 * - Enables queries like "What was the value 3 months ago?"
 * - Supports compliance reporting (GDPR, SOC2, etc.)
 */
#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(columns: ['entity_class', 'entity_id'], name: 'idx_audit_entity')]
#[ORM\Index(columns: ['user_id', 'created_at'], name: 'idx_audit_user')]
#[ORM\Index(columns: ['action', 'created_at'], name: 'idx_audit_action')]
#[ORM\Index(columns: ['created_at'], name: 'idx_audit_created')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private Uuid $id;

    /**
     * Action performed: entity_created, entity_updated, entity_deleted
     */
    #[ORM\Column(length: 255)]
    private string $action;

    /**
     * Fully qualified class name of the entity
     */
    #[ORM\Column(length: 255)]
    private string $entityClass;

    /**
     * UUID of the entity that was changed
     */
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $entityId;

    /**
     * User who made the change (nullable for system operations)
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    /**
     * Field-level changes: ['field' => ['old_value', 'new_value']]
     */
    #[ORM\Column(type: 'json')]
    private array $changes = [];

    /**
     * Additional metadata: IP address, user agent, etc.
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    /**
     * SHA-256 checksum for tamper detection
     * Generated from: action + entityClass + entityId + changes + createdAt + salt
     */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $checksum = null;

    /**
     * When this audit event occurred
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
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
            'changes' => $this->changes,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
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
            'changes' => $this->changes,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ]);

        $expectedChecksum = hash('sha256', $data . $salt);

        return hash_equals($expectedChecksum, $this->checksum);
    }
}
