<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Course;
use App\Repository\Generated\CourseRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

final class CourseRepository extends CourseRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    // Add custom query methods here
}
