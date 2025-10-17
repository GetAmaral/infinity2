<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StepOutput;
use App\Entity\Step;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StepOutput>
 */
class StepOutputRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StepOutput::class);
    }

    /**
     * Find all outputs for a step
     */
    public function findByStep(Step $step): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.step = :step')
            ->setParameter('step', $step)
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
