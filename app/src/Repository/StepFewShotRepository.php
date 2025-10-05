<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StepFewShot;
use App\Entity\StepQuestion;
use App\Enum\FewShotType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StepFewShot>
 */
class StepFewShotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StepFewShot::class);
    }

    /**
     * Find examples by type (positive or negative)
     */
    public function findByQuestionAndType(StepQuestion $question, FewShotType $type): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.question = :question')
            ->andWhere('f.type = :type')
            ->setParameter('question', $question)
            ->setParameter('type', $type)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all positive examples for a question
     */
    public function findPositiveExamples(StepQuestion $question): array
    {
        return $this->findByQuestionAndType($question, FewShotType::POSITIVE);
    }

    /**
     * Find all negative examples for a question
     */
    public function findNegativeExamples(StepQuestion $question): array
    {
        return $this->findByQuestionAndType($question, FewShotType::NEGATIVE);
    }
}
