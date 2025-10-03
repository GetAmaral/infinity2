<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Audit Event Message for asynchronous audit logging.
 *
 * This message is dispatched to the message queue when an entity is created or updated.
 * It allows audit logging to happen asynchronously without blocking entity operations.
 */
final readonly class AuditEventMessage
{
    public function __construct(
        public string $action,
        public string $entityClass,
        public ?string $entityId,
        public ?string $userId,
        public ?string $userEmail,
        public string $timestamp,
        public ?string $ipAddress,
        public ?string $userAgent,
        public array $changes = []
    ) {}
}
