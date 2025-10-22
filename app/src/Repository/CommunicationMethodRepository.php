<?php

declare(strict_types=1);

namespace App\Repository;

use App\Repository\Generated\CommunicationMethodRepositoryGenerated;
use App\Entity\Organization;
use App\Entity\CommunicationMethod;

/**
 * CommunicationMethod Repository - Custom repository for CommunicationMethod entity queries
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Provides optimized queries for:
 * - Active communication methods filtering
 * - Channel type-based filtering
 * - Verified methods selection
 * - Default method selection
 * - Organization-scoped queries
 * - Provider-based filtering
 * - Analytics and usage statistics
 * - Health and performance monitoring
 *
 * @generated once by Luminai Code Generator
 */
class CommunicationMethodRepository extends CommunicationMethodRepositoryGenerated
{
    /**
     * Find all active communication methods for an organization
     *
     * @param Organization $organization
     * @return CommunicationMethod[]
     */
    public function findActiveByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->andWhere('e.visible = :visible')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->setParameter('visible', true)
            ->orderBy('e.priority', 'ASC')
            ->addOrderBy('e.sortOrder', 'ASC')
            ->addOrderBy('e.methodName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find verified communication methods
     *
     * @param Organization $organization
     * @return CommunicationMethod[]
     */
    public function findVerifiedByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.verified = :verified')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('verified', true)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->addOrderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find communication methods by channel type
     *
     * @param Organization $organization
     * @param string $channelType
     * @return CommunicationMethod[]
     */
    public function findByChannelType(Organization $organization, string $channelType): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.channelType = :channelType')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('channelType', $channelType)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->addOrderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find default communication method for a channel type
     *
     * @param Organization $organization
     * @param string $channelType
     * @return CommunicationMethod|null
     */
    public function findDefaultByChannelType(Organization $organization, string $channelType): ?CommunicationMethod
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.channelType = :channelType')
            ->andWhere('e.default = :default')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('channelType', $channelType)
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all default communication methods
     *
     * @param Organization $organization
     * @return CommunicationMethod[]
     */
    public function findDefaults(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.default = :default')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->addOrderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find communication methods by category
     *
     * @param Organization $organization
     * @param string $category
     * @return CommunicationMethod[]
     */
    public function findByCategory(Organization $organization, string $category): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.category = :category')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->addOrderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find communication methods by provider
     *
     * @param Organization $organization
     * @param string $provider
     * @return CommunicationMethod[]
     */
    public function findByProvider(Organization $organization, string $provider): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.provider = :provider')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('provider', $provider)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find automated communication methods
     *
     * @param Organization $organization
     * @return CommunicationMethod[]
     */
    public function findAutomated(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.automated = :automated')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('automated', true)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find two-way communication methods
     *
     * @param Organization $organization
     * @return CommunicationMethod[]
     */
    public function findTwoWay(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.supportsTwoWay = :supportsTwoWay')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('supportsTwoWay', true)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find methods supporting bulk sending
     *
     * @param Organization $organization
     * @return CommunicationMethod[]
     */
    public function findBulkSendingEnabled(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.supportsBulkSending = :supportsBulkSending')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('supportsBulkSending', true)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find methods with tracking enabled
     *
     * @param Organization $organization
     * @return CommunicationMethod[]
     */
    public function findTrackingEnabled(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.supportsTracking = :supportsTracking')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('supportsTracking', true)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find methods with encryption support
     *
     * @param Organization $organization
     * @return CommunicationMethod[]
     */
    public function findEncryptionSupported(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.supportsEncryption = :supportsEncryption')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('supportsEncryption', true)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find methods with compliance enabled
     *
     * @param Organization $organization
     * @return CommunicationMethod[]
     */
    public function findComplianceEnabled(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.complianceEnabled = :complianceEnabled')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('complianceEnabled', true)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find methods requiring opt-in
     *
     * @param Organization $organization
     * @return CommunicationMethod[]
     */
    public function findRequiringOptIn(Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.requiresOptIn = :requiresOptIn')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('requiresOptIn', true)
            ->setParameter('active', true)
            ->orderBy('e.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find ready methods (active and verified if required)
     *
     * @param Organization $organization
     * @return CommunicationMethod[]
     */
    public function findReady(Organization $organization): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('active', true);

        // Add verification check - methods either don't require verification or are verified
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('e.requiresVerification', ':requiresVerificationFalse'),
                $qb->expr()->eq('e.verified', ':verifiedTrue')
            )
        )
        ->setParameter('requiresVerificationFalse', false)
        ->setParameter('verifiedTrue', true);

        return $qb->orderBy('e.priority', 'ASC')
            ->addOrderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get most used communication methods
     *
     * @param Organization $organization
     * @param int $limit
     * @return CommunicationMethod[]
     */
    public function findMostUsed(Organization $organization, int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('e.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find methods by priority level
     *
     * @param Organization $organization
     * @param string $priority (low, normal, high, urgent, critical)
     * @return CommunicationMethod[]
     */
    public function findByPriority(Organization $organization, string $priority): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.priority = :priority')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('priority', $priority)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find method by code
     *
     * @param Organization $organization
     * @param string $code
     * @return CommunicationMethod|null
     */
    public function findByCode(Organization $organization, string $code): ?CommunicationMethod
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.code = :code')
            ->setParameter('organization', $organization)
            ->setParameter('code', strtoupper($code))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count methods by channel type for an organization
     *
     * @param Organization $organization
     * @return array<string, int>
     */
    public function countByChannelType(Organization $organization): array
    {
        $results = $this->createQueryBuilder('e')
            ->select('e.channelType', 'COUNT(e.id) as count')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->groupBy('e.channelType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['channelType']] = (int)$result['count'];
        }

        return $counts;
    }

    /**
     * Count methods by provider for an organization
     *
     * @param Organization $organization
     * @return array<string, int>
     */
    public function countByProvider(Organization $organization): array
    {
        $results = $this->createQueryBuilder('e')
            ->select('e.provider', 'COUNT(e.id) as count')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->andWhere('e.provider IS NOT NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->groupBy('e.provider')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['provider']] = (int)$result['count'];
        }

        return $counts;
    }

    /**
     * Get total statistics for organization
     *
     * @param Organization $organization
     * @return array
     */
    public function getStatistics(Organization $organization): array
    {
        $result = $this->createQueryBuilder('e')
            ->select([
                'COUNT(e.id) as total_methods',
                'SUM(e.totalSent) as total_sent',
                'SUM(e.totalDelivered) as total_delivered',
                'SUM(e.totalFailed) as total_failed',
                'SUM(e.totalCost) as total_cost',
                'SUM(CASE WHEN e.active = true THEN 1 ELSE 0 END) as active_methods',
                'SUM(CASE WHEN e.verified = true THEN 1 ELSE 0 END) as verified_methods',
            ])
            ->andWhere('e.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleResult();

        return [
            'total_methods' => (int)$result['total_methods'],
            'active_methods' => (int)$result['active_methods'],
            'verified_methods' => (int)$result['verified_methods'],
            'total_sent' => (int)$result['total_sent'],
            'total_delivered' => (int)$result['total_delivered'],
            'total_failed' => (int)$result['total_failed'],
            'total_cost' => (int)$result['total_cost'],
            'delivery_rate' => $result['total_sent'] > 0
                ? round(($result['total_delivered'] / $result['total_sent']) * 100, 2)
                : 0,
        ];
    }

    /**
     * Find methods needing verification renewal
     *
     * @param Organization $organization
     * @param int $daysBeforeExpiry
     * @return CommunicationMethod[]
     */
    public function findNeedingVerificationRenewal(Organization $organization, int $daysBeforeExpiry = 30): array
    {
        $expiryThreshold = new \DateTimeImmutable("+{$daysBeforeExpiry} days");

        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->andWhere('e.requiresVerification = :requiresVerification')
            ->andWhere('e.verificationExpiresAt IS NOT NULL')
            ->andWhere('e.verificationExpiresAt <= :expiryThreshold')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->setParameter('requiresVerification', true)
            ->setParameter('expiryThreshold', $expiryThreshold)
            ->orderBy('e.verificationExpiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find methods with low reliability score
     *
     * @param Organization $organization
     * @param int $threshold
     * @return CommunicationMethod[]
     */
    public function findLowReliability(Organization $organization, int $threshold = 80): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->andWhere('e.reliabilityScore IS NOT NULL')
            ->andWhere('e.reliabilityScore < :threshold')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->setParameter('threshold', $threshold)
            ->orderBy('e.reliabilityScore', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find methods exceeding daily limit
     *
     * @param Organization $organization
     * @param array $dailyUsage Array mapping method IDs to current usage counts
     * @return CommunicationMethod[]
     */
    public function findExceedingLimits(Organization $organization, array $dailyUsage): array
    {
        $methods = $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->andWhere('e.dailyLimit IS NOT NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $exceeding = [];
        foreach ($methods as $method) {
            $usage = $dailyUsage[$method->getId()->toRfc4122()] ?? 0;
            if ($usage >= $method->getDailyLimit()) {
                $exceeding[] = $method;
            }
        }

        return $exceeding;
    }
}
