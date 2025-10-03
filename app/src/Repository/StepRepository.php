<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Step;
use App\Entity\TreeFlow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Step>
 */
class StepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Step::class);
    }

    /**
     * Find the first step in a TreeFlow
     */
    public function findFirstStep(TreeFlow $treeFlow): ?Step
    {
        return $this->createQueryBuilder('s')
            ->where('s.treeFlow = :treeFlow')
            ->andWhere('s.first = :first')
            ->setParameter('treeFlow', $treeFlow)
            ->setParameter('first', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all steps for a TreeFlow
     */
    public function findByTreeFlow(TreeFlow $treeFlow): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.treeFlow = :treeFlow')
            ->setParameter('treeFlow', $treeFlow)
            ->orderBy('s.first', 'DESC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
