<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\UuidV7Generator;
use App\Entity\Trait\AuditTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Abstract base entity providing common functionality for all entities
 *
 * This abstract class provides:
 * - UUIDv7 primary key with time-ordered generation
 * - Audit trail functionality (createdAt, updatedAt, createdBy, updatedBy)
 * - Automatic audit field initialization
 * - Common entity patterns and lifecycle callbacks
 *
 * Usage:
 * ```php
 * #[ORM\Entity]
 * class MyEntity extends EntityBase
 * {
 *     // Your entity-specific fields and methods
 * }
 * ```
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class EntityBase
{
    use AuditTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    protected Uuid $id;

    public function __construct()
    {
        $this->initializeAuditFields();
    }

    public function getId(): ?Uuid
    {
        return $this->id ?? null;
    }

    /**
     * String representation of the entity
     * Override this method in child classes for better representation
     */
    public function __toString(): string
    {
        return static::class . '#' . ($this->id ?? 'unsaved');
    }
}