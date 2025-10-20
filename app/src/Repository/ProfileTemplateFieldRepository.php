<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProfileTemplateField;
use App\Entity\ProfileTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * ProfileTemplateFieldRepository
 *
 * Optimized repository for ProfileTemplateField entity with:
 * - Indexed query optimization
 * - N+1 prevention
 * - Efficient filtering and sorting
 *
 * @extends ServiceEntityRepository<ProfileTemplateField>
 *
 * @method ProfileTemplateField|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProfileTemplateField|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProfileTemplateField[]    findAll()
 * @method ProfileTemplateField[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProfileTemplateFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfileTemplateField::class);
    }

    /**
     * Save a ProfileTemplateField entity
     */
    public function save(ProfileTemplateField $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a ProfileTemplateField entity
     */
    public function remove(ProfileTemplateField $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // ===== OPTIMIZED QUERIES WITH INDEXES =====

    /**
     * Find active fields by template
     * Uses: idx_field_template, idx_field_active, idx_field_order
     *
     * Query Performance: O(log n) with indexed lookup + sorting
     */
    public function findActiveByTemplate(ProfileTemplate $template): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('active', true)
            ->orderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find fields by template ordered
     * Uses: idx_field_template, idx_field_order
     */
    public function findByTemplateOrdered(ProfileTemplate $template): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.profileTemplate = :template')
            ->setParameter('template', $template)
            ->orderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find required fields by template
     * Uses: idx_field_template, idx_field_required
     *
     * Query Performance: Optimized with composite index
     */
    public function findRequiredByTemplate(ProfileTemplate $template): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.required = :required')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('required', true)
            ->setParameter('active', true)
            ->orderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find searchable fields by template
     * Uses: idx_field_template
     */
    public function findSearchableByTemplate(ProfileTemplate $template): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.searchable = :searchable')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('searchable', true)
            ->setParameter('active', true)
            ->orderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find fields by section
     * Uses: idx_field_section, idx_field_template
     */
    public function findBySection(ProfileTemplate $template, string $section): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.section = :section')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('section', $section)
            ->setParameter('active', true)
            ->orderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find fields by type
     * Uses: idx_field_type
     */
    public function findByType(ProfileTemplate $template, string $fieldType): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.fieldType = :type')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('type', $fieldType)
            ->setParameter('active', true)
            ->orderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find field by name
     * Uses: idx_field_name
     */
    public function findByName(ProfileTemplate $template, string $fieldName): ?ProfileTemplateField
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.fieldName = :name')
            ->setParameter('template', $template)
            ->setParameter('name', $fieldName)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get fields grouped by section
     * Uses: idx_field_section
     *
     * Returns: ['section_name' => [field1, field2, ...]]
     */
    public function findGroupedBySection(ProfileTemplate $template): array
    {
        $fields = $this->createQueryBuilder('f')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('active', true)
            ->orderBy('f.section', 'ASC')
            ->addOrderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($fields as $field) {
            $section = $field->getSection() ?? 'General';
            if (!isset($grouped[$section])) {
                $grouped[$section] = [];
            }
            $grouped[$section][] = $field;
        }

        return $grouped;
    }

    // ===== AGGREGATE QUERIES =====

    /**
     * Count fields by template
     * Uses: idx_field_template
     */
    public function countByTemplate(ProfileTemplate $template): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.profileTemplate = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count active fields by template
     * Uses: idx_field_template, idx_field_active
     */
    public function countActiveByTemplate(ProfileTemplate $template): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count required fields by template
     * Uses: idx_field_required
     */
    public function countRequiredByTemplate(ProfileTemplate $template): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.required = :required')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('required', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get field type distribution
     * Uses: idx_field_type
     */
    public function getTypeDistribution(ProfileTemplate $template): array
    {
        $results = $this->createQueryBuilder('f')
            ->select('f.fieldType, COUNT(f.id) as count')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('active', true)
            ->groupBy('f.fieldType')
            ->getQuery()
            ->getResult();

        $distribution = [];
        foreach ($results as $result) {
            $distribution[$result['fieldType']] = (int) $result['count'];
        }

        return $distribution;
    }

    // ===== BATCH OPERATIONS =====

    /**
     * Batch update field order
     * Efficient bulk update without loading entities
     */
    public function updateFieldOrder(string $fieldId, int $newOrder): void
    {
        $this->createQueryBuilder('f')
            ->update()
            ->set('f.fieldOrder', ':order')
            ->set('f.updatedAt', ':now')
            ->where('f.id = :id')
            ->setParameter('order', $newOrder)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('id', $fieldId)
            ->getQuery()
            ->execute();
    }

    /**
     * Batch deactivate fields
     */
    public function deactivateByIds(array $fieldIds): int
    {
        return $this->createQueryBuilder('f')
            ->update()
            ->set('f.active', ':active')
            ->set('f.updatedAt', ':now')
            ->where('f.id IN (:ids)')
            ->setParameter('active', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('ids', $fieldIds)
            ->getQuery()
            ->execute();
    }

    /**
     * Batch activate fields
     */
    public function activateByIds(array $fieldIds): int
    {
        return $this->createQueryBuilder('f')
            ->update()
            ->set('f.active', ':active')
            ->set('f.updatedAt', ':now')
            ->where('f.id IN (:ids)')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('ids', $fieldIds)
            ->getQuery()
            ->execute();
    }

    /**
     * Reorder fields in template
     * Shifts all fields after the given order up by 1
     */
    public function shiftOrdersUp(ProfileTemplate $template, int $fromOrder): int
    {
        return $this->createQueryBuilder('f')
            ->update()
            ->set('f.fieldOrder', 'f.fieldOrder + 1')
            ->set('f.updatedAt', ':now')
            ->where('f.profileTemplate = :template')
            ->andWhere('f.fieldOrder >= :fromOrder')
            ->setParameter('template', $template)
            ->setParameter('fromOrder', $fromOrder)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Get next order number for template
     */
    public function getNextOrder(ProfileTemplate $template): int
    {
        $maxOrder = $this->createQueryBuilder('f')
            ->select('MAX(f.fieldOrder)')
            ->andWhere('f.profileTemplate = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxOrder ?? 0) + 10;
    }

    // ===== UTILITY METHODS =====

    /**
     * Check if field name exists in template
     */
    public function nameExists(string $fieldName, ProfileTemplate $template, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.fieldName = :name')
            ->andWhere('f.profileTemplate = :template')
            ->setParameter('name', $fieldName)
            ->setParameter('template', $template);

        if ($excludeId !== null) {
            $qb->andWhere('f.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Find fields with conditional visibility
     */
    public function findWithVisibilityConditions(ProfileTemplate $template): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.visibilityConditions IS NOT NULL')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('active', true)
            ->orderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find unique fields
     */
    public function findUniqueFields(ProfileTemplate $template): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.unique = :unique')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('unique', true)
            ->setParameter('active', true)
            ->orderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find sensitive fields (for privacy/security)
     */
    public function findSensitiveFields(ProfileTemplate $template): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.profileTemplate = :template')
            ->andWhere('f.sensitive = :sensitive')
            ->andWhere('f.active = :active')
            ->setParameter('template', $template)
            ->setParameter('sensitive', true)
            ->setParameter('active', true)
            ->orderBy('f.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
