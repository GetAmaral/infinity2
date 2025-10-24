<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Role;
use App\Repository\Generated\RoleRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

final class RoleRepository extends RoleRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    // Custom query methods below

    /**
     * Find all non-system roles
     */
    public function findNonSystemRoles(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isSystem = :isSystem')
            ->setParameter('isSystem', false)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find role by name
     */
    public function findOneByName(string $name): ?Role
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find roles with specific permission
     */
    public function findByPermission(string $permission): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('JSON_CONTAINS(r.permissions, :permission) = 1')
            ->setParameter('permission', json_encode($permission))
            ->getQuery()
            ->getResult();
    }
}
