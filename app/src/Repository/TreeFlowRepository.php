<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TreeFlow;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TreeFlow>
 */
class TreeFlowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TreeFlow::class);
    }

    /**
     * Find active TreeFlows for an organization
     */
    public function findActiveByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.organization = :organization')
            ->andWhere('t.active = :active')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a TreeFlow by name and organization
     */
    public function findOneByNameAndOrganization(string $name, Organization $organization): ?TreeFlow
    {
        return $this->createQueryBuilder('t')
            ->where('t.name = :name')
            ->andWhere('t.organization = :organization')
            ->setParameter('name', $name)
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
