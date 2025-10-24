<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StepConnection;
use App\Entity\StepInput;
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
     * Check if a connection already exists between an output and input
     */
    public function connectionExists(StepOutput $output, StepInput $input): bool
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.sourceOutput = :output')
            ->andWhere('c.targetInput = :input')
            ->setParameter('output', $output)
            ->setParameter('input', $input)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }
}
