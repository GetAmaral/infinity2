<?php

declare(strict_types=1);

namespace App\Repository\Generator;

use App\Entity\Generator\GeneratorProperty;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<GeneratorProperty>
 */
final class GeneratorPropertyRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeneratorProperty::class);
    }

    /**
     * Get entity name for search configuration
     */
    protected function getEntityName(): string
    {
        return 'generator_property';
    }

    /**
     * Get searchable fields for this entity
     */
    protected function getSearchableFields(): array
    {
        return ['propertyName', 'propertyLabel', 'propertyType'];
    }

    /**
     * Define sortable fields mapping
     * API field name => Entity property
     */
    protected function getSortableFields(): array
    {
        return [
            'propertyName' => 'propertyName',
            'propertyLabel' => 'propertyLabel',
            'propertyType' => 'propertyType',
            'propertyOrder' => 'propertyOrder',
            'relationshipType' => 'relationshipType',
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt',
        ];
    }

    /**
     * Define filterable fields (exclude relationship and computed fields)
     */
    protected function getFilterableFields(): array
    {
        return [
            'propertyName' => 'propertyName',
            'propertyLabel' => 'propertyLabel',
            'propertyType' => 'propertyType',
            'relationshipType' => 'relationshipType',
            'nullable' => 'nullable',
            'unique' => 'unique',
            'showInList' => 'showInList',
            'showInDetail' => 'showInDetail',
            'showInForm' => 'showInForm',
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
            'entityName' => ['relation' => 'entity', 'field' => 'entityName'],
        ];
    }

    /**
     * Define boolean fields for proper filtering
     */
    protected function getBooleanFilterFields(): array
    {
        return [
            'nullable',
            'unique',
            'orphanRemoval',
            'formRequired',
            'formReadOnly',
            'showInList',
            'showInDetail',
            'showInForm',
            'sortable',
            'searchable',
            'filterable',
            'apiReadable',
            'apiWritable',
        ];
    }

    /**
     * Define date fields for range filtering
     */
    protected function getDateFilterFields(): array
    {
        return ['createdAt', 'updatedAt'];
    }

    /**
     * Find properties by entity ID
     */
    public function findByEntity(string $entityId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.entity = :entityId')
            ->setParameter('entityId', $entityId)
            ->orderBy('p.propertyOrder', 'ASC')
            ->addOrderBy('p.propertyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find relationship properties (properties with relationships)
     */
    public function findRelationshipProperties(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.relationshipType IS NOT NULL')
            ->orderBy('p.propertyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Transform GeneratorProperty to array for API response
     */
    protected function entityToArray(object $entity): array
    {
        assert($entity instanceof GeneratorProperty);

        return [
            'id' => $entity->getId()?->toString() ?? '',
            'entityId' => $entity->getEntity()->getId()?->toString() ?? '',
            'entityName' => $entity->getEntity()->getEntityName(),
            'propertyName' => $entity->getPropertyName(),
            'propertyLabel' => $entity->getPropertyLabel(),
            'propertyType' => $entity->getPropertyType(),
            'propertyOrder' => $entity->getPropertyOrder(),
            'nullable' => $entity->isNullable(),
            'unique' => $entity->isUnique(),
            'length' => $entity->getLength(),
            'relationshipType' => $entity->getRelationshipType(),
            'targetEntity' => $entity->getTargetEntity(),
            'showInList' => $entity->isShowInList(),
            'showInDetail' => $entity->isShowInDetail(),
            'showInForm' => $entity->isShowInForm(),
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'updatedAt' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
