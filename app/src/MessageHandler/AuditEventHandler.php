<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\AuditLog;
use App\Message\AuditEventMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Asynchronous handler for audit event messages.
 *
 * This handler processes audit events from the message queue and:
 * - Writes to the audit log file (for real-time monitoring)
 * - Stores in the audit_log database table (for historical queries)
 *
 * By processing events asynchronously, we eliminate the performance impact
 * of I/O operations during entity persist/flush.
 */
#[AsMessageHandler]
final class AuditEventHandler
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.audit')]
        private readonly LoggerInterface $auditLogger,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository
    ) {}

    public function __invoke(AuditEventMessage $message): void
    {
        // 1. Write to log file (for real-time monitoring)
        $logData = [
            'action' => $message->action,
            'entity_class' => $message->entityClass,
            'entity_id' => $message->entityId,
            'user_id' => $message->userId,
            'user_email' => $message->userEmail,
            'timestamp' => $message->timestamp,
            'ip_address' => $message->ipAddress,
            'user_agent' => $message->userAgent,
        ];

        if (!empty($message->changes)) {
            $logData['changes'] = $message->changes;
        }

        $this->auditLogger->info('Audit event recorded', $logData);

        // 2. Store in database (for historical queries)
        $this->storeInDatabase($message);
    }

    /**
     * Store audit event in database for historical tracking
     */
    private function storeInDatabase(AuditEventMessage $message): void
    {
        try {
            $auditLog = new AuditLog();
            $auditLog->setAction($message->action);
            $auditLog->setEntityClass($message->entityClass);

            // Parse entity ID
            if ($message->entityId && $message->entityId !== 'unknown' && $message->entityId !== 'not-generated-yet') {
                try {
                    $auditLog->setEntityId(Uuid::fromString($message->entityId));
                } catch (\InvalidArgumentException $e) {
                    // If ID is not a valid UUID, skip database storage
                    // (this can happen for entities before ID generation)
                    return;
                }
            } else {
                // Skip database storage if entity ID is not yet available
                return;
            }

            // Look up user if userId provided
            if ($message->userId) {
                try {
                    $user = $this->userRepository->find(Uuid::fromString($message->userId));
                    $auditLog->setUser($user);
                } catch (\Exception $e) {
                    // User not found or invalid UUID - continue without user
                }
            }

            // Store changes
            $auditLog->setChanges($message->changes);

            // Store metadata
            $metadata = [];
            if ($message->ipAddress) {
                $metadata['ip_address'] = $message->ipAddress;
            }
            if ($message->userAgent) {
                $metadata['user_agent'] = $message->userAgent;
            }
            if ($message->userEmail) {
                $metadata['user_email'] = $message->userEmail;
            }
            if (!empty($metadata)) {
                $auditLog->setMetadata($metadata);
            }

            // Set timestamp from message
            if ($message->timestamp) {
                try {
                    $auditLog->setCreatedAt(new \DateTimeImmutable($message->timestamp));
                } catch (\Exception $e) {
                    // Use current time if timestamp parsing fails
                }
            }

            $this->entityManager->persist($auditLog);
            $this->entityManager->flush();

        } catch (\Exception $e) {
            // Log error but don't fail the message processing
            $this->auditLogger->error('Failed to store audit log in database', [
                'error' => $e->getMessage(),
                'entity_class' => $message->entityClass,
                'entity_id' => $message->entityId,
            ]);
        }
    }
}
