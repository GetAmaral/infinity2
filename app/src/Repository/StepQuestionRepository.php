<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StepQuestion;
use App\Entity\Step;
use App\Repository\Generated\StepQuestionRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StepQuestion>
 */
class StepQuestionRepository extends StepQuestionRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StepQuestion::class);
    }

    /**
     * Find questions for a step ordered by importance
     */
    public function findByStepOrderedByImportance(Step $step): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.step = :step')
            ->setParameter('step', $step)
            ->orderBy('q.importance', 'DESC')
            ->addOrderBy('q.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find high importance questions (7-10)
     */
    public function findHighImportanceQuestions(Step $step): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.step = :step')
            ->andWhere('q.importance >= :minImportance')
            ->setParameter('step', $step)
            ->setParameter('minImportance', 7)
            ->orderBy('q.importance', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
