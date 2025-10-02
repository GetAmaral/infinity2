<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Define searchable fields for User entity
     * Used by BaseRepository for full-text search
     */
    protected function getSearchableFields(): array
    {
        return ['name', 'email'];
    }

    /**
     * Define sortable fields mapping
     * API field name => Entity property
     */
    protected function getSortableFields(): array
    {
        return [
            'name' => 'name',
            'email' => 'email',
            'roles' => 'roles',
            'isVerified' => 'isVerified',
            'organizationName' => 'organization.name', // Relationship field - sortable but not filterable
            'lastLoginAt' => 'lastLoginAt',
            'createdAt' => 'createdAt',
        ];
    }

    /**
     * Define filterable fields (exclude relationship fields)
     */
    protected function getFilterableFields(): array
    {
        return [
            'name' => 'name',
            'email' => 'email',
            'isVerified' => 'isVerified',
            'lastLoginAt' => 'lastLoginAt',
            'createdAt' => 'createdAt',
        ];
    }

    /**
     * Define boolean fields for proper filtering
     */
    protected function getBooleanFilterFields(): array
    {
        return ['isVerified'];
    }

    /**
     * Define date fields for range filtering
     */
    protected function getDateFilterFields(): array
    {
        return ['lastLoginAt', 'createdAt'];
    }

    /**
     * Find all users with organization (eager loading)
     * @return User[]
     */
    public function findAllWithOrganization(): array
    {
        return $this->findAllWithRelations(['organization']);
    }

    /**
     * Find users by organization
     * @return User[]
     */
    public function findByOrganization(int $organizationId): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.organization', 'o')
            ->where('o.id = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users without organization
     * @return User[]
     */
    public function findWithoutOrganization(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.organization IS NULL')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get users statistics
     */
    public function getStatistics(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.organization', 'o')
            ->select('COUNT(DISTINCT u.id) as totalUsers')
            ->addSelect('COUNT(DISTINCT o.id) as usersWithOrganization')
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Find user by email
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
