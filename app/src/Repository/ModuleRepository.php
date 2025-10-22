<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Module;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ModuleRepository - Query methods for Module entity
 *
 * Provides optimized queries for CRM module management:
 * - Active/enabled module retrieval
 * - Permission-based filtering
 * - Hierarchical module queries
 * - Organization-scoped queries
 * - Dependency resolution
 * - License validation
 *
 * @extends ServiceEntityRepository<Module>
 */
class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    /**
     * Find all active and enabled modules
     */
    public function findActiveModules(?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC');

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all modules visible in menu
     */
    public function findMenuModules(?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->andWhere('m.visibleInMenu = :visible')
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->setParameter('visible', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC');

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find root modules (modules without parent)
     */
    public function findRootModules(?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.parent IS NULL')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC');

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find child modules of a parent
     */
    public function findChildModules(Module $parent): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.parent = :parent')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->setParameter('parent', $parent)
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find module by code
     */
    public function findOneByCode(string $code, ?Organization $organization = null): ?Module
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.code = :code')
            ->setParameter('code', $code);

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find modules by category
     */
    public function findByCategory(string $category, ?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.category = :category')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC');

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find modules with specific permission
     */
    public function findByPermission(string $permission, ?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('JSON_CONTAINS(m.permissions, :permission) = 1')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->setParameter('permission', json_encode($permission))
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC');

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find system modules
     */
    public function findSystemModules(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.system = :system')
            ->setParameter('system', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find non-system modules
     */
    public function findNonSystemModules(?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.system = :system')
            ->setParameter('system', false)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC');

        if ($organization !== null) {
            $qb->andWhere('m.organization = :organization')
                ->setParameter('organization', $organization);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find modules requiring specific license
     */
    public function findByLicenseType(string $licenseType, ?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.licenseRequired = :required')
            ->andWhere('m.licenseType = :type')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->setParameter('required', true)
            ->setParameter('type', $licenseType)
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC');

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find modules with public access
     */
    public function findPublicModules(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.publicAccess = :public')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->setParameter('public', true)
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find modules by tag
     */
    public function findByTag(string $tag, ?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('JSON_CONTAINS(m.tags, :tag) = 1')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->setParameter('tag', json_encode($tag))
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC');

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get module statistics
     */
    public function getModuleStats(?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select([
                'COUNT(m.id) as total',
                'SUM(CASE WHEN m.active = true THEN 1 ELSE 0 END) as active',
                'SUM(CASE WHEN m.enabled = true THEN 1 ELSE 0 END) as enabled',
                'SUM(CASE WHEN m.system = true THEN 1 ELSE 0 END) as system',
                'SUM(CASE WHEN m.licenseRequired = true THEN 1 ELSE 0 END) as licensed',
            ]);

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Find most used modules
     */
    public function findMostUsed(int $limit = 10, ?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->andWhere('m.usageCount > 0')
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('m.usageCount', 'DESC')
            ->setMaxResults($limit);

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find recently used modules
     */
    public function findRecentlyUsed(int $limit = 10, ?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->andWhere('m.lastUsedAt IS NOT NULL')
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('m.lastUsedAt', 'DESC')
            ->setMaxResults($limit);

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find modules with dependencies on a specific module
     */
    public function findDependentModules(string $moduleCode, ?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('JSON_CONTAINS(m.dependencies, :code) = 1')
            ->setParameter('code', json_encode($moduleCode));

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Search modules by name or description
     */
    public function search(string $query, ?Organization $organization = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.name LIKE :query OR m.description LIKE :query OR m.code LIKE :query')
            ->andWhere('m.active = :active')
            ->andWhere('m.enabled = :enabled')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('active', true)
            ->setParameter('enabled', true)
            ->orderBy('m.displayOrder', 'ASC')
            ->addOrderBy('m.name', 'ASC');

        if ($organization !== null) {
            $qb->andWhere('(m.organization = :organization OR m.organization IS NULL)')
                ->setParameter('organization', $organization);
        } else {
            $qb->andWhere('m.organization IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get hierarchical module tree
     */
    public function getModuleTree(?Organization $organization = null): array
    {
        $rootModules = $this->findRootModules($organization);
        $tree = [];

        foreach ($rootModules as $root) {
            $tree[] = $this->buildModuleNode($root);
        }

        return $tree;
    }

    /**
     * Build module tree node recursively
     */
    private function buildModuleNode(Module $module): array
    {
        $node = [
            'id' => $module->getId()->toRfc4122(),
            'name' => $module->getName(),
            'code' => $module->getCode(),
            'icon' => $module->getIcon(),
            'url' => $module->getUrl(),
            'children' => [],
        ];

        foreach ($module->getChildren() as $child) {
            if ($child->isActive() && $child->isEnabled()) {
                $node['children'][] = $this->buildModuleNode($child);
            }
        }

        return $node;
    }

    /**
     * Count modules by organization
     */
    public function countByOrganization(Organization $organization): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
