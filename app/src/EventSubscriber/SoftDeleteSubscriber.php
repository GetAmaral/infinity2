<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Trait\SoftDeletableTrait;
use App\Entity\User;
use App\Message\AuditEventMessage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * SoftDeleteSubscriber intercepts entity deletion and performs soft delete instead
 *
 * This subscriber:
 * - Detects when an entity with SoftDeletableTrait is being deleted
 * - Cancels the hard delete operation
 * - Performs a soft delete instead (sets deletedAt timestamp)
 * - Records the deletion in the audit log
 * - Preserves data for compliance and audit trails
 *
 * Benefits:
 * - Data preservation for audit requirements
 * - Ability to restore accidentally deleted records
 * - Maintains referential integrity
 * - Complete audit trail of deletions
 */
#[AsDoctrineListener(event: Events::preRemove)]
final class SoftDeleteSubscriber
{
    public function __construct(
        private readonly Security $security,
        private readonly MessageBusInterface $messageBus
    ) {}

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        error_log("[SoftDeleteSubscriber] preRemove called for: " . get_class($entity));

        // Check if entity supports soft delete
        $hasTrait = $this->hasSoftDeleteTrait($entity);
        error_log("[SoftDeleteSubscriber] Has SoftDeletableTrait: " . ($hasTrait ? 'YES' : 'NO'));

        if (!$hasTrait) {
            error_log("[SoftDeleteSubscriber] Allowing normal hard delete");
            return; // Allow normal hard delete
        }

        error_log("[SoftDeleteSubscriber] Performing soft delete instead");

        // Get entity manager
        $em = $args->getObjectManager();

        // Cancel the hard delete
        $em->detach($entity);

        // Perform soft delete
        $currentUser = $this->getCurrentUser();
        $entity->softDelete($currentUser);

        // Re-persist the entity (now with deletedAt set)
        $em->persist($entity);
        $em->flush();

        // Log the deletion to audit system
        $this->logDeletion($entity, $currentUser);
    }

    /**
     * Check if entity uses SoftDeletableTrait
     */
    private function hasSoftDeleteTrait(object $entity): bool
    {
        $traits = $this->getClassTraitsRecursive($entity);
        return in_array(SoftDeletableTrait::class, $traits, true);
    }

    /**
     * Get all traits used by a class, including parent classes
     */
    private function getClassTraitsRecursive(object $entity): array
    {
        $traits = [];
        $class = get_class($entity);

        do {
            $traits = array_merge($traits, array_keys(class_uses($class) ?: []));
            $class = get_parent_class($class);
        } while ($class);

        return array_unique($traits);
    }

    /**
     * Get currently authenticated user
     */
    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * Log deletion event to audit system
     */
    private function logDeletion(object $entity, ?User $user): void
    {
        $entityClass = get_class($entity);

        // Get entity ID
        $entityId = 'unknown';
        if (method_exists($entity, 'getId')) {
            try {
                $id = $entity->getId();
                $entityId = $id ? (string) $id : 'unknown';
            } catch (\Error $e) {
                $entityId = 'unknown';
            }
        }

        // Create audit message for deletion
        $message = new AuditEventMessage(
            action: 'entity_deleted',
            entityClass: $entityClass,
            entityId: $entityId,
            userId: $user?->getId()?->toString(),
            userEmail: $user?->getEmail(),
            timestamp: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ipAddress: $this->getClientIpAddress(),
            userAgent: $this->getUserAgent(),
            changes: [] // Deletions don't have field changes
        );

        $this->messageBus->dispatch($message);
    }

    /**
     * Get client IP address
     */
    private function getClientIpAddress(): ?string
    {
        if (!isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !isset($_SERVER['REMOTE_ADDR'])) {
            return null;
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Get user agent
     */
    private function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}
