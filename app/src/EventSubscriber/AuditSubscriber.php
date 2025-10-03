<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Trait\AuditTrait;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * AuditSubscriber automatically populates audit fields for entities using AuditTrait
 *
 * This subscriber listens to Doctrine lifecycle events and:
 * - Sets createdAt/createdBy on entity creation
 * - Updates updatedAt/updatedBy on entity modification
 * - Handles cases where no user is authenticated (CLI, fixtures, etc.)
 * - Logs audit events for security monitoring
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class AuditSubscriber
{
    public function __construct(
        private readonly Security $security,
        #[Autowire(service: 'monolog.logger.audit')]
        private readonly LoggerInterface $auditLogger
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$this->hasAuditTrait($entity)) {
            return;
        }

        $now = new \DateTimeImmutable();
        $currentUser = $this->getCurrentUser();

        // Set creation audit fields
        $entity->setCreatedAt($now);
        $entity->setUpdatedAt($now);

        if(!$currentUser){
            return;
        }

        $entity->setCreatedBy($currentUser);
        $entity->setUpdatedBy($currentUser);

        $this->logAuditEvent('entity_created', $entity, $currentUser);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$this->hasAuditTrait($entity)) {
            return;
        }

        // Update modification audit fields
        $entity->updateAuditTimestamp();

        $currentUser = $this->getCurrentUser();

        if(!$currentUser){
            return;
        }

        $entity->setUpdatedBy($currentUser);

        $this->logAuditEvent('entity_updated', $entity, $currentUser, $args->getEntityChangeSet());
    }

    /**
     * Check if entity uses the AuditTrait
     */
    private function hasAuditTrait(object $entity): bool
    {
        $traits = $this->getClassTraitsRecursive($entity);
        return in_array(AuditTrait::class, $traits, true);
    }

    /**
     * Get all traits used by a class, including parent classes
     */
    private function getClassTraitsRecursive(object $entity): array
    {
        $traits = [];
        $class = get_class($entity);

        do {
            $traits = array_merge($traits, array_keys(class_uses($class)));
            $class = get_parent_class($class);
        } while ($class);

        return array_unique($traits);
    }

    /**
     * Get the currently authenticated user
     * Returns null for unauthenticated contexts (CLI, fixtures, etc.)
     */
    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();

        // Ensure we have a User entity (not just any UserInterface implementation)
        return $user instanceof User ? $user : null;
    }

    /**
     * Log audit events for security monitoring
     */
    private function logAuditEvent(
        string $action,
        object $entity,
        ?User $user,
        array $changeSet = []
    ): void {
        $entityClass = get_class($entity);

        // Safely get entity ID, handling cases where it might not be initialized yet
        $entityId = 'unknown';
        if (method_exists($entity, 'getId')) {
            try {
                $id = $entity->getId();
                $entityId = $id ? (string) $id : 'not-generated-yet';
            } catch (\Error $e) {
                // ID not yet initialized (before persist)
                $entityId = 'not-generated-yet';
            }
        }

        $logData = [
            'action' => $action,
            'entity_class' => $entityClass,
            'entity_id' => $entityId,
            'user_id' => $user?->getId()?->toString(),
            'user_email' => $user?->getEmail(),
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'ip_address' => $this->getClientIpAddress(),
            'user_agent' => $this->getUserAgent(),
        ];

        // Add change information for updates
        if ($action === 'entity_updated' && !empty($changeSet)) {
            $logData['changes'] = $this->sanitizeChangeSet($changeSet);
        }

        $this->auditLogger->info('Audit event recorded', $logData);
    }

    /**
     * Get client IP address from request context
     */
    private function getClientIpAddress(): ?string
    {
        if (!isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !isset($_SERVER['REMOTE_ADDR'])) {
            return null; // CLI context
        }

        // Handle X-Forwarded-For header (proxy/load balancer)
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Get user agent from request context
     */
    private function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Sanitize change set to remove sensitive data from logs
     */
    private function sanitizeChangeSet(array $changeSet): array
    {
        $sensitiveFields = ['password', 'apiToken', 'verificationToken'];
        $sanitized = [];

        foreach ($changeSet as $field => $values) {
            if (in_array($field, $sensitiveFields, true)) {
                $sanitized[$field] = ['[REDACTED]', '[REDACTED]'];
            } else {
                // Convert objects to strings for logging
                $sanitized[$field] = [
                    $this->serializeValue($values[0]),
                    $this->serializeValue($values[1])
                ];
            }
        }

        return $sanitized;
    }

    /**
     * Convert values to loggable format
     */
    private function serializeValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            if (method_exists($value, 'getId')) {
                return get_class($value) . '#' . $value->getId();
            }
            return get_class($value);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
