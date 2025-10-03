<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Trait\AuditTrait;
use App\Entity\User;
use App\Message\AuditEventMessage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * AuditSubscriber automatically populates audit fields for entities using AuditTrait
 *
 * This subscriber listens to Doctrine lifecycle events and:
 * - Sets createdAt/createdBy on entity creation
 * - Updates updatedAt/updatedBy on entity modification
 * - Handles cases where no user is authenticated (CLI, fixtures, etc.)
 * - Dispatches audit events asynchronously for performance
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class AuditSubscriber
{
    public function __construct(
        private readonly Security $security,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager
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

        if ($currentUser) {
            $entity->setUpdatedBy($currentUser);
        }

        // Log audit event even if no user (for CLI/background operations)
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
     * Dispatch audit event message for asynchronous logging
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

        // Sanitize change set for updates
        $changes = [];
        if ($action === 'entity_updated' && !empty($changeSet)) {
            $changes = $this->sanitizeChangeSet($changeSet, $entity);
        }

        // Dispatch async message instead of logging directly
        $message = new AuditEventMessage(
            action: $action,
            entityClass: $entityClass,
            entityId: $entityId,
            userId: $user?->getId()?->toString(),
            userEmail: $user?->getEmail(),
            timestamp: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ipAddress: $this->getClientIpAddress(),
            userAgent: $this->getUserAgent(),
            changes: $changes
        );

        $this->messageBus->dispatch($message);
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
     * For JSON fields, performs deep comparison and returns only changed keys
     */
    private function sanitizeChangeSet(array $changeSet, object $entity): array
    {
        $sensitiveFields = ['password', 'apiToken', 'verificationToken'];
        $sanitized = [];

        // Get entity metadata to detect JSON fields
        $metadata = $this->entityManager->getClassMetadata(get_class($entity));

        foreach ($changeSet as $field => $values) {
            if (in_array($field, $sensitiveFields, true)) {
                $sanitized[$field] = ['[REDACTED]', '[REDACTED]'];
                continue;
            }

            // Check if this is a JSON field
            if ($this->isJsonField($metadata, $field)) {
                // Perform deep comparison for JSON fields
                $jsonDiff = $this->getJsonDiff($values[0], $values[1]);

                // Only add to sanitized if there are actual changes
                if (!empty($jsonDiff)) {
                    $sanitized[$field] = $jsonDiff;
                }
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
     * Check if a field is a JSON type in Doctrine metadata
     */
    private function isJsonField($metadata, string $fieldName): bool
    {
        if (!$metadata->hasField($fieldName)) {
            return false;
        }

        $fieldMapping = $metadata->getFieldMapping($fieldName);
        return in_array($fieldMapping['type'], ['json', 'json_array'], true);
    }

    /**
     * Get recursive diff for JSON fields
     * Returns only the changed keys with old and new values
     */
    private function getJsonDiff(mixed $oldValue, mixed $newValue): array
    {
        // Parse JSON values if they're strings
        $old = $this->parseJsonValue($oldValue);
        $new = $this->parseJsonValue($newValue);

        // If either is not an array, return simple diff
        if (!is_array($old) || !is_array($new)) {
            return [
                'old' => $this->serializeValue($old),
                'new' => $this->serializeValue($new)
            ];
        }

        // Perform recursive comparison
        return $this->recursiveJsonDiff($old, $new);
    }

    /**
     * Parse JSON value - handles both arrays and JSON strings
     */
    private function parseJsonValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        return $value;
    }

    /**
     * Recursively compare two arrays and return only changed keys
     */
    private function recursiveJsonDiff(array $old, array $new): array
    {
        $diff = [];

        // Get all keys from both arrays
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($allKeys as $key) {
            $oldExists = array_key_exists($key, $old);
            $newExists = array_key_exists($key, $new);

            // Key was removed
            if ($oldExists && !$newExists) {
                $diff[$key] = [
                    'old' => $this->serializeValue($old[$key]),
                    'new' => null,
                    'status' => 'removed'
                ];
                continue;
            }

            // Key was added
            if (!$oldExists && $newExists) {
                $diff[$key] = [
                    'old' => null,
                    'new' => $this->serializeValue($new[$key]),
                    'status' => 'added'
                ];
                continue;
            }

            // Both exist - check if values are different
            $oldVal = $old[$key];
            $newVal = $new[$key];

            // If both are arrays, recurse
            if (is_array($oldVal) && is_array($newVal)) {
                $nestedDiff = $this->recursiveJsonDiff($oldVal, $newVal);
                if (!empty($nestedDiff)) {
                    $diff[$key] = $nestedDiff;
                }
            } else {
                // Scalar comparison
                if ($oldVal !== $newVal) {
                    $diff[$key] = [
                        'old' => $this->serializeValue($oldVal),
                        'new' => $this->serializeValue($newVal),
                        'status' => 'modified'
                    ];
                }
            }
        }

        return $diff;
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
