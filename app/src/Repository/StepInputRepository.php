<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StepInput;
use App\Entity\Step;
use App\Enum\InputType;
use App\Repository\Generated\StepInputRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StepInput>
 */
class StepInputRepository extends StepInputRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StepInput::class);
    }

    /**
     * Find all inputs for a step
     */
    public function findByStep(Step $step): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.step = :step')
            ->setParameter('step', $step)
            ->orderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find inputs from a specific source step
     */
    public function findBySource(Step $sourceStep): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.sourceStep = :source')
            ->setParameter('source', $sourceStep)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find inputs by type
     */
    public function findByStepAndType(Step $step, InputType $type): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.step = :step')
            ->andWhere('i.type = :type')
            ->setParameter('step', $step)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }
}
