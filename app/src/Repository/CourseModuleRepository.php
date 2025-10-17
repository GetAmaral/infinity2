<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CourseModule;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<CourseModule>
 */
final class CourseModuleRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseModule::class);
    }

    /**
     * Get entity name for search configuration
     */
    protected function getEntityName(): string
    {
        return 'course_module';
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
            'viewOrder' => 'viewOrder',
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
            'viewOrder' => 'viewOrder',
            'releaseDate' => 'releaseDate',
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
            'courseName' => ['relation' => 'course', 'field' => 'name'],
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
     * Transform CourseModule entity to array for API response
     */
    protected function entityToArray(object $entity): array
    {
        assert($entity instanceof CourseModule);

        return [
            'id' => $entity->getId()?->toString() ?? '',
            'name' => $entity->getName(),
            'description' => $entity->getDescription() ?? '',
            'active' => $entity->isActive(),
            'releaseDate' => $entity->getReleaseDate()?->format('c'),
            'viewOrder' => $entity->getViewOrder(),
            'totalLengthSeconds' => $entity->getTotalLengthSeconds(),
            'totalLengthFormatted' => $entity->getTotalLengthFormatted(),
            'courseId' => $entity->getCourse()->getId()?->toString() ?? '',
            'courseName' => $entity->getCourse()->getName() ?? '',
            'lecturesCount' => $entity->getLectures()->count(),
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'updatedAt' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
