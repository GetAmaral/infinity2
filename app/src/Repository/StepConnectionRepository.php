<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Step;
use App\Entity\StepConnection;
use App\Entity\StepOutput;
use App\Repository\Generated\StepConnectionRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StepConnection>
 */
class StepConnectionRepository extends StepConnectionRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StepConnection::class);
    }

    /**
     * Check if a connection already exists between an output and step
     */
    public function connectionExists(StepOutput $output, Step $targetStep): bool
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.sourceOutput = :output')
            ->andWhere('c.targetStep = :targetStep')
            ->setParameter('output', $output)
            ->setParameter('targetStep', $targetStep)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }
}
