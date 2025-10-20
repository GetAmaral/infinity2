<?php

declare(strict_types=1);

namespace App\Repository;

use App\Repository\Generated\TalkTypeRepositoryGenerated;

/**
 * TalkType Repository - Custom repository for TalkType entity queries
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Provides optimized queries for:
 * - Active talk types filtering
 * - Channel-based filtering
 * - Default type selection
 * - Organization-scoped queries
 * - Analytics and usage statistics
 *
 * @generated once by Luminai Code Generator
 */
class TalkTypeRepository extends TalkTypeRepositoryGenerated
{
    /**
     * Find all active talk types for an organization
     *
     * @param \App\Entity\Organization $organization
     * @return \App\Entity\TalkType[]
     */
    public function findActiveByOrganization(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->andWhere('e.visible = :visible')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->setParameter('visible', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->addOrderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find talk types by channel
     *
     * @param \App\Entity\Organization $organization
     * @param string $channel
     * @return \App\Entity\TalkType[]
     */
    public function findByChannel(\App\Entity\Organization $organization, string $channel): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.channel = :channel')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('channel', $channel)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->addOrderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find default talk type for a channel
     *
     * @param \App\Entity\Organization $organization
     * @param string $channel
     * @return \App\Entity\TalkType|null
     */
    public function findDefaultByChannel(\App\Entity\Organization $organization, string $channel): ?\App\Entity\TalkType
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.channel = :channel')
            ->andWhere('e.default = :default')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('channel', $channel)
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all default talk types
     *
     * @param \App\Entity\Organization $organization
     * @return \App\Entity\TalkType[]
     */
    public function findDefaults(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.default = :default')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->addOrderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find talk types by category
     *
     * @param \App\Entity\Organization $organization
     * @param string $category
     * @return \App\Entity\TalkType[]
     */
    public function findByCategory(\App\Entity\Organization $organization, string $category): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.category = :category')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->addOrderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find automated talk types
     *
     * @param \App\Entity\Organization $organization
     * @return \App\Entity\TalkType[]
     */
    public function findAutomated(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.automated = :automated')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('automated', true)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find talk types with SLA enabled
     *
     * @param \App\Entity\Organization $organization
     * @return \App\Entity\TalkType[]
     */
    public function findWithSla(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.slaEnabled = :slaEnabled')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('slaEnabled', true)
            ->setParameter('active', true)
            ->orderBy('e.slaHours', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find talk types by direction
     *
     * @param \App\Entity\Organization $organization
     * @param string $direction (inbound, outbound, bidirectional)
     * @return \App\Entity\TalkType[]
     */
    public function findByDirection(\App\Entity\Organization $organization, string $direction): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.direction = :direction')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('direction', $direction)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find talk types that allow bulk sending
     *
     * @param \App\Entity\Organization $organization
     * @return \App\Entity\TalkType[]
     */
    public function findBulkSendingEnabled(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.allowsBulkSending = :allowsBulkSending')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('allowsBulkSending', true)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get most used talk types
     *
     * @param \App\Entity\Organization $organization
     * @param int $limit
     * @return \App\Entity\TalkType[]
     */
    public function findMostUsed(\App\Entity\Organization $organization, int $limit = 10): array
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
     * Find talk types with compliance enabled
     *
     * @param \App\Entity\Organization $organization
     * @return \App\Entity\TalkType[]
     */
    public function findComplianceEnabled(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.complianceEnabled = :complianceEnabled')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('complianceEnabled', true)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find talk type by code
     *
     * @param \App\Entity\Organization $organization
     * @param string $code
     * @return \App\Entity\TalkType|null
     */
    public function findByCode(\App\Entity\Organization $organization, string $code): ?\App\Entity\TalkType
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
     * Count talk types by channel for an organization
     *
     * @param \App\Entity\Organization $organization
     * @return array<string, int>
     */
    public function countByChannel(\App\Entity\Organization $organization): array
    {
        $results = $this->createQueryBuilder('e')
            ->select('e.channel', 'COUNT(e.id) as count')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->groupBy('e.channel')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['channel']] = (int)$result['count'];
        }

        return $counts;
    }
}
