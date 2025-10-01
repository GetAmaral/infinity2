<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CourseLecture;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<CourseLecture>
 */
final class CourseLectureRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseLecture::class);
    }

    /**
     * Get entity name for search configuration
     */
    protected function getEntityName(): string
    {
        return 'course_lecture';
    }

    /**
     * Get searchable fields for this entity
     */
    protected function getSearchableFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * Get filterable fields and their types
     */
    protected function getFilterableFields(): array
    {
        return [
            'course' => 'entity',
        ];
    }

    /**
     * Get default sort configuration
     */
    protected function getDefaultSort(): array
    {
        return ['viewOrder' => 'ASC', 'name' => 'ASC'];
    }

    /**
     * Define sortable fields mapping
     * API field name => Entity property
     */
    protected function getSortableFields(): array
    {
        return [
            'name' => 'name',
            'viewOrder' => 'viewOrder',
            'createdAt' => 'createdAt',
            'updatedAt' => 'updatedAt',
        ];
    }

    /**
     * Transform CourseLecture entity to array for API response
     */
    protected function entityToArray(object $entity): array
    {
        assert($entity instanceof CourseLecture);

        return [
            'id' => $entity->getId()?->toString() ?? '',
            'name' => $entity->getName(),
            'description' => $entity->getDescription() ?? '',
            'videoUrl' => $entity->getVideoUrl() ?? '',
            'viewOrder' => $entity->getViewOrder(),
            'courseId' => $entity->getCourse()->getId()?->toString() ?? '',
            'courseName' => $entity->getCourse()->getName() ?? '',
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'updatedAt' => $entity->getUpdatedAt()->format('c'),
        ];
    }

    /**
     * Find lectures by course ordered by view order
     */
    public function findByCourseOrdered(string $courseId): array
    {
        return $this->createQueryBuilder('cl')
            ->andWhere('cl.course = :courseId')
            ->setParameter('courseId', $courseId)
            ->orderBy('cl.viewOrder', 'ASC')
            ->addOrderBy('cl.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
