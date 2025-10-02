<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Course>
 */
final class CourseRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    /**
     * Get entity name for search configuration
     */
    protected function getEntityName(): string
    {
        return 'course';
    }

    /**
     * Get searchable fields for this entity
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
            'active' => 'active',
            'ownerName' => 'owner.name', // Relationship field - sortable but not filterable
            'releaseDate' => 'releaseDate',
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
            'name' => 'name',
            'active' => 'active',
            'releaseDate' => 'releaseDate',
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt',
        ];
    }

    /**
     * Define relationship filter mappings
     * When filtering by ownerName, search in the owner's name
     */
    protected function getRelationshipFilterFields(): array
    {
        return [
            'ownerName' => ['relation' => 'owner', 'field' => 'name'],
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
     * Define date fields for range filtering
     */
    protected function getDateFilterFields(): array
    {
        return ['releaseDate', 'createdAt', 'updatedAt'];
    }

    /**
     * Transform Course entity to array for API response
     */
    protected function entityToArray(object $entity): array
    {
        assert($entity instanceof Course);

        return [
            'id' => $entity->getId()?->toString() ?? '',
            'name' => $entity->getName(),
            'description' => $entity->getDescription() ?? '',
            'active' => $entity->isActive(),
            'releaseDate' => $entity->getReleaseDate()?->format('c'),
            'totalLength' => $entity->getTotalLength(),
            'organizationId' => $entity->getOrganization()->getId()?->toString() ?? '',
            'organizationName' => $entity->getOrganization()->getName() ?? '',
            'ownerId' => $entity->getOwner()->getId()?->toString() ?? '',
            'ownerName' => $entity->getOwner()->getName() ?? '',
            'lecturesCount' => $entity->getLectures()->count(),
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'updatedAt' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
