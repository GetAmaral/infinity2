<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Organization;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Organization>
 */
class OrganizationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    /**
     * Define searchable fields for Organization entity
     * Used by BaseRepository for full-text search
     */
    protected function getSearchableFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * Define sortable fields mapping
     * API field name => Entity property
     */
    protected function getSortableFields(): array
    {
        return [
            'name' => 'name',
            'description' => 'description',
            'userCount' => 'id', // Will need custom handling for computed fields
            'createdAt' => 'createdAt',
        ];
    }

    /**
     * Find all organizations with user counts (eager loading)
     * @return Organization[]
     */
    public function findAllWithUserCounts(): array
    {
        return $this->findAllWithRelations(['users']);
    }

    /**
     * Find organizations with most users
     * @return Organization[]
     */
    public function findMostActive(int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.users', 'u')
            ->addSelect('COUNT(u.id) as HIDDEN userCount')
            ->groupBy('o.id')
            ->orderBy('userCount', 'DESC')
            ->addOrderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find organizations by user count range
     * @return Organization[]
     */
    public function findByUserCountRange(int $minUsers = 0, ?int $maxUsers = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.users', 'u')
            ->addSelect('COUNT(u.id) as HIDDEN userCount')
            ->groupBy('o.id')
            ->having('COUNT(u.id) >= :minUsers')
            ->setParameter('minUsers', $minUsers);

        if ($maxUsers !== null) {
            $qb->andHaving('COUNT(u.id) <= :maxUsers')
               ->setParameter('maxUsers', $maxUsers);
        }

        return $qb->orderBy('userCount', 'DESC')
            ->addOrderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get organizations statistics
     */
    public function getStatistics(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.users', 'u')
            ->select('COUNT(DISTINCT o.id) as totalOrganizations')
            ->addSelect('COUNT(DISTINCT u.id) as totalUsers')
            ->getQuery()
            ->getSingleResult();
    }
}