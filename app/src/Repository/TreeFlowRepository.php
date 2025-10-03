<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TreeFlow;
use App\Entity\Organization;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<TreeFlow>
 */
final class TreeFlowRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TreeFlow::class);
    }

    /**
     * Get entity name for search configuration
     */
    protected function getEntityName(): string
    {
        return 'treeflow';
    }

    /**
     * Get searchable fields for this entity
     */
    protected function getSearchableFields(): array
    {
        return ['name'];
    }

    /**
     * Define sortable fields mapping
     */
    protected function getSortableFields(): array
    {
        return [
            'name' => 'name',
            'version' => 'version',
            'active' => 'active',
            'organizationName' => 'organization.name',
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt',
        ];
    }

    /**
     * Define filterable fields
     */
    protected function getFilterableFields(): array
    {
        return [
            'name' => 'name',
            'version' => 'version',
            'active' => 'active',
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt',
        ];
    }

    /**
     * Define relationship filter mappings
     */
    protected function getRelationshipFilterFields(): array
    {
        return [
            'organizationName' => ['relation' => 'organization', 'field' => 'name'],
        ];
    }

    /**
     * Define boolean fields for proper filtering
     */
    protected function getBooleanFilterFields(): array
    {
        return ['active'];
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
