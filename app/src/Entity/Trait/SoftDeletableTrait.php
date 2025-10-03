<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * SoftDeletableTrait provides soft delete functionality for entities
 *
 * Instead of permanently deleting records, this trait marks them as deleted
 * with a timestamp and the user who performed the deletion. This:
 * - Preserves data for audit trails and compliance
 * - Allows restoration of accidentally deleted records
 * - Maintains referential integrity
 * - Enables "trash" functionality
 *
 * Usage:
 * ```php
 * #[ORM\Entity]
 * class MyEntity extends EntityBase
 * {
 *     use SoftDeletableTrait;
 * }
 * ```
 *
 * When used with SoftDeleteSubscriber, calling $em->remove() will automatically
 * perform a soft delete instead of a hard delete.
 */
trait SoftDeletableTrait
{
    /**
     * When was this entity soft-deleted?
     * NULL means the entity is active (not deleted)
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /**
     * Which user soft-deleted this entity?
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $deletedBy = null;

    /**
     * Soft delete this entity
     */
    public function softDelete(?User $user = null): void
    {
        $this->deletedAt = new \DateTimeImmutable();
        $this->deletedBy = $user;
    }

    /**
     * Restore this entity from soft delete
     */
    public function restore(): void
    {
        $this->deletedAt = null;
        $this->deletedBy = null;
    }

    /**
     * Check if this entity is soft-deleted
     */
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    /**
     * Check if this entity is active (not deleted)
     */
    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    /**
     * Get when this entity was deleted
     */
    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    /**
     * Get who deleted this entity
     */
    public function getDeletedBy(): ?User
    {
        return $this->deletedBy;
    }
}
