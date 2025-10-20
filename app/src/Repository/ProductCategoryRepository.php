<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProductCategory;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * ProductCategoryRepository - Specialized queries for product categories
 *
 * Provides optimized queries for:
 * - Hierarchical category retrieval
 * - Active/visible category filtering
 * - Category tree building
 * - Search and filtering
 * - Statistics and analytics
 *
 * @extends ServiceEntityRepository<ProductCategory>
 *
 * @method ProductCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductCategory[]    findAll()
 * @method ProductCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductCategory::class);
    }

    // ====================================
    // BASE QUERY BUILDERS
    // ====================================

    /**
     * Create base query builder with common joins and filters
     */
    private function createBaseQueryBuilder(string $alias = 'pc'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->leftJoin($alias . '.parentCategory', 'parent')
            ->addSelect('parent');
    }

    /**
     * Create query builder for active categories only
     */
    private function createActiveQueryBuilder(string $alias = 'pc'): QueryBuilder
    {
        return $this->createBaseQueryBuilder($alias)
            ->where($alias . '.active = :active')
            ->setParameter('active', true);
    }

    /**
     * Create query builder for visible categories only
     */
    private function createVisibleQueryBuilder(string $alias = 'pc'): QueryBuilder
    {
        return $this->createActiveQueryBuilder($alias)
            ->andWhere($alias . '.visible = :visible')
            ->setParameter('visible', true);
    }

    // ====================================
    // ROOT CATEGORIES
    // ====================================

    /**
     * Find all root categories (no parent)
     *
     * @return ProductCategory[]
     */
    public function findRootCategories(): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.parentCategory IS NULL')
            ->orderBy('pc.sortOrder', 'ASC')
            ->addOrderBy('pc.categoryName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active root categories
     *
     * @return ProductCategory[]
     */
    public function findActiveRootCategories(): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.parentCategory IS NULL')
            ->andWhere('pc.active = :active')
            ->setParameter('active', true)
            ->orderBy('pc.sortOrder', 'ASC')
            ->addOrderBy('pc.categoryName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find visible root categories (for frontend)
     *
     * @return ProductCategory[]
     */
    public function findVisibleRootCategories(): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.parentCategory IS NULL')
            ->andWhere('pc.active = :active')
            ->andWhere('pc.visible = :visible')
            ->setParameter('active', true)
            ->setParameter('visible', true)
            ->orderBy('pc.sortOrder', 'ASC')
            ->addOrderBy('pc.categoryName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ====================================
    // CHILD CATEGORIES
    // ====================================

    /**
     * Find children of a specific category
     *
     * @return ProductCategory[]
     */
    public function findChildrenByParent(ProductCategory $parent): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.parentCategory = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('pc.sortOrder', 'ASC')
            ->addOrderBy('pc.categoryName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active children of a specific category
     *
     * @return ProductCategory[]
     */
    public function findActiveChildrenByParent(ProductCategory $parent): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.parentCategory = :parent')
            ->andWhere('pc.active = :active')
            ->setParameter('parent', $parent)
            ->setParameter('active', true)
            ->orderBy('pc.sortOrder', 'ASC')
            ->addOrderBy('pc.categoryName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ====================================
    // HIERARCHY QUERIES
    // ====================================

    /**
     * Find categories by level
     *
     * @return ProductCategory[]
     */
    public function findByLevel(int $level): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.categoryLevel = :level')
            ->setParameter('level', $level)
            ->orderBy('pc.categoryPath', 'ASC')
            ->addOrderBy('pc.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find categories up to a maximum level
     *
     * @return ProductCategory[]
     */
    public function findUpToLevel(int $maxLevel): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.categoryLevel <= :maxLevel')
            ->setParameter('maxLevel', $maxLevel)
            ->orderBy('pc.categoryPath', 'ASC')
            ->addOrderBy('pc.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Build complete category tree (optimized with single query)
     *
     * @return array Tree structure with children
     */
    public function findCategoryTree(): array
    {
        $categories = $this->createQueryBuilder('pc')
            ->leftJoin('pc.childCategories', 'children')
            ->addSelect('children')
            ->orderBy('pc.sortOrder', 'ASC')
            ->addOrderBy('pc.categoryName', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->buildTree($categories);
    }

    /**
     * Build visible category tree (for frontend)
     *
     * @return array Tree structure with children
     */
    public function findVisibleCategoryTree(): array
    {
        $categories = $this->createQueryBuilder('pc')
            ->leftJoin('pc.childCategories', 'children')
            ->addSelect('children')
            ->where('pc.active = :active')
            ->andWhere('pc.visible = :visible')
            ->setParameter('active', true)
            ->setParameter('visible', true)
            ->orderBy('pc.sortOrder', 'ASC')
            ->addOrderBy('pc.categoryName', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->buildTree($categories);
    }

    /**
     * Helper method to build hierarchical tree from flat list
     */
    private function buildTree(array $categories, ?ProductCategory $parent = null): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->getParentCategory() === $parent) {
                $children = $this->buildTree($categories, $category);
                if (!empty($children)) {
                    $tree[] = [
                        'category' => $category,
                        'children' => $children
                    ];
                } else {
                    $tree[] = [
                        'category' => $category,
                        'children' => []
                    ];
                }
            }
        }

        return $tree;
    }

    // ====================================
    // SEARCH & FILTERING
    // ====================================

    /**
     * Search categories by name or description
     *
     * @return ProductCategory[]
     */
    public function searchByText(string $searchTerm): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.categoryName LIKE :search')
            ->orWhere('pc.description LIKE :search')
            ->orWhere('pc.categoryCode LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('pc.categoryPath', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find category by slug
     */
    public function findBySlug(string $slug): ?ProductCategory
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.categorySlug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find category by code
     */
    public function findByCode(string $code): ?ProductCategory
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.categoryCode = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // ====================================
    // FEATURED & SPECIAL CATEGORIES
    // ====================================

    /**
     * Find featured categories
     *
     * @return ProductCategory[]
     */
    public function findFeaturedCategories(int $limit = 10): array
    {
        return $this->createVisibleQueryBuilder('pc')
            ->andWhere('pc.isFeatured = :featured')
            ->setParameter('featured', true)
            ->orderBy('pc.sortOrder', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find categories for menu display
     *
     * @return ProductCategory[]
     */
    public function findMenuCategories(): array
    {
        return $this->createVisibleQueryBuilder('pc')
            ->andWhere('pc.showInMenu = :showInMenu')
            ->setParameter('showInMenu', true)
            ->orderBy('pc.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find categories for homepage
     *
     * @return ProductCategory[]
     */
    public function findHomepageCategories(): array
    {
        return $this->createVisibleQueryBuilder('pc')
            ->andWhere('pc.showOnHomepage = :showOnHomepage')
            ->setParameter('showOnHomepage', true)
            ->orderBy('pc.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ====================================
    // STATISTICS & ANALYTICS
    // ====================================

    /**
     * Get categories with product counts
     *
     * @return ProductCategory[]
     */
    public function findCategoriesWithProducts(): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.productCount > 0')
            ->orderBy('pc.productCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get top categories by product count
     *
     * @return ProductCategory[]
     */
    public function findTopCategoriesByProductCount(int $limit = 10): array
    {
        return $this->createVisibleQueryBuilder('pc')
            ->orderBy('pc.totalProductCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get top categories by view count
     *
     * @return ProductCategory[]
     */
    public function findTopCategoriesByViews(int $limit = 10): array
    {
        return $this->createVisibleQueryBuilder('pc')
            ->orderBy('pc.viewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get empty categories (no products)
     *
     * @return ProductCategory[]
     */
    public function findEmptyCategories(): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.productCount = 0')
            ->andWhere('pc.totalProductCount = 0')
            ->orderBy('pc.categoryPath', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ====================================
    // ORGANIZATION FILTERING
    // ====================================

    /**
     * Find categories by organization
     *
     * @return ProductCategory[]
     */
    public function findByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('pc.categoryPath', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find visible root categories by organization
     *
     * @return ProductCategory[]
     */
    public function findVisibleRootCategoriesByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.organization = :organization')
            ->andWhere('pc.parentCategory IS NULL')
            ->andWhere('pc.active = :active')
            ->andWhere('pc.visible = :visible')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->setParameter('visible', true)
            ->orderBy('pc.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ====================================
    // UTILITY METHODS
    // ====================================

    /**
     * Check if slug is available (not used by another category)
     */
    public function isSlugAvailable(string $slug, ?ProductCategory $excludeCategory = null): bool
    {
        $qb = $this->createQueryBuilder('pc')
            ->select('COUNT(pc.id)')
            ->where('pc.categorySlug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeCategory !== null) {
            $qb->andWhere('pc.id != :excludeId')
               ->setParameter('excludeId', $excludeCategory->getId());
        }

        return (int) $qb->getQuery()->getSingleScalarResult() === 0;
    }

    /**
     * Check if code is available (not used by another category)
     */
    public function isCodeAvailable(string $code, ?ProductCategory $excludeCategory = null): bool
    {
        $qb = $this->createQueryBuilder('pc')
            ->select('COUNT(pc.id)')
            ->where('pc.categoryCode = :code')
            ->setParameter('code', $code);

        if ($excludeCategory !== null) {
            $qb->andWhere('pc.id != :excludeId')
               ->setParameter('excludeId', $excludeCategory->getId());
        }

        return (int) $qb->getQuery()->getSingleScalarResult() === 0;
    }

    /**
     * Get total category count
     */
    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('pc')
            ->select('COUNT(pc.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get active category count
     */
    public function getActiveCount(): int
    {
        return (int) $this->createQueryBuilder('pc')
            ->select('COUNT(pc.id)')
            ->where('pc.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Save category
     */
    public function save(ProductCategory $category, bool $flush = true): void
    {
        $this->getEntityManager()->persist($category);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove category
     */
    public function remove(ProductCategory $category, bool $flush = true): void
    {
        $this->getEntityManager()->remove($category);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
