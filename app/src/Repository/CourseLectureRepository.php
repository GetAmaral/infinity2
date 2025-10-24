<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CourseLecture;
use App\Repository\Generated\CourseLectureRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<CourseLecture>
 */
final class CourseLectureRepository extends CourseLectureRepositoryGenerated
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
            'courseModule' => 'entity',
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
            'videoType' => $entity->getVideoType(),
            'durationSeconds' => $entity->getDurationSeconds(),
            'durationFormatted' => $entity->getDurationFormatted(),
            'viewOrder' => $entity->getViewOrder(),
            'active' => $entity->isActive(),
            'published' => $entity->isPublished(),
            'free' => $entity->isFree(),
            'publishedAt' => $entity->getPublishedAt()?->format('c'),
            'hasVideo' => $entity->hasVideo(),
            'hasTranscript' => $entity->hasTranscript(),
            'hasSubtitles' => $entity->hasSubtitles(),
            'hasAttachments' => $entity->hasAttachments(),
            'attachmentCount' => $entity->getAttachmentCount(),
            'isMicrolearning' => $entity->isMicrolearning(),
            'difficultyLevel' => $entity->getDifficultyLevel(),
            'viewCount' => $entity->getViewCount(),
            'completionCount' => $entity->getCompletionCount(),
            'completionRate' => $entity->getCompletionRate(),
            'averageWatchPercentage' => $entity->getAverageWatchPercentage(),
            'rating' => $entity->getRating(),
            'ratingCount' => $entity->getRatingCount(),
            'averageRating' => $entity->getAverageRating(),
            'pointsValue' => $entity->getPointsValue(),
            'courseModuleId' => $entity->getCourseModule()->getId()?->toString() ?? '',
            'courseModuleName' => $entity->getCourseModule()->getName() ?? '',
            'courseId' => $entity->getCourseModule()->getCourse()->getId()?->toString() ?? '',
            'courseName' => $entity->getCourseModule()->getCourse()->getName() ?? '',
            'organizationId' => $entity->getOrganization()->getId()?->toString() ?? '',
            'organizationName' => $entity->getOrganization()->getName() ?? '',
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'updatedAt' => $entity->getUpdatedAt()->format('c'),
        ];
    }

    /**
     * Find lectures by module ordered by view order
     */
    public function findByModuleOrdered(string $moduleId): array
    {
        return $this->createQueryBuilder('cl')
            ->andWhere('cl.courseModule = :moduleId')
            ->setParameter('moduleId', $moduleId)
            ->orderBy('cl.viewOrder', 'ASC')
            ->addOrderBy('cl.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find lectures by course ordered by module and view order
     * Gets all lectures from all modules of a course
     */
    public function findByCourseOrdered(string $courseId): array
    {
        return $this->createQueryBuilder('cl')
            ->join('cl.courseModule', 'cm')
            ->andWhere('cm.course = :courseId')
            ->setParameter('courseId', $courseId)
            ->orderBy('cm.viewOrder', 'ASC')
            ->addOrderBy('cl.viewOrder', 'ASC')
            ->addOrderBy('cl.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
