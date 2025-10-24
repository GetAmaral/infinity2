<?php

declare(strict_types=1);

namespace App\Repository\Generator;

use App\Entity\Generator\GeneratorEntity;
use App\Repository\Base\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<GeneratorEntity>
 */
final class GeneratorEntityRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeneratorEntity::class);
    }

    /**
     * Get entity name for search configuration
     */
    protected function getEntityName(): string
    {
        return 'generator_entity';
    }

    /**
     * Get searchable fields for this entity
     */
    protected function getSearchableFields(): array
    {
        return ['entityName', 'entityLabel', 'pluralLabel', 'description'];
    }

    /**
     * Define sortable fields mapping
     * API field name => Entity property
     */
    protected function getSortableFields(): array
    {
        return [
            'entityName' => 'entityName',
            'entityLabel' => 'entityLabel',
            'isGenerated' => 'isGenerated',
            'menuGroup' => 'menuGroup',
            'menuOrder' => 'menuOrder',
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
            'entityName' => 'entityName',
            'entityLabel' => 'entityLabel',
            'isGenerated' => 'isGenerated',
            'apiEnabled' => 'apiEnabled',
            'voterEnabled' => 'voterEnabled',
            'menuGroup' => 'menuGroup',
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt',
        ];
    }

    /**
     * Define boolean fields for proper filtering
     */
    protected function getBooleanFilterFields(): array
    {
        return ['isGenerated', 'apiEnabled', 'voterEnabled', 'testEnabled', 'hasOrganization'];
    }

    /**
     * Define date fields for range filtering
     */
    protected function getDateFilterFields(): array
    {
        return ['createdAt', 'updatedAt', 'lastGeneratedAt'];
    }

    /**
     * Find all entities with their properties eagerly loaded
     */
    public function findAllWithProperties(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.properties', 'p')
            ->addSelect('p')
            ->orderBy('e.menuOrder', 'ASC')
            ->addOrderBy('e.entityName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find entity with properties eagerly loaded
     */
    public function findWithProperties(string $id): ?GeneratorEntity
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.properties', 'p')
            ->addSelect('p')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find entities for code generation with properties eagerly loaded
     * Ordered by menuGroup, menuOrder, and entityName
     *
     * @param string|null $entityFilter Optional entity name filter
     * @return array<GeneratorEntity>
     */
    public function findForGeneration(?string $entityFilter = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.properties', 'p')
            ->addSelect('p')
            ->orderBy('e.menuGroup', 'ASC')
            ->addOrderBy('e.menuOrder', 'ASC')
            ->addOrderBy('e.entityName', 'ASC');

        if ($entityFilter) {
            $qb->where('e.entityName = :name')
               ->setParameter('name', $entityFilter);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Transform GeneratorEntity to array for API response
     */
    protected function entityToArray(object $entity): array
    {
        assert($entity instanceof GeneratorEntity);

        return [
            'id' => $entity->getId()?->toString() ?? '',
            'entityName' => $entity->getEntityName(),
            'entityLabel' => $entity->getEntityLabel(),
            'pluralLabel' => $entity->getPluralLabel(),
            'icon' => $entity->getIcon(),
            'description' => $entity->getDescription(),
            'canvasX' => $entity->getCanvasX(),
            'canvasY' => $entity->getCanvasY(),
            'hasOrganization' => $entity->isHasOrganization(),
            'apiEnabled' => $entity->isApiEnabled(),
            'voterEnabled' => $entity->isVoterEnabled(),
            'menuGroup' => $entity->getMenuGroup(),
            'menuOrder' => $entity->getMenuOrder(),
            'isGenerated' => $entity->isGenerated(),
            'lastGeneratedAt' => $entity->getLastGeneratedAt()?->format('c'),
            'propertiesCount' => $entity->getProperties()->count(),
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'updatedAt' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
