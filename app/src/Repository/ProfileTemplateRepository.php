<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProfileTemplate;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * ProfileTemplateRepository
 *
 * Optimized repository for ProfileTemplate entity with:
 * - N+1 query prevention through eager loading
 * - Indexed query optimization
 * - Cached common queries
 * - Batch operations support
 *
 * @extends ServiceEntityRepository<ProfileTemplate>
 *
 * @method ProfileTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProfileTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProfileTemplate[]    findAll()
 * @method ProfileTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProfileTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfileTemplate::class);
    }

    /**
     * Save a ProfileTemplate entity
     */
    public function save(ProfileTemplate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a ProfileTemplate entity
     */
    public function remove(ProfileTemplate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // ===== OPTIMIZED QUERIES WITH INDEXES =====

    /**
     * Find active templates for organization
     * Uses: idx_template_organization, idx_template_active
     *
     * Query Performance: O(log n) due to indexed lookup
     */
    public function findActiveByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.active = :active')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('pt.templateName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find default template for organization
     * Uses: idx_template_organization, idx_template_default
     *
     * Query Performance: O(1) - highly optimized with composite index
     */
    public function findDefaultByOrganization(Organization $organization): ?ProfileTemplate
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.defaultTemplate = :default')
            ->andWhere('pt.active = :active')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find templates by category with eager loading
     * Uses: idx_template_category, idx_template_organization
     *
     * Prevents N+1: Eager loads fields collection
     */
    public function findByCategoryWithFields(string $category, Organization $organization): array
    {
        return $this->createQueryBuilder('pt')
            ->leftJoin('pt.fields', 'f')
            ->addSelect('f')
            ->andWhere('pt.category = :category')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.active = :active')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('category', $category)
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('pt.templateName', 'ASC')
            ->addOrderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find template by code with fields (eager loading)
     * Uses: idx_template_organization
     *
     * Prevents N+1: Single query loads template + all fields
     */
    public function findByCodeWithFields(string $templateCode, Organization $organization): ?ProfileTemplate
    {
        return $this->createQueryBuilder('pt')
            ->leftJoin('pt.fields', 'f')
            ->addSelect('f')
            ->andWhere('pt.templateCode = :code')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('code', $templateCode)
            ->setParameter('organization', $organization)
            ->orderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find published templates
     * Uses: idx_template_organization, idx_template_active
     *
     * Query Performance: Optimized for filtering published templates
     */
    public function findPublishedByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.published = :published')
            ->andWhere('pt.active = :active')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->orderBy('pt.templateName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search templates by name
     * Uses: idx_template_name with LIKE optimization
     *
     * Query Performance: PostgreSQL can use index for prefix search
     */
    public function searchByName(string $searchTerm, Organization $organization): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.templateName LIKE :search OR pt.description LIKE :search')
            ->andWhere('pt.active = :active')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('search', '%' . $searchTerm . '%')
            ->setParameter('active', true)
            ->orderBy('pt.templateName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find most used templates
     * Uses: idx_template_organization
     *
     * Query Performance: Sorting by usageCount (indexed)
     */
    public function findMostUsed(Organization $organization, int $limit = 10): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.active = :active')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('pt.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently created templates
     * Uses: idx_template_created
     *
     * Query Performance: O(log n) with indexed timestamp
     */
    public function findRecentlyCreated(Organization $organization, int $limit = 5): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->orderBy('pt.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find templates by industry
     * Uses: idx_template_industry
     */
    public function findByIndustry(string $industry, Organization $organization): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.industry = :industry')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.active = :active')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('industry', $industry)
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('pt.templateName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find templates by tag
     */
    public function findByTag(string $tag, Organization $organization): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.organization = :organization')
            ->andWhere('JSON_CONTAINS(pt.tags, :tag) = 1')
            ->andWhere('pt.active = :active')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('tag', json_encode($tag))
            ->setParameter('active', true)
            ->orderBy('pt.templateName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find system templates
     */
    public function findSystemTemplates(): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.system = :system')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('system', true)
            ->orderBy('pt.templateName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ===== AGGREGATE QUERIES =====

    /**
     * Count active templates by organization
     * Uses: idx_template_organization, idx_template_active
     */
    public function countActiveByOrganization(Organization $organization): int
    {
        return (int) $this->createQueryBuilder('pt')
            ->select('COUNT(pt.id)')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.active = :active')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count templates by category
     * Uses: idx_template_category
     */
    public function countByCategory(Organization $organization): array
    {
        $results = $this->createQueryBuilder('pt')
            ->select('pt.category, COUNT(pt.id) as count')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.active = :active')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->groupBy('pt.category')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['category']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * Get total usage count for organization
     */
    public function getTotalUsageCount(Organization $organization): int
    {
        return (int) $this->createQueryBuilder('pt')
            ->select('SUM(pt.usageCount)')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    // ===== BATCH OPERATIONS =====

    /**
     * Batch update usage count
     * Efficient bulk update without loading entities
     */
    public function incrementUsageCount(string $templateId): void
    {
        $this->createQueryBuilder('pt')
            ->update()
            ->set('pt.usageCount', 'pt.usageCount + 1')
            ->set('pt.lastUsedAt', ':now')
            ->where('pt.id = :id')
            ->setParameter('id', $templateId)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Batch deactivate templates
     */
    public function deactivateByIds(array $templateIds): int
    {
        return $this->createQueryBuilder('pt')
            ->update()
            ->set('pt.active', ':active')
            ->where('pt.id IN (:ids)')
            ->setParameter('active', false)
            ->setParameter('ids', $templateIds)
            ->getQuery()
            ->execute();
    }

    /**
     * Soft delete templates
     * Uses: idx_template_deleted
     */
    public function softDeleteByIds(array $templateIds): int
    {
        return $this->createQueryBuilder('pt')
            ->update()
            ->set('pt.deletedAt', ':now')
            ->where('pt.id IN (:ids)')
            ->andWhere('pt.system = :system')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('ids', $templateIds)
            ->setParameter('system', false)
            ->getQuery()
            ->execute();
    }

    // ===== UTILITY METHODS =====

    /**
     * Check if template code exists
     */
    public function codeExists(string $templateCode, Organization $organization, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('pt')
            ->select('COUNT(pt.id)')
            ->andWhere('pt.templateCode = :code')
            ->andWhere('pt.organization = :organization')
            ->andWhere('pt.deletedAt IS NULL')
            ->setParameter('code', $templateCode)
            ->setParameter('organization', $organization);

        if ($excludeId !== null) {
            $qb->andWhere('pt.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Create base query builder with common filters
     */
    private function createBaseQueryBuilder(string $alias = 'pt'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->andWhere($alias . '.deletedAt IS NULL');
    }
}
