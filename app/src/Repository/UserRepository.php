<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Repository\Generated\UserRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

final class UserRepository extends UserRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // Custom query methods below

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
