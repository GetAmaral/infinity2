<?php

declare(strict_types=1);

namespace App\Repository;

use App\Repository\Generated\SocialMediaTypeRepositoryGenerated;

/**
 * SocialMediaType Repository - Custom repository for SocialMediaType entity queries
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Provides optimized queries for:
 * - Active social media platforms filtering
 * - Category-based filtering (social_network, professional_network, video_platform, etc.)
 * - Integration status filtering
 * - High-priority and high-adoption platforms
 * - Organization-scoped queries
 * - Analytics and usage statistics
 * - Platform capability filtering
 *
 * @generated once by Luminai Code Generator
 */
class SocialMediaTypeRepository extends SocialMediaTypeRepositoryGenerated
{
    /**
     * Find all active social media platforms for an organization
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
            ->addOrderBy('e.platformName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find social media platforms by category
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
            ->addOrderBy('e.platformName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find default social media platforms
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
            ->addOrderBy('e.platformName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find platforms with active integration
     */
    public function findIntegrated(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.integrationEnabled = :integrationEnabled')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('integrationEnabled', true)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->addOrderBy('e.platformName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find featured platforms
     */
    public function findFeatured(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.featured = :featured')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('featured', true)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->addOrderBy('e.platformName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find high-priority platforms for marketers (priority <= 25)
     */
    public function findHighPriority(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.marketerPriority IS NOT NULL')
            ->andWhere('e.marketerPriority <= :priorityThreshold')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('priorityThreshold', 25)
            ->setParameter('active', true)
            ->orderBy('e.marketerPriority', 'ASC')
            ->addOrderBy('e.platformName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find platforms with high marketer adoption (>= 70%)
     */
    public function findHighAdoption(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.marketerAdoption IS NOT NULL')
            ->andWhere('e.marketerAdoption >= :adoptionThreshold')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('adoptionThreshold', 70)
            ->setParameter('active', true)
            ->orderBy('e.marketerAdoption', 'DESC')
            ->addOrderBy('e.platformName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find platforms by primary use case
     */
    public function findByUseCase(\App\Entity\Organization $organization, string $useCase): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.primaryUseCase = :useCase')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('useCase', $useCase)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find platforms that support video content
     */
    public function findWithVideoSupport(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.supportsVideos = :supportsVideos')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('supportsVideos', true)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find platforms that support paid advertising
     */
    public function findWithAdvertisingSupport(\App\Entity\Organization $organization): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.supportsPaidAdvertising = :supportsPaidAdvertising')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('supportsPaidAdvertising', true)
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find platform by code
     */
    public function findByCode(\App\Entity\Organization $organization, string $code): ?\App\Entity\SocialMediaType
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
     * Get most used platforms
     */
    public function findMostUsed(\App\Entity\Organization $organization, int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->andWhere('e.usageCount > 0')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('e.usageCount', 'DESC')
            ->addOrderBy('e.platformName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently used platforms
     */
    public function findRecentlyUsed(\App\Entity\Organization $organization, int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.lastUsedAt IS NOT NULL')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('e.lastUsedAt', 'DESC')
            ->addOrderBy('e.platformName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count platforms by category for an organization
     */
    public function countByCategory(\App\Entity\Organization $organization): array
    {
        $results = $this->createQueryBuilder('e')
            ->select('e.category', 'COUNT(e.id) as count')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->groupBy('e.category')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['category']] = (int)$result['count'];
        }

        return $counts;
    }

    /**
     * Search platforms by name or code
     */
    public function search(\App\Entity\Organization $organization, string $searchTerm): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organization = :organization')
            ->andWhere('e.active = :active')
            ->andWhere('e.platformName LIKE :search OR e.code LIKE :search OR e.description LIKE :search')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('e.sortOrder', 'ASC')
            ->addOrderBy('e.platformName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
