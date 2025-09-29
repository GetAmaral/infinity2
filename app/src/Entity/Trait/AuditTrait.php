<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * AuditTrait provides standardized audit fields for entities
 *
 * This trait automatically tracks:
 * - When an entity was created/updated (timestamps)
 * - Who created/updated the entity (user references)
 *
 * Usage:
 * 1. Add this trait to your entity
 * 2. The AuditSubscriber will automatically populate these fields
 * 3. Use serialization groups to control API visibility
 */
trait AuditTrait
{
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['audit:read'])]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['audit:read'])]
    protected \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['audit:read'])]
    protected ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['audit:read'])]
    protected ?User $updatedBy = null;

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    /**
     * Initialize audit timestamps on entity creation
     * This method should be called in the entity's constructor
     */
    protected function initializeAuditFields(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * Update the updatedAt timestamp
     * This method is called automatically by the AuditSubscriber
     */
    public function updateAuditTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}