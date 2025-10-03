<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\AuditLogRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Service for enforcing audit log retention policies
 *
 * Automatically deletes audit logs older than configured retention periods
 * based on entity type.
 */
final class AuditRetentionService
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository,
        private readonly ParameterBagInterface $params
    ) {}

    /**
     * Enforce retention policies for all entity types
     *
     * Deletes audit logs older than the configured retention period
     * for each entity class.
     *
     * @return array<string, int> Statistics showing deleted count per entity
     */
    public function enforceRetentionPolicies(): array
    {
        $stats = [];
        $policies = $this->params->get('audit.retention.policies');

        foreach ($policies as $entityClass => $retentionDays) {
            $cutoffDate = new \DateTimeImmutable("-{$retentionDays} days");

            $deleted = $this->auditLogRepository->deleteOlderThan(
                $entityClass,
                $cutoffDate
            );

            $stats[$entityClass] = $deleted;
        }

        return $stats;
    }

    /**
     * Anonymize old audit data for GDPR compliance
     *
     * Removes personally identifiable information (PII) from audit logs
     * older than the configured anonymization period.
     *
     * @return int Number of records anonymized
     */
    public function anonymizeOldData(): int
    {
        if (!$this->params->get('audit.compliance.gdpr_enabled')) {
            return 0;
        }

        $anonymizeAfter = $this->params->get('audit.compliance.anonymize_after_days');
        $cutoffDate = new \DateTimeImmutable("-{$anonymizeAfter} days");

        return $this->auditLogRepository->anonymizeUserData($cutoffDate);
    }

    /**
     * Get retention period for a specific entity class
     *
     * @param string $entityClass Fully qualified entity class name
     * @return int Retention period in days
     */
    public function getRetentionPeriod(string $entityClass): int
    {
        $policies = $this->params->get('audit.retention.policies');

        return $policies[$entityClass] ?? $this->params->get('audit.retention.default_days');
    }

    /**
     * Calculate when an audit log will be deleted
     *
     * @param string $entityClass Fully qualified entity class name
     * @param \DateTimeInterface $createdAt When the audit log was created
     * @return \DateTimeImmutable When the audit log will be deleted
     */
    public function calculateDeletionDate(string $entityClass, \DateTimeInterface $createdAt): \DateTimeImmutable
    {
        $retentionDays = $this->getRetentionPeriod($entityClass);

        return \DateTimeImmutable::createFromInterface($createdAt)
            ->modify("+{$retentionDays} days");
    }

    /**
     * Check if audit logging is enabled for encryption
     *
     * @return bool
     */
    public function isEncryptionEnabled(): bool
    {
        return $this->params->get('audit.encryption.enabled');
    }

    /**
     * Check if GDPR compliance is enabled
     *
     * @return bool
     */
    public function isGdprEnabled(): bool
    {
        return $this->params->get('audit.compliance.gdpr_enabled');
    }
}
